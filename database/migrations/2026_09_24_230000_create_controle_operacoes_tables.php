<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Compatível com a migration de token enviada anteriormente.
        if (!Schema::hasColumn('horarios', 'token_cancelamento')) {
            Schema::table('horarios', function (Blueprint $table) {
                $table->string('token_cancelamento', 64)->nullable()->unique();
            });
        }
        DB::table('horarios')->where('disponivel', 0)
            ->where(function ($q) { $q->where('confirmado', 0)->orWhereNull('confirmado'); })
            ->whereNull('token_cancelamento')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('horarios')->where('id', $row->id)->whereNull('token_cancelamento')
                        ->update(['token_cancelamento' => Str::random(64)]);
                }
            });
        Schema::create('travas_operacoes', function (Blueprint $table) {
            $table->string('nome', 40)->primary();
        });
        DB::table('travas_operacoes')->insert([['nome' => 'agenda'], ['nome' => 'prontuario']]);
        Schema::create('operacoes_http', function (Blueprint $table) {
            $table->string('chave', 64)->primary();
            $table->string('assinatura', 64);
            $table->text('resposta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        Schema::create('avisos_email', function (Blueprint $table) {
            $table->id();
            $table->string('evento', 64)->unique();
            $table->longText('conteudo'); // Snapshot criptografado, sem senha ou prontuário.
            $table->string('estado', 20)->default('pendente')->index();
            $table->timestamp('enviado_em')->nullable();
            $table->string('erro_tipo')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('avisos_email');
        Schema::dropIfExists('operacoes_http');
        Schema::dropIfExists('travas_operacoes');
        // Preserva token_cancelamento: pode pertencer à migration anterior.
    }
};
