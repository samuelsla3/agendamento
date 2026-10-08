<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';
    protected $guarded = ['*'];
    protected $casts = ['versao' => 'integer'];

    public static function horarioLembretes(): string
    {
        // Conserva o horário anterior durante a instalação da migration.
        if (!Schema::hasTable('configuracoes_sistema')) {
            return '09:00';
        }

        return static::query()->whereKey(1)->value('horario_lembretes') ?? '09:00';
    }
}