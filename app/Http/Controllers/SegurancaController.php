<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AcessoProntuario;
use App\Services\SegurancaPsicologa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SegurancaController extends Controller
{
    private function usuario(Request $request, bool $bloquear = false): Usuario
    {
        $query = Usuario::whereKey($request->user()->id);
        if ($bloquear) $query->lockForUpdate();
        $usuario = $query->firstOrFail();
        abort_unless(SegurancaPsicologa::sessaoAtual($request, $usuario), 403, 'Faça login novamente.');
        return $usuario;
    }

    private function conferirVersao(Request $request, Usuario $usuario): void
    {
        abort_unless((int) $request->input('versao_acesso') === (int) $usuario->psicologa_autenticacao_versao
            && (int) $request->input('versao_prontuario') === (int) $usuario->psicologa_prontuario_versao,
            409, 'As senhas mudaram em outra aba. Atualize esta página antes de continuar.');
    }

    public function primeiro(Request $request)
    {
        $usuario = $this->usuario($request);
        if (SegurancaPsicologa::configurada($usuario)) return redirect()->route('seguranca.index');
        return response()->view('seguranca.primeiro', compact('usuario'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function index(Request $request)
    {
        $usuario = $this->usuario($request);
        return response()->view('seguranca.index', compact('usuario'))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    // Escritas protegidas pela transação e trava agenda de RequisicaoUnica.
    public function cadastrar(Request $request)
    {
        $usuario = $this->usuario($request, true);
        abort_if(SegurancaPsicologa::configurada($usuario), 409, 'O cadastro inicial já foi concluído.');
        $dados = $request->validate([
            'senha_atual' => $usuario->psicologa_conta_teste ? ['required', 'string', 'max:512'] : ['nullable', 'string'],
            'usar_senha_suap' => ['nullable', 'boolean'],
            'senha_local' => SegurancaPsicologa::regrasNovaSenha(),
            'senha_prontuario' => SegurancaPsicologa::regrasNovaSenha(),
            'versao_acesso' => 'required|integer|min:1', 'versao_prontuario' => 'required|integer|min:1',
        ], SegurancaPsicologa::mensagensSenha());
        $this->conferirVersao($request, $usuario);
        if ($usuario->psicologa_conta_teste) {
            if (!SegurancaPsicologa::conferir($dados['senha_atual'], $usuario->senha)) {
                throw ValidationException::withMessages(['senha_atual' => 'A senha atual do login está incorreta.']);
            }
        } else {
            $confirmado = (int) $request->session()->get('psicologa_suap_confirmado_em', 0);
            abort_unless($request->session()->get('psicologa_login_metodo') === 'suap'
                && $confirmado > now()->subMinutes(30)->timestamp && $confirmado <= now()->timestamp,
                403, 'A confirmação do SUAP expirou. Saia e entre pelo SUAP para concluir o cadastro.');
        }
        $usarSuap = $request->boolean('usar_senha_suap');
        if ($usuario->psicologa_conta_teste && $usarSuap) {
            throw ValidationException::withMessages(['usar_senha_suap' => 'Esta opção é exclusiva do primeiro acesso real pelo SUAP.']);
        }
        if (!$usuario->psicologa_conta_teste) {
            $mesmaSenha = SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, $dados['senha_local']);
            if ($usarSuap && !$mesmaSenha) {
                throw ValidationException::withMessages(['senha_local' => 'Digite a mesma senha utilizada neste acesso pelo SUAP. Se a confirmação não estiver mais disponível, saia e entre novamente pelo SUAP.']);
            }
            if (!$usarSuap && $mesmaSenha) {
                throw ValidationException::withMessages(['usar_senha_suap' => 'Para reutilizar a senha do SUAP, marque a opção de autorização ou escolha uma senha diferente.']);
            }
        }
        if (hash_equals($dados['senha_local'], $dados['senha_prontuario'])) {
            throw ValidationException::withMessages(['senha_prontuario' => 'Escolha uma senha de prontuário diferente da senha local.']);
        }
        $usuario->forceFill(['senha' => Hash::make($dados['senha_local']), 'password' => null,
            'senha_prontuario' => Hash::make($dados['senha_prontuario']), 'psicologa_senha_local_definida' => true,
            'psicologa_configurada_em' => now(),
            'psicologa_autenticacao_versao' => (int) $usuario->psicologa_autenticacao_versao + 1,
            'psicologa_prontuario_versao' => (int) $usuario->psicologa_prontuario_versao + 1])->save();
        $this->renovarSessao($request, $usuario);
        return redirect()->route('psicologa.index')->with('sucesso', 'Senhas cadastradas. O prontuário usa a senha própria que você acabou de criar.');
    }

    public function alterarLocal(Request $request)
    {
        return $this->alterar($request, false);
    }

    public function alterarProntuario(Request $request)
    {
        return $this->alterar($request, true);
    }

    private function alterar(Request $request, bool $prontuario)
    {
        $usuario = $this->usuario($request, true);
        abort_unless(SegurancaPsicologa::configurada($usuario), 403);
        $dados = $request->validate(['senha_atual' => ['required', 'string', 'max:512'],
            'nova_senha' => SegurancaPsicologa::regrasNovaSenha(),
            'versao_acesso' => 'required|integer|min:1', 'versao_prontuario' => 'required|integer|min:1'],
            SegurancaPsicologa::mensagensSenha());
        $this->conferirVersao($request, $usuario);
        $campo = $prontuario ? 'senha_prontuario' : 'senha';
        if (!SegurancaPsicologa::conferir($dados['senha_atual'], $usuario->{$campo})) {
            throw ValidationException::withMessages(['senha_atual' => 'A senha atual informada está incorreta.']);
        }
        if (SegurancaPsicologa::conferir($dados['nova_senha'], $usuario->{$campo})) {
            throw ValidationException::withMessages(['nova_senha' => 'Escolha uma senha diferente da atual.']);
        }
        if (SegurancaPsicologa::conferir($dados['nova_senha'], $usuario->{$prontuario ? 'senha' : 'senha_prontuario'})) {
            throw ValidationException::withMessages(['nova_senha' => 'A senha local e a do prontuário precisam ser diferentes.']);
        }
        $usuario->{$campo} = Hash::make($dados['nova_senha']);
        $usuario->psicologa_prontuario_versao = (int) $usuario->psicologa_prontuario_versao + 1;
        if (!$prontuario) {
            $usuario->password = null;
            $usuario->psicologa_autenticacao_versao = (int) $usuario->psicologa_autenticacao_versao + 1;
        }
        $usuario->save();
        $this->renovarSessao($request, $usuario);
        return redirect()->route('seguranca.index')->with('sucesso', $prontuario
            ? 'Senha do prontuário alterada. Os acessos desbloqueados foram revogados.'
            : 'Senha local alterada. As outras sessões foram revogadas; a senha do SUAP continua independente.');
    }

    private function renovarSessao(Request $request, Usuario $usuario): void
    {
        Auth::setUser($usuario);
        $request->session()->regenerate(true);
        $request->session()->put('psicologa_autenticacao_versao', (int) $usuario->psicologa_autenticacao_versao);
        $request->session()->forget(['psicologa_suap_confirmado_em',
            'psicologa_suap_senha_hash', 'psicologa_suap_senha_usuario_id']);
        AcessoProntuario::bloquear($request);
    }
}
