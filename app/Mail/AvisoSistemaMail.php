<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;

class AvisoSistemaMail extends Mailable
{
    public function __construct(private string $assuntoAviso, private string $htmlAviso) {}
    public function build()
    {
        return $this->subject($this->assuntoAviso)->html($this->htmlAviso);
    }
}
