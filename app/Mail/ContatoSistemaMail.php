<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ContatoSistemaMail extends Mailable
{
    public function __construct(
        private string $assuntoContato,
        private string $textoContato,
        private string $nomeContato,
        private string $responderPara,
        private array $arquivosContato = [],
    ) {}

    public function build()
    {
        $this->subject($this->assuntoContato)
            ->replyTo($this->responderPara, $this->nomeContato)
            ->view('emails.contato-sistema')
            ->text('emails.contato-sistema-texto')
            ->with(['textoContato' => $this->textoContato, 'nomeContato' => $this->nomeContato]);

        foreach ($this->arquivosContato as $arquivo) {
            $conteudo = file_get_contents($arquivo['upload']->getRealPath());
            if ($conteudo === false) throw new \RuntimeException('anexo_indisponivel');
            $this->attachData($conteudo, $arquivo['nome'], ['mime' => $arquivo['mime']]);
        }

        return $this;
    }
}
