<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class LimpezaLegadoTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Use phpunit-operacoes.xml com SQLite em memória e limpe o cache de configuração.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $this->withoutVite();
    }

    private function limpeza(): object
    {
        return require database_path('migrations/2026_09_25_020000_remove_tabelas_legadas_users_e_agendamentos.php');
    }

    private function usuario(): Usuario
    {
        return Usuario::forceCreate([
            'nome' => 'Profissional de Teste', 'matricula' => '99999999999',
            'email' => 'psicologa@example.test', 'tipo' => 'psicologa',
            'senha' => Hash::make('senha-teste'), 'password' => Hash::make('senha-teste'),
        ]);
    }

    private function conferirInterrupcao(string $trecho): void
    {
        try {
            $this->limpeza()->up();
            $this->fail('A migration deveria interromper a limpeza.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString($trecho, $e->getMessage());
        }

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('agendamentos'));
    }

    public function test_remove_somente_tabelas_vazias_e_preserva_dados_funcionais(): void
    {
        $this->usuario();
        Horario::create(['data' => now()->addDays(2)->toDateString(), 'hora' => '09:00:00', 'disponivel' => 1]);
        DB::table('jobs')->insert([
            'queue' => 'teste', 'payload' => '{}', 'attempts' => 0,
            'reserved_at' => null, 'available_at' => time(), 'created_at' => time(),
        ]);

        // Simula atualização de um banco existente e também o rollback estrutural.
        $migration = $this->limpeza();
        $migration->down();
        $this->assertTrue(Schema::hasColumns('users', ['name', 'email', 'password', 'remember_token']));
        $this->assertTrue(Schema::hasColumns('agendamentos', ['id_horario', 'nome', 'observacao']));

        $antes = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $tabela) {
            if (!in_array($tabela, ['users', 'agendamentos'], true)) {
                $antes[$tabela] = DB::table($tabela)->get()->toJson();
            }
        }

        $migration->up();
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('agendamentos'));
        foreach ($antes as $tabela => $dados) {
            $this->assertTrue(Schema::hasTable($tabela), $tabela);
            $this->assertSame($dados, DB::table($tabela)->get()->toJson(), $tabela);
        }
    }

    public function test_users_com_registro_impede_remocao_de_ambas(): void
    {
        $this->limpeza()->down();
        DB::table('users')->insert(['name' => 'Legado', 'email' => 'legado@example.test', 'password' => 'hash-de-teste']);
        $this->conferirInterrupcao('users contém registros');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_agendamentos_com_registro_impede_remocao_de_ambas(): void
    {
        $this->limpeza()->down();
        DB::table('agendamentos')->insert(['nome' => 'Reserva legada']);
        $this->conferirInterrupcao('agendamentos contém registros');
        $this->assertDatabaseCount('agendamentos', 1);
    }

    public function test_chave_estrangeira_para_users_impede_limpeza(): void
    {
        $this->limpeza()->down();
        Schema::create('dependencia_legada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
        });
        $this->conferirInterrupcao('chave estrangeira para users');
    }

    public function test_chave_estrangeira_para_agendamentos_impede_limpeza(): void
    {
        $this->limpeza()->down();
        Schema::create('dependencia_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos');
        });
        $this->conferirInterrupcao('chave estrangeira para agendamentos');
    }

    public function test_autenticacao_diferente_de_usuario_impede_limpeza(): void
    {
        $this->limpeza()->down();
        $provider = config('auth.guards.web.provider');
        config(["auth.providers.{$provider}.model" => Horario::class]);
        $this->conferirInterrupcao('autenticação web usa');
    }

    public function test_relatorio_funciona_sem_partial_e_sem_tabelas_legadas(): void
    {
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('agendamentos'));
        $this->assertFalse(view()->exists('partials.tabela_relatorio'));

        DB::table('registros_atendimentos')->insert([
            ['id_horario_original' => 1, 'nome' => 'Aluno Alfa', 'matricula' => '11111111111',
                'status' => 'Realizado', 'data_registro' => '2026-09-25 09:00:00',
                'data_atendimento' => '2026-09-25', 'hora_atendimento' => '09:00:00'],
            ['id_horario_original' => 2, 'nome' => 'Aluno Beta', 'matricula' => '22222222222',
                'status' => 'Cancelado pelo Aluno', 'data_registro' => '2026-09-24 17:35:00',
                'data_atendimento' => '2026-09-26', 'hora_atendimento' => '14:00:00'],
        ]);

        $this->actingAs($this->usuario())->withSession(['usuario_tipo' => 'psicologa']);
        $this->post('/agenda/relatorio', ['ordenar_por' => 'nome_asc'])
            ->assertOk()->assertSee('Total de registros encontrados: 2')
            ->assertSeeInOrder(['Aluno Alfa', 'Aluno Beta'])->assertSee('Atendimento Realizado')
            ->assertSee('Cancelado pelo Aluno')->assertSee('26/09/2026 às 14:00')
            ->assertDontSee('24/09/2026 às 17:35');
        $this->post('/agenda/relatorio', ['aluno_matricula' => '11111111111'])
            ->assertOk()->assertSee('Aluno Alfa')->assertDontSee('Aluno Beta');
        $this->post('/agenda/relatorio', ['data_inicio' => '2026-09-26', 'data_fim' => '2026-09-26'])
            ->assertOk()->assertSee('Aluno Beta')->assertDontSee('Aluno Alfa');
        $this->post('/agenda/relatorio', ['aluno_nome' => 'Inexistente'])
            ->assertOk()->assertSee('Nenhum agendamento encontrado');

        // A ordem de cancelamento é diferente da ordem dos horários marcados.
        $this->post('/agenda/relatorio', ['ordenar_por' => 'data_asc'])
            ->assertOk()->assertSeeInOrder(['Aluno Alfa', 'Aluno Beta']);
        $this->post('/agenda/relatorio', ['ordenar_por' => 'data_desc'])
            ->assertOk()->assertSeeInOrder(['Aluno Beta', 'Aluno Alfa']);

        DB::table('registros_atendimentos')->insert([
            'id_horario_original' => 3, 'nome' => 'Aluno Legado', 'matricula' => '33333333333',
            'status' => 'Cancelado pelo Aluno', 'data_registro' => '2026-09-26 18:00:00',
        ]);
        $this->post('/agenda/relatorio', ['ordenar_por' => 'data_asc'])
            ->assertOk()->assertSeeInOrder(['Aluno Alfa', 'Aluno Beta', 'Aluno Legado'])
            ->assertSee('Data/hora não preservadas neste registro antigo');
        $this->post('/agenda/relatorio', ['ordenar_por' => 'data_desc'])
            ->assertOk()->assertSeeInOrder(['Aluno Beta', 'Aluno Alfa', 'Aluno Legado']);
        $this->post('/agenda/relatorio', ['data_inicio' => '2026-09-26', 'data_fim' => '2026-09-26'])
            ->assertOk()->assertSee('Aluno Beta')->assertDontSee('Aluno Legado');
    }
}
