<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('psicologa_conta_teste')->default(false);
            $table->boolean('psicologa_senha_local_definida')->default(false);
            $table->string('senha_prontuario', 255)->nullable();
            $table->timestamp('psicologa_configurada_em')->nullable();
            $table->unsignedInteger('psicologa_autenticacao_versao')->default(1);
            $table->unsignedInteger('psicologa_prontuario_versao')->default(1);
        });
        // Preserva o cadastro, e-mail e hash atuais. Contas anteriores são de teste
        // e só entram com liberação explícita em ambiente local/testing.
        DB::table('usuarios')->where('tipo', 'psicologa')->update([
            'psicologa_conta_teste' => true, 'psicologa_senha_local_definida' => true,
        ]);
    }

    public function down(): void
    {
        if (DB::table('usuarios')->whereNotNull('senha_prontuario')->exists()) {
            throw new \RuntimeException('Não é possível remover automaticamente senhas de prontuário já cadastradas. Planeje a reversão com backup e código compatível.');
        }
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['psicologa_conta_teste', 'psicologa_senha_local_definida',
                'senha_prontuario', 'psicologa_configurada_em',
                'psicologa_autenticacao_versao', 'psicologa_prontuario_versao']);
        });
    }
};
