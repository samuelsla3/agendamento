<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RelatorioSituacaoTest extends TestCase
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
        $usuario = Usuario::forceCreate([
            'nome' => 'Profissional de Teste', 'matricula' => '99999999999',
            'email' => 'psicologa@example.test', 'tipo' => 'psicologa',
            'senha' => Hash::make('teste'), 'password' => Hash::make('teste'),
        ]);
        $this->actingAs($usuario)->withSession(['usuario_tipo' => 'psicologa']);
        foreach ([
            ['Aluno Alfa', '11111111111', 'Realizado', '2026-09-01'],
            ['Aluno Alfa', '11111111111', 'Realizado', '2026-09-02'],
            ['Aluno Beta', '22222222222', 'Realizado', '2026-09-03'],
            ['Aluno Cancelado', '33333333333', 'Cancelado pelo Aluno', '2026-09-04'],
            ['Aluno Remarcado', '44444444444', 'Cancelado pela Psicóloga', '2026-09-05'],
            ['Aluno Antigo', '55555555555', 'Realizado', null],
            ['Aluno Futuro', '66666666666', 'Agendado', '2026-10-01'],
        ] as $i => [$nome, $matricula, $status, $data]) {
            DB::table('registros_atendimentos')->insert([
                'id_horario_original' => $i + 1, 'nome' => $nome, 'matricula' => $matricula,
                'status' => $status, 'data_registro' => '2026-09-20 12:00:00',
                'data_atendimento' => $data, 'hora_atendimento' => $data ? '09:00:00' : null,
            ]);
        }
    }

    public function test_padrao_conta_atendimentos_e_alunos_distintos_sem_cancelados_ou_pendentes(): void
    {
        $this->post('/agenda/relatorio')->assertOk()
            ->assertSee('Atendimentos realizados: 4 · Alunos atendidos: 3')
            ->assertSee('Total de registros encontrados: 4')
            ->assertSee('Período: todos os registros')
            ->assertSee('Aluno Antigo')->assertDontSee('Aluno Cancelado')
            ->assertDontSee('Aluno Remarcado')->assertDontSee('Aluno Futuro')
            ->assertDontSee('N/A');
    }

    public function test_todos_preserva_tabela_completa_mas_conta_apenas_realizados(): void
    {
        $this->post('/agenda/relatorio', ['situacao' => 'todos'])->assertOk()
            ->assertSee('Total de registros encontrados: 7')
            ->assertSee('Atendimentos realizados: 4 · Alunos atendidos: 3')
            ->assertSee('Aluno Cancelado')->assertSee('Aluno Remarcado')->assertSee('Aluno Futuro');
    }

    public function test_cada_tipo_de_cancelamento_tem_indicadores_zerados(): void
    {
        foreach (['cancelados_aluno' => 'Aluno Cancelado', 'cancelados_psicologa' => 'Aluno Remarcado'] as $filtro => $aluno) {
            $this->post('/agenda/relatorio', ['situacao' => $filtro])->assertOk()
                ->assertSee('Total de registros encontrados: 1')->assertSee($aluno)
                ->assertDontSee('Aluno Alfa')
                ->assertSee('Atendimentos realizados: 0 · Alunos atendidos: 0');
        }
        $this->post('/agenda/relatorio', ['situacao' => 'cancelados'])->assertOk()
            ->assertSee('Total de registros encontrados: 2')
            ->assertSee('Aluno Cancelado')->assertSee('Aluno Remarcado')
            ->assertSee('Atendimentos realizados: 0 · Alunos atendidos: 0');
    }

    public function test_filtros_combinados_e_datas_inclusivas_usam_a_data_marcada(): void
    {
        $this->post('/agenda/relatorio', [
            'aluno_nome' => 'Alfa', 'aluno_matricula' => '11111111111',
            'data_inicio' => '2026-09-01', 'data_fim' => '2026-09-02',
        ])->assertOk()->assertSee('Atendimentos realizados: 2 · Alunos atendidos: 1')
            ->assertSee('Período selecionado: 01/09/2026 a 02/09/2026')
            ->assertDontSee('Aluno Antigo')->assertDontSee('Aluno Beta');
        $this->post('/agenda/relatorio', ['data_inicio' => '2026-09-02'])->assertOk()
            ->assertSee('Atendimentos realizados: 2 · Alunos atendidos: 2')
            ->assertSee('a partir de 02/09/2026')->assertDontSee('Aluno Antigo');
        $this->post('/agenda/relatorio', ['data_fim' => '2026-09-01'])->assertOk()
            ->assertSee('Atendimentos realizados: 1 · Alunos atendidos: 1')
            ->assertSee('até 01/09/2026')->assertDontSee('Aluno Antigo');
    }

    public function test_sem_resultados_mantem_indicadores_e_contexto_para_pdf(): void
    {
        $this->post('/agenda/relatorio', ['aluno_nome' => 'Inexistente'])->assertOk()
            ->assertSee('Atendimentos realizados: 0 · Alunos atendidos: 0')
            ->assertSee('Total de registros encontrados: 0')
            ->assertSee('id="resumo-relatorio"', false)->assertSee('id="sem-resultados"', false)
            ->assertDontSee('id="tabela-relatorio"', false);
    }

    public function test_rejeita_situacao_e_periodo_invalidos(): void
    {
        $this->postJson('/agenda/relatorio', ['situacao' => 'invalida'])
            ->assertUnprocessable()->assertJsonValidationErrors('situacao');
        $this->postJson('/agenda/relatorio', ['data_inicio' => '2026-09-10', 'data_fim' => '2026-09-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('data_fim');
    }

    public function test_escapa_dados_na_tabela_e_no_resumo(): void
    {
        DB::table('registros_atendimentos')->where('matricula', '11111111111')
            ->update(['nome' => '<script>alert(1)</script>']);
        $this->post('/agenda/relatorio', ['aluno_nome' => '<script>'])->assertOk()
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>', false);
    }

    public function test_aluno_nao_pode_acessar_o_relatorio(): void
    {
        // Verifica também a defesa do controller independentemente do middleware.
        $this->withoutMiddleware();
        auth()->user()->forceFill(['tipo' => 'estudante'])->save();
        $this->withSession(['usuario_tipo' => 'estudante'])->postJson('/agenda/relatorio')->assertForbidden();
    }
}
