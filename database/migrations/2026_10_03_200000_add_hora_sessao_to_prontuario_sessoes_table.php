<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prontuario_sessoes', function (Blueprint $table) {
            // Registros antigos ficam sem horário; created_at não é a hora da sessão.
            $table->time('hora_sessao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prontuario_sessoes', function (Blueprint $table) {
            $table->dropColumn('hora_sessao');
        });
    }
};
