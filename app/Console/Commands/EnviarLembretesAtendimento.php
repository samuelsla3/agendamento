<?php

namespace App\Console\Commands;

#precisa-se de importar:
use App\Mail\ConfirmacaoAtendimentoMail; #a classe da mensagem do email
use App\Models\Horario; #model que consulta a tabela que armazena os horários no banco
use Carbon\Carbon; #biblioteca interessante para manipulação de datas
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail; #*facade essencial para o disparo de emails

class EnviarLembretesAtendimento extends Command
{
    #declarado abaixo o comando que o agendador rodará ou o terminal
    protected $signature = 'atendimentos:enviar-lembretes'; #signature ligada ao console.php
    protected $description = 'Envia e-mail de lembrete/confirmação para alunos com agendamento amanhã';

    public function handle()
{
    $amanha = Carbon::tomorrow()->toDateString(); // YYYY-MM-DD

    #busca dados no banco
    $horarios = Horario::with('usuario')
        ->whereDate('data', $amanha)
        ->where('disponivel', 0)
        ->get();

    $count = 0;

    #importante: loop que percorre cada horario encontrado para enviar a mensagem
    foreach ($horarios as $horario) {
        #busca o email do usuario do horario
        $email = $horario->usuario->email ?? null;

        if ($email) {
            #Passa o objeto do agendamento (ou do próprio horário) para a classe do e-mail
            $objetoParaEmail = $horario->agendamento ?? $horario;

            #disparo do email: instancia a classe Mailable e envia ao destinatário
            Mail::to($email)->send(new ConfirmacaoAtendimentoMail($objetoParaEmail));
            $count++;
        }
    }

    $this->info("Sucesso! {$count} lembretes foram enviados.");
}
}

#código para teste manual
# php artisan atendimentos:enviar-lembretes