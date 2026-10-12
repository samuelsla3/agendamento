<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sugestoes_alunos', function (Blueprint $table) {
            $table->id();
            $table->char('chave_envio', 64)->unique();
            $table->longText('conteudo'); // Texto criptografado com APP_KEY.
            $table->date('data_envio'); // Sem horário exato ou identificação do autor.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sugestoes_alunos');
    }
};
