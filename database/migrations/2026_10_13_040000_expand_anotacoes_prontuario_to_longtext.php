<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Apenas amplia a coluna. Os valores criptografados são preservados sem regravação.
        Schema::table('prontuario_sessoes', function (Blueprint $table) {
            $table->longText('anotacoes')->change();
        });
    }

    public function down(): void
    {
        // Impede uma redução que possa perder/corromper conteúdo criptografado já salvo.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)
            && DB::table('prontuario_sessoes')->whereRaw('LENGTH(anotacoes) > 65535')->exists()) {
            throw new RuntimeException('Há anotações maiores que TEXT. Mantenha LONGTEXT para preservar os registros.');
        }
        Schema::table('prontuario_sessoes', function (Blueprint $table) {
            $table->text('anotacoes')->change();
        });
    }
};
