<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Controle de convite separado: não contém id da resposta nem data de envio.
        Schema::create('pesquisa_participacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('usuario_id')->primary();
            $table->string('estado', 20)->default('convidado');
        });
        // Respostas sem identificação, endereço IP, horário ou sequência de inserção.
        Schema::create('pesquisa_respostas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            foreach (range(1, 5) as $numero) $table->string('p'.$numero, 30);
            $table->unsignedTinyInteger('estrelas');
            $table->text('sugestao')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('pesquisa_respostas');
        Schema::dropIfExists('pesquisa_participacoes');
    }
};
