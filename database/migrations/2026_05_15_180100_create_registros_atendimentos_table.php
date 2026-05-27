<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registros_atendimentos', function (Blueprint $table) {
            $table->id();
            $table->integer('id_horario_original');
            $table->string('matricula_aluno', 20);
            $table->string('nome_aluno', 100);
            $table->date('data_atendimento');
            $table->time('hora_atendimento');
            $table->enum('status', ['Realizado', 'Cancelado pelo Aluno', 'Cancelado pela Psicóloga']);
            $table->text('observacao')->nullable();
            $table->timestamp('data_registro')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_atendimentos');
    }
};
