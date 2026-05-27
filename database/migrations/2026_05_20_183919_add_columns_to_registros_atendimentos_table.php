<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registros_atendimentos', function (Blueprint $table) {
            $table->string('status', 100)->after('id');
            
            $table->dateTime('data_registro')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('registros_atendimentos', function (Blueprint $table) {
            $table->dropColumn(['status', 'data_registro']);
        });
    }
};