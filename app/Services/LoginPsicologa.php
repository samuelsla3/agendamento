<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginPsicologa
{
    public function entrar(Request $request, string $matricula, string $senha, SuapService $suap)
    {
        $existente = Usuario::where('matricula', $matricula)->first();
        $testeLocal = $existente?->psicologa_conta_teste && SegurancaPsicologa::testesPermitidos()
            && !SegurancaPsicologa::matriculaAutorizada($matricula);
        // Após o cadastro inicial, a matrícula identifica o login local da psicóloga.
        // Contas de teste continuam restritas ao ambiente local/testing autorizado.
        $localConfigurado = $existente && !$existente->psicologa_conta_teste
            && SegurancaPsicologa::configurada($existente)
            && SegurancaPsicologa::autorizada($existente);
        if ($testeLocal || $localConfigurado) {
            $usuario = DB::transaction(function () use ($matricula, $senha) {
                $this->travar();
                $usuario = Usuario::where('matricula', $matricula)->lockForUpdate()->first();
                if (!$usuario || !SegurancaPsicologa::autorizada($usuario)
                    || !$usuario->psicologa_senha_local_definida
                    || !SegurancaPsicologa::conferir($senha, $usuario->senha)) return null;
                return $usuario;
            });
            if (!$usuario) return $this->falhar($request);
            SegurancaPsicologa::iniciarSessao($request, $usuario, 'local');
            return $this->destino($usuario);
        }
        if (!SegurancaPsicologa::matriculaAutorizada($matricula)) return $this->falhar($request);
        $token = $suap->autenticar($matricula, $senha);
        $perfil = $token ? $suap->meusDados($token) : null;
        if (!$token || !is_array($perfil) || !$perfil) return $this->falhar($request);
        // Se a API fornece a matrícula, ela precisa corresponder ao login autorizado.
        $identidade = $perfil['vinculo']['matricula'] ?? $perfil['matricula'] ?? $perfil['username'] ?? null;
        if ($identidade !== null && (!is_scalar($identidade) || trim((string) $identidade) !== $matricula)) {
            return $this->falhar($request);
        }
        $nome = $perfil['vinculo']['nome'] ?? $perfil['nome_completo'] ?? $perfil['nome_usual'] ?? $perfil['nome'] ?? null;
        if (!is_string($nome) || trim($nome) === '') return $this->falhar($request);
        $nome = mb_substr(trim($nome), 0, 100, 'UTF-8');
        $email = null;
        foreach ([$perfil['email_pessoal'] ?? null, $perfil['vinculo']['email_pessoal'] ?? null,
            $perfil['email'] ?? null, $perfil['vinculo']['email'] ?? null] as $candidato) {
            if (is_string($candidato) && strlen(trim($candidato)) <= 100
                && filter_var(trim($candidato), FILTER_VALIDATE_EMAIL) !== false) {
                $email = trim($candidato); break;
            }
        }
        $usuario = DB::transaction(function () use ($matricula, $nome, $email) {
            $this->travar();
            $usuario = Usuario::where('matricula', $matricula)->lockForUpdate()->first();
            $novoPerfil = !$usuario || $usuario->tipo !== 'psicologa';
            $usuario ??= new Usuario();
            $contato = $usuario->email ?: $email;
            if ($contato && Usuario::where('email', $contato)->where('matricula', '<>', $matricula)->exists()) {
                $contato = null; // E-mail compartilhado não concede nem impede privilégios.
            }
            $usuario->forceFill(['matricula' => $matricula, 'nome' => $nome, 'email' => $contato,
                'tipo' => 'psicologa', 'cadastro_provisorio' => false, 'psicologa_conta_teste' => false]);
            if ($novoPerfil) {
                // Placeholder aleatório: nunca copia a senha do SUAP como senha local.
                $usuario->forceFill(['senha' => Hash::make(Str::random(64)), 'password' => null,
                    'psicologa_senha_local_definida' => false, 'senha_prontuario' => null,
                    'psicologa_configurada_em' => null]);
            }
            $usuario->save();
            return $usuario->fresh();
        });
        SegurancaPsicologa::iniciarSessao($request, $usuario, 'suap');
        SegurancaPsicologa::registrarSenhaSuap($request, $usuario, $senha);
        return $this->destino($usuario);
    }

    private function travar(): void
    {
        abort_unless(DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first(), 503,
            'Execute as migrations antes de usar o sistema.');
    }

    private function destino(Usuario $usuario)
    {
        return redirect()->route(SegurancaPsicologa::configurada($usuario) ? 'psicologa.index' : 'seguranca.primeiro');
    }

    private function falhar(Request $request)
    {
        return back()->withErrors(['matricula' => 'Não foi possível entrar. Confira a matrícula e a senha. No primeiro acesso da psicóloga, use o SUAP.'])
            ->withInput($request->only('matricula'));
    }
}
