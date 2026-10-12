<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Http\Request;

class AcessoProntuario
{
    public static function valido(Request $request, Usuario $usuario): bool
    {
        $ultimo = (int) $request->session()->get('prontuario_ultimo_acesso', 0);
        $limite = max(1, (int) config('psicologia.prontuario_inatividade_minutos', 30)) * 60;
        return SegurancaPsicologa::sessaoAtual($request, $usuario) && SegurancaPsicologa::configurada($usuario)
            && $request->session()->get('prontuario_autorizado') === true
            && (int) $request->session()->get('prontuario_usuario_id', 0) === (int) $usuario->id
            && (int) $request->session()->get('prontuario_versao', 0) === (int) $usuario->psicologa_prontuario_versao
            && $ultimo <= now()->timestamp && $ultimo > now()->timestamp - $limite;
    }

    public static function liberar(Request $request, Usuario $usuario): void
    {
        $request->session()->put(['prontuario_autorizado' => true, 'prontuario_usuario_id' => $usuario->id,
            'prontuario_versao' => (int) $usuario->psicologa_prontuario_versao,
            'prontuario_ultimo_acesso' => now()->timestamp]);
    }

    public static function bloquear(Request $request): void
    {
        $request->session()->forget(['prontuario_autorizado', 'prontuario_usuario_id',
            'prontuario_versao', 'prontuario_ultimo_acesso']);
    }

    public static function exigir(Request $request, ?Usuario $usuario = null): void
    {
        $usuario ??= $request->user();
        abort_unless($usuario && self::valido($request, $usuario), 403,
            'O prontuário está bloqueado. Valide a senha do prontuário novamente.');
        $request->session()->put('prontuario_ultimo_acesso', now()->timestamp);
    }
}
