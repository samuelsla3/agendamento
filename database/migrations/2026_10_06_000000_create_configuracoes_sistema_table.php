<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_sistema', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->char('horario_lembretes', 5)->default('09:00');
            $table->unsignedInteger('versao')->default(1);
            $table->timestamps();
        });

        DB::table('configuracoes_sistema')->insert([
            'id' => 1, 'horario_lembretes' => '09:00', 'versao' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_sistema');
    }
};