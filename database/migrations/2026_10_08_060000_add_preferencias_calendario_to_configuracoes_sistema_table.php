<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->string('calendario_visualizacao', 20)->default('padrao');
            $table->boolean('calendario_faixa_ativa')->default(false);
            $table->char('calendario_hora_inicio', 5)->default('08:00');
            $table->char('calendario_hora_fim', 5)->default('18:00');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['calendario_visualizacao', 'calendario_faixa_ativa',
                'calendario_hora_inicio', 'calendario_hora_fim']);
        });
    }
};
