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
    if (
        (int) $agendamento->disponivel !== 0
        || (int) $agendamento->confirmado === 1
        || empty($agendamento->token_cancelamento)
    ) {
        throw new \InvalidArgumentException(
            'Não é possível enviar lembrete para uma reserva inativa.'
        );
    }

    // Guarda os dados deste destinatário para o processamento na fila.
    // Evita recarregar depois um horário ocupado por outra pessoa.
    $this->agendamento = (object) [
        'nome' => $agendamento->nome ?? 'Discente',
        'data' => $agendamento->data,
        'hora' => $agendamento->hora,
    ];

    $this->urlCancelamento = URL::temporarySignedRoute(
        'agendamento.cancelarDirect',
        now()->addHours(48),
        [
            'id' => $agendamento->id,
            'token' => $agendamento->token_cancelamento,
        ]
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