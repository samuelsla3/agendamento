<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProntuarioSessao extends Model
{
    protected $table = 'prontuario_sessoes';

    protected $fillable = [
        'aluno_id',
        'horario_id',
        'anotacoes',
        'data_sessao',
    ];

    protected $casts = [
        'anotacoes' => 'encrypted',
        'data_sessao' => 'date',
    ];

    public function aluno()
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }
}