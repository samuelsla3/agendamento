<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAtendimento extends Model
{
    protected $table = 'registros_atendimentos';

    protected $fillable = [
    'id_horario_original', 
    'nome', 
    'matricula', 
    'status', 
    'observacao', 
    'data_registro',
    'data_atendimento',
    'hora_atendimento'
];

    public function horarioOriginal()
        {
            return $this->belongsTo(Horario::class, 'id_horario_original', 'id');
        }
}