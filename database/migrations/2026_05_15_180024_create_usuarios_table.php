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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id(); // int(11) AUTO_INCREMENT PRIMARY KEY
            $table->string('nome', 100);
            $table->string('email', 100)->unique();
            $table->string('senha', 255); // No Laravel vamos salvar a Hash aqui
            $table->enum('tipo', ['estudante', 'psicologa']);
            $table->string('matricula', 20)->unique();
            $table->date('data_nascimento')->nullable();
            $table->string('cidade', 100)->nullable();
            $table->timestamps(); // Cria as colunas created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
