<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->text('atendimento_resumo')->nullable();
            $table->string('atendimento_local', 255)->nullable();
            $table->text('atendimento_orientacoes')->nullable();
            $table->string('atendimento_email', 100)->nullable();
            $table->string('atendimento_telefone', 50)->nullable();
            $table->text('atendimento_outros_contatos')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'atendimento_resumo', 'atendimento_local', 'atendimento_orientacoes',
                'atendimento_email', 'atendimento_telefone', 'atendimento_outros_contatos',
            ]);
        });
    }
};
