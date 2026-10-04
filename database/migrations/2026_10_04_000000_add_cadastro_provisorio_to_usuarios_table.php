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
            // Evita inventar um endereço para alunos ainda sem contato cadastrado.
            $table->string('email', 100)->nullable()->change();
            $table->boolean('cadastro_provisorio')->default(false);
        });
    }

    public function down(): void
    {
        // Não descarta a proteção de acesso nem inventa e-mails durante uma reversão.
        if (DB::table('usuarios')->whereNull('email')->exists()
            || DB::table('usuarios')->where('cadastro_provisorio', true)->exists()) {
            throw new \RuntimeException('Não é possível reverter enquanto houver cadastros provisórios ou sem e-mail.');
        }
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('email', 100)->nullable(false)->change();
            $table->dropColumn('cadastro_provisorio');
        });
    }
};
