<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prontuario_sessoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('horario_id')->nullable()->constrained('horarios')->onDelete('set null');
            
            $table->text('anotacoes'); 
            
            $table->date('data_sessao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prontuario_sessoes');
    }
};