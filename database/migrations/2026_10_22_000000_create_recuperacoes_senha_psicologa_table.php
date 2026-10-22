<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recuperacoes_senha_psicologa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->string('tipo', 20);
            $table->char('token_hash', 64);
            $table->char('email_hash', 64);
            $table->unsignedInteger('versao_acesso');
            $table->unsignedInteger('versao_prontuario');
            $table->timestamp('expira_em');
            $table->timestamp('usado_em')->nullable();
            $table->timestamp('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('recuperacoes_senha_psicologa');
    }
};
