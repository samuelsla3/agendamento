<?php

namespace App\Console\Commands;

use App\Models\Horario;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class EnviarLembretesAtendimento extends Command
{
    protected $signature = 'atendimentos:enviar-lembretes';
    protected $description = 'Envia e-mail de lembrete/confirmação com a view e o link de cancelamento';

    public function handle()
    {
        $amanha = Carbon::tomorrow()->toDateString();

        $horarios = Horario::where('data', $amanha)
    ->where('disponivel', 0)
    ->where(function ($query) {
        $query->where('confirmado', 0)
            ->orWhereNull('confirmado');
    })
    ->whereNotNull('token_cancelamento')
    ->get();

        if ($horarios->isEmpty()) {
            $this->warn("Nenhum agendamento encontrado para a data de amanhã ({$amanha}).");
            return;
        }

        $count = 0;

        foreach ($horarios as $horario) {
            $aluno = null;

            if (!empty($horario->matricula)) {
                $aluno = Usuario::where('matricula', $horario->matricula)->first();
            }

            if (!$aluno && (isset($horario->aluno_id) || isset($horario->user_id))) {
                $alunoId = $horario->aluno_id ?? $horario->user_id;
                $aluno = Usuario::find($alunoId);
            }

            if ($aluno && !empty($aluno->email)) {
                // Generates the signed URL for direct cancellation valid for 48 hours
                $urlCancelamento = URL::temporarySignedRoute(
    'agendamento.cancelarDirect',
    now()->addHours(48),
    [
        'id' => $horario->id,
        'token' => $horario->token_cancelamento,
    ]
);

                try {
                    // Uses Mail::send linking the Blade view and passing the data variables
                    Mail::send('emails.confirmacao_atendimento', [
                        'agendamento' => $horario,
                        'urlCancelamento' => $urlCancelamento
                    ], function ($message) use ($aluno) {
                        $message->to($aluno->email)
                                ->subject('Lembrete: Confirmação do seu Atendimento Psicológico Amanhã');
                    });

                    $count++;
                    $this->info("Lembrete (com View e Link) enviado para: {$aluno->email}");
                } catch (\Exception $e) {
                    Log::error("Erro ao enviar lembrete para {$aluno->email}: " . $e->getMessage());
                    $this->error("Falha ao enviar para {$aluno->email}");
                }
            } else {
                $this->warn("Horário ID {$horario->id} ignorado: Aluno/E-mail não localizado.");
            }
        }

        $this->info("Sucesso! Total de {$count} lembretes enviados.");
    }
}