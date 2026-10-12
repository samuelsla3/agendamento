<?php

namespace App\Jobs;

use App\Mail\AvisoSistemaMail;
use App\Models\Horario;
use App\Models\Usuario;
use App\Services\VerificacaoEmail;
use Illuminate\Support\Facades\Crypt;
use App\Services\AvisosEmail;
use App\Services\InformacoesAtendimento;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class EnviarAvisoEmail implements ShouldQueue
{
    use Dispatchable, Queueable;
    public int $tries = 1;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    private ?string $tentativaId = null;
    public function __construct(public int $avisoId) {}

    public function handle(): void
    {
        $aviso = DB::transaction(function () {
            $aviso = DB::table('avisos_email')->where('id', $this->avisoId)->lockForUpdate()->first();
            if (!$aviso || $aviso->estado !== 'pendente') {
                return null;
            }
            $this->tentativaId = (string) Str::uuid();
            // Somente uma cópia pode assumir este aviso. SMTP fica fora da transação.
            DB::table('avisos_email')->where('id', $this->avisoId)->update([
                'estado' => 'em_envio', 'tentativa_id' => $this->tentativaId,
                'tentativas' => (int) $aviso->tentativas + 1, 'iniciado_em' => now(),
                'revisao' => (int) $aviso->revisao + 1, 'updated_at' => now(),
            ]);
            return $aviso;
        });
        if (!$aviso) {
            return;
        }
        $transporteIniciado = false;
        try {
            $dados = AvisosEmail::dados($aviso);
            $contexto = AvisosEmail::contexto($aviso);
            $verificacao = $aviso->tipo === 'verificacao_email';
            if ($verificacao) {
                if (($dados['condicao']['finalidade'] ?? null) !== 'verificacao_email'
                    || !VerificacaoEmail::condicaoValida($dados['condicao'], $dados['email'])) {
                    $this->resultado('descartado', 'codigo_expirado', ['conteudo' => '']);
                    return;
                }
            } elseif (!empty($dados['condicao'])) {
                $condicao = $dados['condicao'];
                $horario = Horario::find($condicao['horario_id'] ?? null);
                if (!$horario || (int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1
                    || empty($horario->token_cancelamento)
                    || !hash_equals((string) ($condicao['reserva_hash'] ?? ''), hash('sha256', $horario->token_cancelamento))
                    || $horario->data !== ($condicao['data'] ?? null)
                    || !Carbon::parse($horario->data . ' ' . $horario->hora, config('app.timezone'))->gt(now())) {
                    $this->resultado('descartado', 'reserva_encerrada', ['conteudo' => '']);
                    return;
                }
            }
            if (!$verificacao) {
                $matricula = $contexto['matricula'] ?? (isset($horario) ? $horario->matricula : null);
                $aluno = $matricula ? Usuario::where('matricula', $matricula)->first() : null;
                if (!$aluno || !$aluno->emailConfirmado()) {
                    $this->resultado('sem_contato', $aluno ? 'email_nao_confirmado' : 'vinculo_contato_ausente');
                    return;
                }
                // Outros dispositivos podem ter sessão antiga. O banco é a fonte do contato.
                $dados['email'] = (string) $aluno->email;
                $contexto['email'] = $dados['email'];
                $contexto['matricula'] = $aluno->matricula;
                $contexto['nome'] = $aluno->nome;
                DB::table('avisos_email')->where('id', $this->avisoId)->where('estado', 'em_envio')
                    ->where('tentativa_id', $this->tentativaId)->update([
                        'conteudo' => Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)),
                        'contexto' => Crypt::encryptString(json_encode($contexto, JSON_THROW_ON_ERROR)),
                        'updated_at' => now(),
                    ]);
            }
            if (filter_var($dados['email'], FILTER_VALIDATE_EMAIL) === false) {
                $this->resultado('sem_contato', 'sem_contato');
                return;
            }
            $html = $verificacao ? $dados['html'] : InformacoesAtendimento::complementarEmail($dados['html']);
            // Estável entre tentativas do MESMO aviso; não substitui a trava/idempotência.
            $dominio = substr(strrchr((string) config('mail.from.address'), '@') ?: '@avisos.psicologia.invalid', 1);
            if (!preg_match('/^[A-Za-z0-9.-]+$/', $dominio)) {
                $dominio = 'avisos.psicologia.invalid';
            }
            $identificador = 'aviso-' . substr($aviso->evento, 0, 48) . '@' . $dominio;
            $mensagem = new AvisoSistemaMail($dados['assunto'], $html, $identificador);
            $destinatario = Mail::to($dados['email']);
            $transporteIniciado = true;
            $enviado = $destinatario->send($mensagem);
            if ($enviado === null) {
                // Um listener pode interromper o envio sem lançar exceção.
                $this->resultado('falhou', 'envio_interrompido');
                return;
            }
            $this->resultado('enviado', null, [
                'enviado_em' => now(), 'conteudo' => '', 'erro_tipo' => null,
            ]);
        } catch (Throwable $e) {
            // Depois de entrar no transporte, uma desconexão pode deixar o resultado
            // incerto. Nunca repetir automaticamente e nunca armazenar mensagem/debug SMTP.
            $this->resultado($transporteIniciado ? 'revisar' : 'falhou',
                $transporteIniciado ? 'envio_incerto' : 'preparacao', ['erro_tipo' => get_class($e)]);
        }
    }

    public function failed(?Throwable $e): void
    {
        // Um callback de job antigo não pode alterar uma nova tentativa.
        if ($this->tentativaId !== null) {
            $this->resultado('revisar', 'envio_incerto', ['erro_tipo' => $e ? get_class($e) : 'falha_worker']);
        }
    }

    private function resultado(string $estado, ?string $motivo, array $extra = []): void
    {
        DB::table('avisos_email')->where('id', $this->avisoId)
            ->where('estado', 'em_envio')->where('tentativa_id', $this->tentativaId)
            ->update(array_merge($extra, ['estado' => $estado, 'motivo_codigo' => $motivo,
                'revisao' => DB::raw('revisao + 1'), 'updated_at' => now()]));
    }
}
