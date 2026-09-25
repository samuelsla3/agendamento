<?php
namespace App\Console\Commands;
use App\Models\Horario;
use App\Models\Usuario;
use App\Services\AvisosEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class EnviarLembretesAtendimento extends Command
{
    protected $signature = 'atendimentos:enviar-lembretes';
    protected $description = 'Registra lembretes únicos e tenta enviá-los diretamente';
    public function handle(): int
    {
        Horario::where('data', now()->addDay()->toDateString())->where('disponivel', 0)
            ->where(function ($q) { $q->where('confirmado', 0)->orWhereNull('confirmado'); })
            ->whereNotNull('token_cancelamento')->orderBy('id')->chunkById(100, function ($horarios) {
                foreach ($horarios as $horario) {
                    $aluno = Usuario::where('matricula', $horario->matricula)->first();
                    if (!$aluno || !$aluno->email) { continue; }
                    $urlCancelamento = URL::temporarySignedRoute('agendamento.cancelarDirect', now()->addHours(48),
                        ['id' => $horario->id, 'token' => $horario->token_cancelamento]);
                    $html = view('emails.confirmacao_atendimento', ['agendamento' => $horario, 'urlCancelamento' => $urlCancelamento])->render();
                    AvisosEmail::registrar('lembrete:'.$horario->token_cancelamento.':'.$horario->data, $aluno->email,
                        'Lembrete: Confirmação do seu Atendimento Psicológico Amanhã', $html,
                        ['horario_id' => $horario->id, 'reserva_hash' => hash('sha256', $horario->token_cancelamento), 'data' => $horario->data]);
                }
            });
        $this->info('Processamento de lembretes concluído. Consulte avisos_email para verificar o resultado dos envios. Eventos já registrados não foram duplicados.');
        return self::SUCCESS;
    }
}
