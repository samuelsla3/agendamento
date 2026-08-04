<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    protected $table = 'agendamentos';

    protected $fillable = ['id_horario', 'nome', 'observacao'];

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'id_horario');
    }
}