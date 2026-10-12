<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos_email', function (Blueprint $table) {
            $table->string('tipo', 30)->default('legado')->index();
            $table->longText('contexto')->nullable(); // Identificação mínima criptografada.
            $table->unsignedInteger('tentativas')->default(0);
            $table->uuid('tentativa_id')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->string('motivo_codigo', 40)->nullable();
            $table->unsignedBigInteger('solicitado_por')->nullable();
            $table->unsignedInteger('revisao')->default(0);
        });
        DB::table('travas_operacoes')->insertOrIgnore(['nome' => 'emails']);
    }

    public function down(): void
    {
        Schema::table('avisos_email', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropColumn(['tipo', 'contexto', 'tentativas', 'tentativa_id',
                'iniciado_em', 'motivo_codigo', 'solicitado_por', 'revisao']);
        });
        DB::table('travas_operacoes')->where('nome', 'emails')->delete();
    }
};
