<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmacaoAtendimentoMail extends Mailable
{
    use Queueable, SerializesModels;

    #aqui estão variáveis public, que ficam disponíveis automaticamente na view que pede a confirmação do atendimento
    public $agendamento;
    public $urlCancelamento;

    public function __construct($agendamento)
    {
        #$agendamento recebe os dados enviados pelo Controller/Comando Artisan
        $this->agendamento = $agendamento;

        #a estrutura abaixo gera o link que vale por 48 horas para lembrar o aluno 
        #e torna possível o cancelamento direto no email:
        $this->urlCancelamento = URL::temporarySignedRoute(
            'agendamento.cancelarDirect', #nome da rota definida no arquivo routes
            now()->addHours(48), #define expiração da url
            ['id' => $agendamento->id] #id do agendamento passada como parâmetro
        );
    }

    #define cofigurações de cabeçalho do email, como assunto
    public function envelope(): Envelope
    {
        return new Envelope(
            #assunto do email
            subject: 'Lembrete: Confirmação do seu Atendimento Psicológico Amanhã',
        );
    }

    #associa o envio do email a view do email
    public function content(): Content
    {
        return new Content(
            #caminho da view
            view: 'emails.confirmacao_atendimento',
        );
    }
}