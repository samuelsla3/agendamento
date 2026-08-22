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

    public function usuario()
    {
        // Conecta a coluna 'matricula' de Horario com a coluna 'matricula' do Model Usuario
        return $this->belongsTo(Usuario::class, 'matricula', 'matricula');
        // NOTA: Se a sua classe de usuários se chamar User::class, substitua Usuario::class por User::class
    }
}