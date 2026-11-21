<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensagens_internas', function (Blueprint $table) {
            $table->timestamp('oculta_aluno_em')->nullable();
            $table->timestamp('oculta_psicologa_em')->nullable();
            $table->timestamp('apagada_todos_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mensagens_internas', function (Blueprint $table) {
            $table->dropColumn(['oculta_aluno_em', 'oculta_psicologa_em', 'apagada_todos_em']);
        });
    }
};
