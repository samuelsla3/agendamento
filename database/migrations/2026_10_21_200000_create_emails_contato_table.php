<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails_contato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained('mensagens_conversas');
            $table->foreignId('remetente_id')->constrained('usuarios');
            $table->char('chave_envio', 64)->unique();
            $table->char('resumo_hash', 64);
            $table->string('estado', 20);
            $table->unsignedBigInteger('anexos_bytes')->default(0);
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();
            $table->index(['remetente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails_contato');
    }
};
