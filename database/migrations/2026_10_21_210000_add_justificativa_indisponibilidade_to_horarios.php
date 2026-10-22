<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->text('justificativa_indisponibilidade')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('horarios', fn (Blueprint $table) => $table->dropColumn('justificativa_indisponibilidade'));
    }
};
