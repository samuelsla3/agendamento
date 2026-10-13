<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;

class AvisoSistemaMail extends Mailable
{
    public function __construct(private string $assuntoAviso, private string $htmlAviso,
        private ?string $identificador = null) {}
    public function build()
    {
        $mensagem = $this->subject($this->assuntoAviso)->html($this->htmlAviso);
        if ($this->identificador) {
            $mensagem->withSymfonyMessage(function (\Symfony\Component\Mime\Email $email) {
                $email->getHeaders()->remove('Message-ID');
                $email->getHeaders()->addIdHeader('Message-ID', $this->identificador);
            });
        }
        return $mensagem;
    }
}
