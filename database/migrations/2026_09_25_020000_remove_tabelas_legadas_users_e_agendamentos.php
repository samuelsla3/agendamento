<?php

use App\Models\Usuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Execute em manutenção, sem processos antigos escrevendo no banco.
        // No MySQL, DROP TABLE não é revertido por uma transação comum.
        $provider = config('auth.guards.web.provider');
        if (config("auth.providers.{$provider}.driver") !== 'eloquent'
            || config("auth.providers.{$provider}.model") !== Usuario::class) {
            throw new RuntimeException('Limpeza interrompida: confirme que a autenticação web usa App\\Models\\Usuario.');
        }

        $tabelas = ['agendamentos', 'users'];

        // Valida AMBAS antes de apagar qualquer uma. Não descarta registros.
        foreach ($tabelas as $tabela) {
            if (Schema::hasTable($tabela) && DB::table($tabela)->exists()) {
                throw new RuntimeException("Limpeza interrompida: a tabela {$tabela} contém registros. Nenhuma tabela foi removida por esta execução.");
            }
        }

        // Procura dependências declaradas nas outras tabelas do banco.
        $prefixo = DB::connection()->getTablePrefix();
        $alvos = array_map(fn ($tabela) => $prefixo . $tabela, $tabelas);
        foreach (Schema::getTableListing(schemaQualified: false) as $nomeFisico) {
            $nome = $prefixo !== '' && str_starts_with($nomeFisico, $prefixo)
                ? substr($nomeFisico, strlen($prefixo))
                : $nomeFisico;

            foreach (Schema::getForeignKeys($nome) as $chave) {
                if (in_array($chave['foreign_table'], $alvos, true)) {
                    throw new RuntimeException("Limpeza interrompida: {$nomeFisico} possui uma chave estrangeira para {$chave['foreign_table']}. Nenhuma tabela foi removida por esta execução.");
                }
            }
        }

        // As tabelas funcionais, de sessões, filas e recuperação ficam intactas.
        Schema::dropIfExists('agendamentos');
        Schema::dropIfExists('users');
    }

    public function down(): void
    {
        // Recria somente as estruturas originais. Não restaura dados de backup.
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('agendamentos')) {
            Schema::create('agendamentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_horario')->nullable()->constrained('horarios')->onDelete('cascade');
                $table->string('nome', 100)->nullable();
                $table->text('observacao')->nullable();
                $table->timestamps();
            });
        }
    }
};
