<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAtendimento extends Model
{
    protected $table = 'registros_atendimentos';

    protected $fillable = [
        'id_horario_original', 'matricula_aluno', 'nome_aluno', 
        'data_atendimento', 'hora_atendimento', 'status', 'observacao', 'data_registro'
    ];
}