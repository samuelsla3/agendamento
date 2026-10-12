<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SegurancaPsicologa
{
    public static function matriculaAutorizada(string $matricula): bool
    {
        return $matricula !== '' && in_array($matricula, config('psicologia.matriculas_suap', []), true);
    }

    public static function testesPermitidos(): bool
    {
        return app()->environment(['local', 'testing']) && config('psicologia.permitir_contas_teste', false) === true;
    }

    public static function autorizada(Usuario $usuario): bool
    {
        if ($usuario->tipo !== 'psicologa') return false;
        return $usuario->psicologa_conta_teste ? self::testesPermitidos()
            : self::matriculaAutorizada((string) $usuario->matricula);
    }

    public static function configurada(Usuario $usuario): bool
    {
        return $usuario->psicologa_configurada_em !== null
            && $usuario->psicologa_senha_local_definida && is_string($usuario->senha_prontuario)
            && $usuario->senha_prontuario !== '';
    }

    public static function conferir(string $senha, ?string $hash): bool
    {
        if (!$hash) return false;
        try { return Hash::check($senha, $hash); } catch (\RuntimeException $e) { return false; }
    }

    public static function regrasNovaSenha(): array
    {
        return ['bail', 'required', 'string', 'max:64', 'min:12', 'confirmed', 'regex:/^(?=.*\p{L})(?=.*[0-9]).+$/us',
            function (string $attribute, mixed $value, \Closure $fail): void {
                // Não permite truncamento silencioso por bcrypt com caracteres multibyte.
                if (is_string($value) && strlen($value) > 72) $fail('A senha é muito longa. Tente uma frase mais curta.');
            }];
    }

    public static function mensagensSenha(): array
    {
        return ['*.required' => 'Preencha os campos de senha.', '*.confirmed' => 'A confirmação precisa ser igual à nova senha.',
            '*.max' => 'Use uma senha de até 64 caracteres.', '*.min' => 'Use pelo menos 12 caracteres.',
            '*.regex' => 'Inclua letras e números na senha.'];
    }

    public static function iniciarSessao(Request $request, Usuario $usuario, string $metodo): void
    {
        Auth::login($usuario);
        $request->session()->regenerate(true);
        $request->session()->forget(['suap_jwt', 'confirmar_email_primeiro_acesso', 'prontuario_autorizado',
            'prontuario_usuario_id', 'prontuario_versao', 'prontuario_ultimo_acesso', 'aluno_id_pendente',
            'psicologa_suap_senha_hash', 'psicologa_suap_senha_usuario_id']);
        $request->session()->put([
            'usuario_tipo' => 'psicologa', 'tipo' => 'psicologa',
            'usuario_nome' => $usuario->nome, 'nome' => $usuario->nome,
            'matricula' => $usuario->matricula, 'email' => $usuario->email,
            'psicologa_autenticacao_versao' => (int) $usuario->psicologa_autenticacao_versao,
            'psicologa_login_metodo' => $metodo,
            'psicologa_suap_confirmado_em' => $metodo === 'suap' ? now()->timestamp : null,
        ]);
    }

    // Prova temporária do primeiro login SUAP. Nunca guarda a senha em texto puro.
    public static function registrarSenhaSuap(Request $request, Usuario $usuario, string $senha): void
    {
        $request->session()->forget(['psicologa_suap_senha_hash', 'psicologa_suap_senha_usuario_id']);
        if ($usuario->psicologa_conta_teste || self::configurada($usuario)
            || !self::sessaoAtual($request, $usuario)
            || $request->session()->get('psicologa_login_metodo') !== 'suap'
            || $senha === '' || strlen($senha) > 72) return;
        $request->session()->put(['psicologa_suap_senha_hash' => Hash::make($senha),
            'psicologa_suap_senha_usuario_id' => (int) $usuario->id]);
    }

    public static function senhaSuapCorresponde(Request $request, Usuario $usuario, string $senha): bool
    {
        $confirmado = (int) $request->session()->get('psicologa_suap_confirmado_em', 0);
        $hash = $request->session()->get('psicologa_suap_senha_hash');
        return !$usuario->psicologa_conta_teste && !self::configurada($usuario)
            && self::sessaoAtual($request, $usuario)
            && $request->session()->get('psicologa_login_metodo') === 'suap'
            && $confirmado > now()->subMinutes(30)->timestamp && $confirmado <= now()->timestamp
            && (int) $request->session()->get('psicologa_suap_senha_usuario_id', 0) === (int) $usuario->id
            && $senha !== '' && strlen($senha) <= 72 && is_string($hash) && self::conferir($senha, $hash);
    }

    public static function sessaoAtual(Request $request, Usuario $usuario): bool
    {
        return (int) $usuario->psicologa_autenticacao_versao > 0 && self::autorizada($usuario)
            && (int) $request->session()->get('psicologa_autenticacao_versao', 0)
                === (int) $usuario->psicologa_autenticacao_versao;
    }

    public static function encerrar(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
