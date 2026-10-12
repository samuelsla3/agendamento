<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerificacaoEmail
{
    public const VALIDADE_MINUTOS = 15;
    public const MAX_TENTATIVAS = 5;
    public const INTERVALO_SEGUNDOS = 60;

    public static function resumo(Usuario $usuario): array
    {
        $pendente = null;
        try {
            $pendente = $usuario->email_pendente;
        } catch (Throwable $e) {
            // A página continua utilizável; solicitar outro código substitui o registro ilegível.
        }
        $ativo = $pendente && $usuario->email_codigo_hash && $usuario->email_codigo_expira_em?->isFuture()
            && $usuario->email_codigo_tentativas < self::MAX_TENTATIVAS;
        $aviso = $usuario->email_verificacao_aviso_id
            ? DB::table('avisos_email')->where('id', $usuario->email_verificacao_aviso_id)->first(['estado']) : null;
        return [
            'atual' => (string) $usuario->email, 'confirmado' => $usuario->emailConfirmado(),
            'pendente' => $pendente, 'codigo_ativo' => (bool) $ativo,
            'versao' => (int) $usuario->email_codigo_versao,
            'expira_em' => $usuario->email_codigo_expira_em?->toIso8601String(),
            'reenviar_em' => $usuario->email_codigo_criado_em?->copy()->addSeconds(self::INTERVALO_SEGUNDOS)->toIso8601String(),
            'envio' => $aviso?->estado,
            'agora' => now()->toIso8601String(),
        ];
    }

    public static function hashCodigo(Usuario $usuario, string $codigo): string
    {
        return hash_hmac('sha256', $usuario->id . '|' . $usuario->email_codigo_versao . '|' . $codigo, config('app.key'));
    }

    public static function condicaoValida(array $condicao, string $email): bool
    {
        $usuario = Usuario::find($condicao['usuario_id'] ?? null);
        if (!$usuario || !in_array($usuario->tipo, ['estudante', 'aluno'], true)
            || (int) $usuario->email_codigo_versao !== (int) ($condicao['versao'] ?? -1)
            || !$usuario->email_codigo_hash || !$usuario->email_codigo_expira_em?->isFuture()
            || $usuario->email_codigo_tentativas >= self::MAX_TENTATIVAS) {
            return false;
        }
        return hash_equals((string) $usuario->email_pendente, $email);
    }

    public static function limparPendente(Usuario $usuario): void
    {
        $usuario->forceFill([
            'email_pendente' => null, 'email_codigo_hash' => null, 'email_codigo_expira_em' => null,
            'email_codigo_criado_em' => null, 'email_codigo_tentativas' => 0,
            'email_verificacao_aviso_id' => null, 'email_codigo_versao' => (int) $usuario->email_codigo_versao + 1,
        ]);
    }
}
