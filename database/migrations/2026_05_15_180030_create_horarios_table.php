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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->time('hora');
            $table->integer('disponivel')->default(1);
            $table->string('nome', 100)->nullable();
            $table->string('matricula', 20)->nullable();
            $table->boolean('confirmado')->default(false);
            $table->text('justificativa_cancelamento')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
