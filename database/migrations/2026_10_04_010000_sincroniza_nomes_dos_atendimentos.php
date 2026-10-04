<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Mesma trava usada pelas reservas e pela atualização no login SUAP.
            $trava = DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first();
            if (!$trava) {
                throw new \RuntimeException('Aplique primeiro a migration de controle de operações da agenda.');
            }

            DB::table('usuarios')->select('id', 'matricula', 'nome')
                ->whereIn('tipo', ['estudante', 'aluno'])
                ->whereNotNull('matricula')->whereNotNull('nome')
                ->orderBy('id')->chunkById(200, function ($alunos) {
                    foreach ($alunos as $aluno) {
                        // Não substitui um nome preservado por cadastro incompleto.
                        if (trim($aluno->nome) === '' || trim($aluno->matricula) === '') {
                            continue;
                        }
                        foreach (['horarios', 'registros_atendimentos'] as $tabela) {
                            DB::table($tabela)->where('matricula', $aluno->matricula)
                                ->update(['nome' => $aluno->nome]);
                        }
                    }
                });
        });
    }

    public function down(): void
    {
        // Correção de dados: não é possível reconstruir com segurança os nomes antigos.
        // O rollback não desfaz os nomes corrigidos nem remove registros.
    }
};
