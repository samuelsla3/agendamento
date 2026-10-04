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
    'cadastro_provisorio',
    'senha',
    'tipo',
    'data_nascimento',
    'cidade',
];

    protected $hidden = ['senha', 'password'];

    protected $casts = ['cadastro_provisorio' => 'boolean'];

    
    public function getAuthPassword()
    {
        return $this->senha;
    }

    public static function formatarTurma(?string $codigo): string
{
    $codigo = trim($codigo ?? '');

    if ($codigo === '') {
        return 'Não informada';
    }

    // Exemplo: 20261.4.18.1I → 4.18.1I
    return preg_replace(
        '/^\d{4}[12]\.(?=\d+\.\d+\.[A-Za-z0-9]+$)/',
        '',
        $codigo
    ) ?? $codigo;
}

public function getTurmaFormatadaAttribute(): string
{
    return self::formatarTurma($this->turma_codigo);
}
}
