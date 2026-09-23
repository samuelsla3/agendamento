<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmacaoAtendimentoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $agendamento;
    public $urlCancelamento;

    public function __construct($agendamento)
    {
        $this->agendamento = $agendamento;

        // Gera a URL assinada temporária (48h de validade) para o cancelamento direto
        $this->urlCancelamento = URL::temporarySignedRoute(
            'agendamento.cancelarDirect',
            now()->addHours(48),
            ['id' => $agendamento->id]
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lembrete: Confirmação do seu Atendimento Psicológico Amanhã',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmacao_atendimento',
        );
    }
}