<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->boolean('antecedencia_agendamento_ativa')->default(false);
            $table->unsignedInteger('antecedencia_agendamento_minutos')->default(120);
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn(['antecedencia_agendamento_ativa', 'antecedencia_agendamento_minutos']);
        });
    }
};
