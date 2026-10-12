<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('email_escolhido')->default(false);
            $table->timestamp('email_confirmado_em')->nullable();
            $table->char('email_confirmado_hash', 64)->nullable();
            $table->text('email_pendente')->nullable(); // Cast encrypted no model.
            $table->char('email_codigo_hash', 64)->nullable();
            $table->timestamp('email_codigo_expira_em')->nullable();
            $table->timestamp('email_codigo_criado_em')->nullable();
            $table->unsignedSmallInteger('email_codigo_tentativas')->default(0);
            $table->unsignedInteger('email_codigo_versao')->default(0);
            $table->unsignedBigInteger('email_verificacao_aviso_id')->nullable();
        });
        // Não marca endereços importados/gerados como confirmados sem prova de acesso.
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['email_escolhido', 'email_confirmado_em', 'email_confirmado_hash',
                'email_pendente', 'email_codigo_hash', 'email_codigo_expira_em', 'email_codigo_criado_em',
                'email_codigo_tentativas', 'email_codigo_versao', 'email_verificacao_aviso_id']);
        });
        // O endereço escolhido em usuarios.email é preservado.
    }
};
