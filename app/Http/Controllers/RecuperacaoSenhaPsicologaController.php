<?php

namespace App\Http\Controllers;

use App\Services\{RecuperacaoSenhaPsicologa, SegurancaPsicologa};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RecuperacaoSenhaPsicologaController extends Controller
{
    private function privado($resposta)
    {
        return $resposta->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }
    public function formulario(Request $request)
    {
        return $this->privado(response()->view('seguranca.recuperar', [
            'matricula' => $request->user()?->tipo === 'psicologa' ? $request->user()->matricula : '',
        ]));
    }
    public function enviar(Request $request, RecuperacaoSenhaPsicologa $servico)
    {
        $dados = $request->validate(['matricula' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'tipo' => 'required|in:local,prontuario']);
        $chave = 'recuperar-psi:'.hash_hmac('sha256', $dados['matricula'].'|'.$dados['tipo'], config('app.key'));
        // A mesma resposta é retornada para matrícula desconhecida, contato ausente e limite atingido.
        if (!RateLimiter::tooManyAttempts($chave, 3)) {
            RateLimiter::hit($chave, 1800);
            $servico->solicitar($dados['matricula'], $dados['tipo']);
        }
        return $this->privado(redirect()->route('seguranca.recuperacao.form')
            ->with('sucesso', RecuperacaoSenhaPsicologa::MENSAGEM));
    }
    public function redefinir(Request $request, string $id, RecuperacaoSenhaPsicologa $servico)
    {
        if ($request->query->has('token')) {
            $request->session()->forget('recuperacao_psicologa');
            $token = $request->query('token');
            $prova = is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) ? hash('sha256', $token) : null;
            if (!$servico->conferir($id, $prova)) return $this->invalido();
            $request->session()->put('recuperacao_psicologa', compact('id', 'prova'));
            // Retira o segredo da URL antes de renderizar recursos ou o formulário.
            return $this->privado(redirect()->route('seguranca.recuperacao.redefinir', ['id' => $id]));
        }
        $prova = $this->prova($request, $id);
        $recuperacao = $servico->conferir($id, $prova);
        if (!$recuperacao) return $this->invalido();
        return $this->privado(response()->view('seguranca.redefinir', compact('recuperacao')));
    }
    public function salvar(Request $request, string $id, RecuperacaoSenhaPsicologa $servico)
    {
        $prova = $this->prova($request, $id);
        if (!$servico->conferir($id, $prova)) return $this->invalido();
        $dados = $request->validate(['nova_senha' => SegurancaPsicologa::regrasNovaSenha()], SegurancaPsicologa::mensagensSenha());
        $usuario = $servico->redefinir($id, $prova, $dados['nova_senha']);
        $request->session()->forget('recuperacao_psicologa');
        if ((int) $request->user()?->id === (int) $usuario->id) SegurancaPsicologa::encerrar($request);
        return $this->privado(redirect()->route('login')->with('sucesso',
            'Senha redefinida. Entre com sua senha local. Os prontuários precisam ser desbloqueados novamente. A senha do SUAP não foi alterada.'));
    }
    private function prova(Request $request, string $id): ?string
    {
        $dados = $request->session()->get('recuperacao_psicologa', []);
        return ($dados['id'] ?? null) === $id && is_string($dados['prova'] ?? null) ? $dados['prova'] : null;
    }
    private function invalido()
    {
        return $this->privado(response()->view('seguranca.redefinir', ['recuperacao' => null], 410));
    }
}
