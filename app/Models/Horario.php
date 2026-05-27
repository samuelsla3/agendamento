<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'data', 'hora', 'disponivel', 'nome', 'matricula', 'confirmado', 'justificativa_cancelamento'
    ];

    // Relacionamento: Um horário pode ter um agendamento vinculado a ele
    public function agendamento()
    {
        return $this->hasOne(Agendamento::class, 'id_horario');
    }
}