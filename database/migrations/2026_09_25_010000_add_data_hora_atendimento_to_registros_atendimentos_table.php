<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registros_atendimentos', function (Blueprint $table) {
            // Nulos nos registros antigos: não presumir a data de uma vaga reutilizada.
            $table->date('data_atendimento')->nullable();
            $table->time('hora_atendimento')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('registros_atendimentos', function (Blueprint $table) {
            $table->dropColumn(['data_atendimento', 'hora_atendimento']);
        });
    }
};
