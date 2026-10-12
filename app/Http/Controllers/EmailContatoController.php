<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AvisosEmail;
use App\Services\VerificacaoEmail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmailContatoController extends Controller
{
    private function aluno(Request $request, bool $bloquear = false): Usuario
    {
        $autenticado = $request->user();
        abort_unless($autenticado && in_array($autenticado->tipo, ['estudante', 'aluno'], true), 403);
        $query = Usuario::whereKey($autenticado->id);
        if ($bloquear) {
            $query->lockForUpdate();
        }
        return $query->firstOrFail();
    }

    public function status(Request $request)
    {
        return response()->json(VerificacaoEmail::resumo($this->aluno($request)))
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    // As duas escritas usam a trava agenda e a transação de RequisicaoUnica.
    // Login SUAP e confirmação de e-mail ficam serializados durante a gravação local.
    public function enviar(Request $request)
    {
        $usuario = $this->aluno($request, true);
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')), 'UTF-8')]);
        }
        $dados = $request->validate(['email' => 'required|string|email:rfc|max:100'], [
            'email.required' => 'Informe o e-mail que você mais utiliza.',
            'email.string' => 'Informe um endereço de e-mail válido.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O endereço pode ter até 100 caracteres.',
        ]);
        if (filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['email' => 'Informe um endereço de e-mail válido.']);
        }
        if (Usuario::where('email', $dados['email'])->where('id', '<>', $usuario->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Não foi possível utilizar este endereço. Confira ou escolha outro e-mail.']);
        }
        if ($usuario->emailConfirmado() && strcasecmp($usuario->email, $dados['email']) === 0) {
            VerificacaoEmail::limparPendente($usuario);
            $usuario->save();
            return response()->json(['status' => 'success', 'message' => 'Este e-mail já está confirmado.']);
        }
        if ($usuario->email_codigo_criado_em?->copy()->addSeconds(VerificacaoEmail::INTERVALO_SEGUNDOS)->isFuture()) {
            throw ValidationException::withMessages(['email' => 'Aguarde um minuto após a solicitação anterior para pedir outro código.']);
        }
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $usuario->forceFill([
            'email_pendente' => $dados['email'], 'email_codigo_versao' => (int) $usuario->email_codigo_versao + 1,
            'email_codigo_expira_em' => now()->addMinutes(VerificacaoEmail::VALIDADE_MINUTOS),
            'email_codigo_criado_em' => now(), 'email_codigo_tentativas' => 0,
        ]);
        $usuario->email_codigo_hash = VerificacaoEmail::hashCodigo($usuario, $codigo);
        $usuario->save();
        $id = AvisosEmail::registrar('verificar_email:' . $usuario->id . ':' . $usuario->email_codigo_versao,
            $dados['email'], 'Confirme seu e-mail — Agendamento IFBA',
            '<p>Use este código para confirmar o e-mail informado no sistema de agendamento do IFBA:</p>'
            . '<p style="font-size:28px;letter-spacing:4px;"><strong>' . e($codigo) . '</strong></p>'
            . '<p>O código vale por 15 minutos. Se você não fez esta solicitação, ignore esta mensagem.</p>',
            ['finalidade' => 'verificacao_email', 'usuario_id' => $usuario->id, 'versao' => $usuario->email_codigo_versao],
            ['nome' => $usuario->nome, 'matricula' => $usuario->matricula]);
        $usuario->forceFill(['email_verificacao_aviso_id' => $id])->save();
        return response()->json(['status' => 'success', 'message' => 'Código solicitado. Confira sua caixa de entrada e o spam. Use o código mais recente.']);
    }

    public function confirmar(Request $request)
    {
        $usuario = $this->aluno($request, true);
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'versao' => 'required|integer|min:0',
        ], ['codigo.regex' => 'Informe o código de seis números.', 'codigo.required' => 'Informe o código recebido.']);
        if ((int) $dados['versao'] !== (int) $usuario->email_codigo_versao) {
            return $this->recusar('codigo_antigo', 'Esse código foi substituído. Atualize e use o código mais recente.');
        }
        if (!$usuario->email_codigo_hash || !$usuario->email_codigo_expira_em?->isFuture()) {
            return $this->recusar('codigo_expirado', 'O código expirou ou já foi utilizado. Solicite outro código.');
        }
        if ($usuario->email_codigo_tentativas >= VerificacaoEmail::MAX_TENTATIVAS) {
            return $this->recusar('codigo_bloqueado', 'O limite de tentativas deste código foi atingido. Solicite outro código.');
        }
        if (!hash_equals($usuario->email_codigo_hash, VerificacaoEmail::hashCodigo($usuario, $dados['codigo']))) {
            $usuario->forceFill(['email_codigo_tentativas' => $usuario->email_codigo_tentativas + 1])->save();
            return $this->recusar('codigo_invalido', $usuario->email_codigo_tentativas >= VerificacaoEmail::MAX_TENTATIVAS
                ? 'O limite de tentativas foi atingido. Solicite outro código.' : 'Código incorreto. Confira o código recebido.');
        }
        $email = $usuario->email_pendente;
        if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->recusar('codigo_expirado', 'Solicite outro código para confirmar o endereço.');
        }
        // Reconfere a unicidade sob a mesma trava usada no login/cadastro.
        if (Usuario::where('email', $email)->where('id', '<>', $usuario->id)->exists()) {
            return $this->recusar('email_indisponivel', 'Não foi possível utilizar este endereço. Escolha outro e-mail.');
        }
        $usuario->forceFill([
            'email' => $email, 'email_escolhido' => true, 'email_confirmado_em' => now(),
            'email_confirmado_hash' => hash('sha256', mb_strtolower(trim($email), 'UTF-8')),
        ]);
        VerificacaoEmail::limparPendente($usuario);
        $usuario->save();
        $request->session()->put('email', $usuario->email);
        $request->session()->forget('confirmar_email_primeiro_acesso');
        return response()->json(['status' => 'success', 'message' => 'E-mail confirmado! Os próximos avisos serão enviados para este endereço.']);
    }

    private function recusar(string $status, string $mensagem)
    {
        // Decisão de negócio concluída: preserva a contagem de erros na transação.
        // status=error ou HTTP 422 faria RequisicaoUnica desfazer essa contagem.
        return response()->json(['status' => $status, 'message' => $mensagem]);
    }
}
