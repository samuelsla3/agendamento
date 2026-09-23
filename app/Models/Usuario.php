<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
    'nome',
    'matricula',
    'email',
    'turma_codigo',
    'senha',
    'tipo',
    'data_nascimento',
    'cidade',
];

    protected $hidden = ['senha']; 

    
    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function getTurmaFormatadaAttribute(): string
{
    if (empty($this->turma_codigo)) {
        return 'Não informada';
    }

    // Se o código tiver 7 ou mais caracteres (ex: 20261.4.18.1I), pega só os últimos 7 ("4.18.1I")
    if (strlen($this->turma_codigo) >= 7) {
        return substr($this->turma_codigo, -7);
    }

    return $this->turma_codigo;
}
}