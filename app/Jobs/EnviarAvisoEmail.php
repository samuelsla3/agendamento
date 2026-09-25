<?php
namespace App\Jobs;

use App\Mail\AvisoSistemaMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarAvisoEmail implements ShouldQueue
{
    use Dispatchable, Queueable;
    public int $tries = 1;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    public function __construct(public int $avisoId) {}

    public function handle(): void
    {
        $aviso = DB::transaction(function () {
            $aviso = DB::table('avisos_email')->where('id', $this->avisoId)->lockForUpdate()->first();
            if (!$aviso || $aviso->estado !== 'pendente') { return null; }
            // Uma segunda cópia do job não pode enviar novamente.
            DB::table('avisos_email')->where('id', $this->avisoId)->update([
                'estado' => 'em_envio', 'updated_at' => now(),
            ]);
            return $aviso;
        });
        if (!$aviso) { return; }
        try {
            $dados = json_decode(Crypt::decryptString($aviso->conteudo), true, 512, JSON_THROW_ON_ERROR);

            if (!empty($dados['condicao'])) {
                $condicao = $dados['condicao'];
                $horario = \App\Models\Horario::find($condicao['horario_id']);
                if (!$horario || (int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1
                    || !hash_equals($condicao['reserva_hash'], hash('sha256', $horario->token_cancelamento ?? ''))
                    || $horario->data !== $condicao['data']
                    || \Carbon\Carbon::parse($horario->data.' '.$horario->hora)->isPast()) {
                    DB::table('avisos_email')->where('id', $this->avisoId)->update([
                        'estado' => 'descartado', 'conteudo' => '', 'updated_at' => now(),
                    ]);
                    return;
                }
            }
            Mail::to($dados['email'])->send(new AvisoSistemaMail($dados['assunto'], $dados['html']));
            DB::table('avisos_email')->where('id', $this->avisoId)->update([
                'estado' => 'enviado', 'enviado_em' => now(), 'updated_at' => now(),
                'conteudo' => '', // Remove destinatário e corpo após confirmação do transporte.
            ]);
        } catch (Throwable $e) {
            $this->marcarRevisao($e);
            // Não gravar mensagem SMTP: pode conter destinatário ou conteúdo.
            throw new \RuntimeException('Envio de aviso requer revisão; consulte o registro pelo ID.');
        }
    }
    public function failed(?Throwable $e): void { $this->marcarRevisao($e); }
    private function marcarRevisao(?Throwable $e): void
    {
        DB::table('avisos_email')->where('id', $this->avisoId)->where('estado', 'em_envio')->update([
            'estado' => 'revisar', 'erro_tipo' => $e ? get_class($e) : 'falha_worker', 'updated_at' => now(),
        ]);
    }
}
