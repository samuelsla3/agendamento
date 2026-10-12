<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens_conversas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->unique()->constrained('usuarios')->restrictOnDelete();
            $table->unsignedBigInteger('lida_aluno_ate')->default(0);
            $table->unsignedBigInteger('lida_psicologa_ate')->default(0);
            $table->timestamp('ultima_mensagem_em')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('mensagens_internas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained('mensagens_conversas')->cascadeOnDelete();
            $table->foreignId('remetente_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('origem', 12); // aluno ou psicologa; definido pelo servidor.
            $table->char('chave_envio', 64)->unique();
            $table->char('resumo_hash', 64);
            $table->longText('conteudo'); // Texto criptografado.
            $table->timestamps();
            $table->index(['conversa_id', 'id']);
        });
        Schema::create('mensagens_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensagem_id')->constrained('mensagens_internas')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->longText('nome'); // Nome original sanitizado e criptografado.
            $table->string('mime', 100);
            $table->unsignedInteger('tamanho');
            $table->string('arquivo', 50)->unique(); // Nome opaco; não é uma URL pública.
            $table->timestamps();
        });
        DB::table('travas_operacoes')->insertOrIgnore(['nome' => 'mensagens']);
    }

    public function down(): void
    {
        if (Schema::hasTable('mensagens_internas') && DB::table('mensagens_internas')->exists()) {
            throw new \RuntimeException('Há mensagens e documentos registrados. Planeje a reversão com backup do banco, da pasta privada e da APP_KEY; eles não serão apagados automaticamente.');
        }
        Schema::dropIfExists('mensagens_anexos');
        Schema::dropIfExists('mensagens_internas');
        Schema::dropIfExists('mensagens_conversas');
        DB::table('travas_operacoes')->where('nome', 'mensagens')->delete();
    }
};
