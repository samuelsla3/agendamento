<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoSistema extends Model
{
    protected $table = 'configuracoes_sistema';
    protected $guarded = ['*'];
    protected $casts = [
        'versao' => 'integer',
        'aviso_ativo' => 'boolean',
    ];

    public static function avisoPublico(): ?string
    {
        // As páginas continuam abrindo durante a instalação da migration.
        if (!Schema::hasTable('configuracoes_sistema')
            || !Schema::hasColumns('configuracoes_sistema', ['aviso_ativo', 'aviso_texto'])) {
            return null;
        }

        $configuracao = static::query()->whereKey(1)
            ->first(['id', 'aviso_ativo', 'aviso_texto']);

        if (!$configuracao?->aviso_ativo) {
            return null;
        }

        $texto = trim((string) $configuracao->aviso_texto);

        return $texto !== '' ? $texto : null;
    }

    public static function horarioLembretes(): string
    {
        // Conserva o horário anterior durante a instalação da migration.
        if (!Schema::hasTable('configuracoes_sistema')) {
            return '09:00';
        }

        return static::query()->whereKey(1)->value('horario_lembretes') ?? '09:00';
    }
}