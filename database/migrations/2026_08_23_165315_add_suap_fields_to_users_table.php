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
        Schema::table('usuarios', function (Blueprint $table) {
            // Adiciona o campo matricula como único para identificar os alunos do SUAP
            if (!Schema::hasColumn('usuarios', 'matricula')) {
                $table->string('matricula')->unique()->after('id');
            }

            // Adiciona a coluna para a senha em Hash (usada no Fallback Local)
            if (!Schema::hasColumn('usuarios', 'password')) {
                $table->string('password')->after('matricula');
            }

            // Garante que a coluna email existe no banco
            if (!Schema::hasColumn('usuarios', 'email')) {
                $table->string('email')->nullable()->after('nome');
            }

            // Garante a coluna do tipo de usuário (estudante/servidor)
            if (!Schema::hasColumn('usuarios', 'tipo')) {
                $table->string('tipo')->default('estudante')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['matricula', 'password', 'email', 'tipo']);
        });
    }
};