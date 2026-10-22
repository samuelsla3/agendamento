<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class RecuperacaoSenhaPsicologaMail extends Mailable
{
    public function __construct(public string $urlRecuperacao, public string $tipoSenha) {}
    public function build()
    {
        $senha = $this->tipoSenha === 'prontuario' ? 'do prontuário' : 'local';
        return $this->subject('Psicologia IFBA: recuperação da senha '.$senha)
            ->html('<p>Recebemos uma solicitação para redefinir a senha '.$senha.' da psicóloga.</p>'
                .'<p><a href="'.e($this->urlRecuperacao).'">Criar nova senha '.$senha.'</a></p>'
                .'<p>Este link vale por 15 minutos e só pode ser utilizado uma vez. As anotações não serão apagadas.</p>'
                .'<p>Se você não fez a solicitação, ignore este e-mail. Nenhuma senha foi alterada.</p>');
    }
}
