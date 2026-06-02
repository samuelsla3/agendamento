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
            $table->string('nome', 255);
            $table->string('matricula', 20);
            $table->string('status', 100);
            $table->text('observacao')->nullable();
            $table->dateTime('data_registro')->useCurrent();
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
