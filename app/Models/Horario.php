<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'data',
        'hora',
        'disponivel',
        'nome',
        'matricula',
        'confirmado',
        'justificativa_cancelamento',
        'token_cancelamento',
    ];

    protected $hidden = [
        'token_cancelamento',
    ];

    public function agendamento()
    {
        return $this->hasOne(Agendamento::class, 'id_horario');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'matricula', 'matricula');
    }

    /**
     * Deve ser chamado dentro de DB::transaction().
     */
    public function bloquearReservaAtual(): self
    {
        $atual = self::whereKey($this->getKey())
            ->lockForUpdate()
            ->first();

        abort_if(
            !$atual
            || (int) $atual->disponivel !== 0
            || (int) $atual->confirmado === 1
            || empty($this->token_cancelamento)
            || $atual->token_cancelamento !== $this->token_cancelamento,
            409,
            'Este agendamento foi alterado ou encerrado. Atualize a página.'
        );

        return $atual;
    }
}