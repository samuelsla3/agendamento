<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->string('token_cancelamento', 64)
                ->nullable()
                ->unique();
        });

        // Cria tokens para os agendamentos ativos já existentes.
        DB::table('horarios')
            ->where('disponivel', 0)
            ->where(function ($query) {
                $query->where('confirmado', 0)
                    ->orWhereNull('confirmado');
            })
            ->orderBy('id')
            ->chunkById(100, function ($horarios) {
                foreach ($horarios as $horario) {
                    DB::table('horarios')
                        ->where('id', $horario->id)
                        ->whereNull('token_cancelamento')
                        ->update([
                            'token_cancelamento' => Str::random(64),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropUnique(['token_cancelamento']);
            $table->dropColumn('token_cancelamento');
        });
    }
};