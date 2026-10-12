<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('situacao_final', 100)->nullable();
            $table->unsignedBigInteger('registro_encerramento_id')->nullable()->index();
            $table->unsignedInteger('revisao_encerramento')->default(0);
        });

        // Query builder: não altera updated_at nem os registros históricos antigos.
        DB::table('horarios')->where('confirmado', 1)->update(['situacao_final' => 'Realizado']);

        Schema::create('correcoes_atendimentos', function (Blueprint $table) {
            $table->id();
            // IDs antigos de horários/registros podem ser inteiros; não cria FKs incompatíveis.
            $table->unsignedBigInteger('horario_id')->index();
            $table->unsignedBigInteger('registro_atendimento_id')->index();
            $table->unsignedBigInteger('psicologa_id');
            $table->string('situacao_anterior', 100);
            $table->string('situacao_nova', 100);
            $table->text('observacao_anterior')->nullable();
            $table->dateTime('corrigido_em');
        });
    }

    public function down(): void
    {
        if (DB::table('registros_atendimentos')->where('status', 'Não compareceu')->exists()
            || DB::table('horarios')->where('situacao_final', 'Não compareceu')->exists()
            || DB::table('correcoes_atendimentos')->exists()) {
            throw new RuntimeException('Não é possível remover esta migration enquanto houver faltas ou correções registradas.');
        }
        Schema::dropIfExists('correcoes_atendimentos');
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex(['registro_encerramento_id']);
            $table->dropColumn(['situacao_final', 'registro_encerramento_id', 'revisao_encerramento']);
        });
    }
};
