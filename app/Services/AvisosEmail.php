<?php

namespace App\Services;

use App\Jobs\EnviarAvisoEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AvisosEmail
{
    public static function registrar(
        string $evento,
        string $email,
        string $assunto,
        string $html,
        ?array $condicao = null
    ): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        DB::transaction(function () use ($evento, $email, $assunto, $html, $condicao) {
            $chave = hash('sha256', $evento);
            $inseriu = DB::table('avisos_email')->insertOrIgnore([
                'evento' => $chave,
                'conteudo' => Crypt::encryptString(json_encode(
                    compact('email', 'assunto', 'html', 'condicao'),
                    JSON_THROW_ON_ERROR
                )),
                'estado' => 'pendente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inseriu !== 1) {
                return;
            }

            $id = (int) DB::table('avisos_email')->where('evento', $chave)->value('id');

            // Aguarda também a transação externa do controller/middleware.
            // Um rollback descarta este callback e não envia o e-mail.
            DB::afterCommit(function () use ($id) {
                try {
                    // Chamada direta: reutiliza a proteção do aviso, sem criar job.
                    // A resposta HTTP aguarda esta tentativa de envio.
                    (new EnviarAvisoEmail($id))->handle();
                } catch (Throwable $e) {
                    // A operação já foi confirmada no banco. Falha no e-mail
                    // não deve transformá-la em erro nem provocar novo envio.
                    Log::warning('Não foi possível concluir o envio direto do aviso.', [
                        'aviso_id' => $id,
                        'erro_tipo' => get_class($e),
                    ]);
                }
            });
        });
    }
}
