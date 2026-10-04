<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\ProntuarioSessao;
use App\Models\Usuario;
use App\Services\AtendimentoEmergencial;
use App\Services\SuapService;
use App\Services\Suap\EmailPessoalService;
use App\Services\Suap\TurmaAlunoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AtendimentoEmergencialTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Execute somente com phpunit-emergencial.xml e SQLite em memória, após config:clear.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $this->withoutVite();
    }

    private function usuario(string $matricula, string $tipo = 'estudante'): Usuario
    {
        return Usuario::forceCreate([
            'nome' => 'Cadastro '.$matricula, 'matricula' => $matricula,
            'email' => $matricula.'@example.test', 'tipo' => $tipo,
            'turma_codigo' => '20261.3.18.1I',
            'senha' => Hash::make('senha-teste'), 'password' => Hash::make('senha-teste'),
        ]);
    }

    private function psicologa(): Usuario
    {
        $psi = $this->usuario('99999999999', 'psicologa');
        $this->actingAs($psi)->withSession(['usuario_tipo' => 'psicologa']);
        return $psi;
    }

    private function dados(array $alteracoes = []): array
    {
        return array_merge([
            'action' => 'atendimento_emergencial', '_operation_id' => (string) Str::uuid(),
            'data' => now()->toDateString(), 'hora' => '09:15',
            'matricula' => '00123456789', 'nome' => 'Aluno Novo de Teste',
            'turma_codigo' => '20261.3.18.1I', 'email' => '',
        ], $alteracoes);
    }

    public function test_cria_provisorio_sem_email_e_prontuario_vinculado_sem_expor_anotacoes(): void
    {
        $this->psicologa();
        $this->postJson('/agenda/acao', $this->dados())->assertOk()->assertJsonPath('status', 'success');
        $aluno = Usuario::where('matricula', '00123456789')->sole();
        $horario = Horario::sole();
        $this->assertTrue($aluno->cadastro_provisorio);
        $this->assertNull($aluno->email);
        $this->assertSame('3.18.1I', $aluno->turma_codigo);
        $this->assertSame($aluno->id, $horario->usuario->id);
        $this->assertSame(0, (int) $horario->disponivel);
        $this->assertSame(0, (int) $horario->confirmado);
        $this->assertDatabaseCount('registros_atendimentos', 0);
        $this->assertDatabaseCount('avisos_email', 0);
        $this->get('/prontuarios/aluno/'.$aluno->id)->assertRedirect('/agenda');
        $this->withSession(['prontuario_autorizado' => true])->post('/prontuarios/aluno/'.$aluno->id, [
            '_operation_id' => (string) Str::uuid(), 'horario_id' => $horario->id,
            'data_sessao' => now()->toDateString(), 'hora_sessao' => '09:15',
            'anotacoes' => 'Texto fictício exclusivo deste teste.',
        ])->assertRedirect();
        $sessao = ProntuarioSessao::sole();
        $this->assertSame($aluno->id, $sessao->aluno->id);
        $this->assertNotSame($sessao->anotacoes, $sessao->getRawOriginal('anotacoes'));
        $this->get('/agenda/eventos')->assertOk()->assertJsonPath('0.extendedProps.aluno_id', $aluno->id)
            ->assertDontSee('Texto fictício exclusivo deste teste.');
        $this->get('/prontuarios/aluno/'.$aluno->id)->assertOk()->assertSee('Não informado');
    }

    public function test_reutiliza_vaga_vazia_sem_sobrescrever_dados_do_aluno_e_repeticao_e_idempotente(): void
    {
        $this->psicologa();
        $aluno = $this->usuario('00123456789');
        $vaga = Horario::create(['data' => now()->toDateString(), 'hora' => '09:15:00',
            'disponivel' => 1, 'confirmado' => 0, 'justificativa_cancelamento' => 'Cancelamento anterior']);
        $dados = $this->dados(['aluno_id' => $aluno->id, 'nome' => 'Nome alterado', 'email' => 'outro@example.test']);
        $this->postJson('/agenda/acao', $dados)->assertOk();
        $this->postJson('/agenda/acao', $dados)->assertOk()->assertHeader('X-Operation-Replayed', 'true');
        $this->assertDatabaseCount('horarios', 1);
        $this->assertDatabaseCount('usuarios', 2);
        $this->assertDatabaseCount('avisos_email', 1);
        $this->assertSame('Cadastro 00123456789', $aluno->fresh()->nome);
        $this->assertSame($aluno->email, $aluno->fresh()->email);
        $this->assertSame($aluno->nome, $vaga->fresh()->nome);
        $this->assertNull($vaga->fresh()->justificativa_cancelamento);
        $this->assertFalse($aluno->fresh()->cadastro_provisorio);
    }

    public function test_conflitos_nao_deixam_cadastro_parcial_e_recusam_segundo_atendimento_no_dia(): void
    {
        $this->psicologa();
        $aluno = $this->usuario('22222222222');
        Horario::create(['data' => now()->toDateString(), 'hora' => '09:15:00',
            'disponivel' => 0, 'confirmado' => 0, 'nome' => $aluno->nome, 'matricula' => $aluno->matricula]);
        $this->postJson('/agenda/acao', $this->dados())->assertUnprocessable()->assertJsonValidationErrors('hora');
        $this->assertDatabaseMissing('usuarios', ['matricula' => '00123456789']);
        $this->postJson('/agenda/acao', $this->dados(['matricula' => $aluno->matricula, 'hora' => '10:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('data');
        $this->assertDatabaseCount('horarios', 1);
        $this->assertDatabaseCount('avisos_email', 0);
    }

    public function test_recusa_identidade_divergente_psicologa_e_dados_invalidos(): void
    {
        $psi = $this->psicologa();
        $aluno = $this->usuario('22222222222');
        foreach ([['aluno_id' => $aluno->id], ['matricula' => $psi->matricula],
            ['matricula' => 'texto'], ['turma_codigo' => '3'], ['email' => 'invalido'],
            ['nome' => ''], ['hora' => '25:00'], ['data' => '2026-02-31']] as $alteracoes) {
            $this->postJson('/agenda/acao', $this->dados($alteracoes))->assertUnprocessable();
        }
        $this->assertDatabaseCount('usuarios', 2);
        $this->assertDatabaseCount('horarios', 0);
    }

    public function test_aluno_nao_pode_criar_emergencial_nem_obter_lista_privada(): void
    {
        $aluno = $this->usuario('22222222222');
        $this->actingAs($aluno)->withSession(['usuario_tipo' => 'aluno']);
        $this->postJson('/agenda/acao', $this->dados())->assertRedirect('/login');
        $this->get('/agenda/eventos')->assertRedirect('/login');
        $this->assertDatabaseCount('horarios', 0);
        // Mesmo com sessão inconsistente, o controller confere o papel autenticado.
        $this->withSession(['usuario_tipo' => 'psicologa']);
        $this->postJson('/agenda/acao', $this->dados())->assertForbidden();
    }

    public function test_primeiro_login_suap_preserva_id_prontuario_e_visibilidade_por_matricula(): void
    {
        app(AtendimentoEmergencial::class)->registrar($this->dados());
        $aluno = Usuario::where('matricula', '00123456789')->sole();
        $horario = Horario::sole();
        DB::table('registros_atendimentos')->insert([
            'id_horario_original' => $horario->id, 'nome' => $aluno->nome, 'matricula' => $aluno->matricula,
            'status' => 'Cancelado pelo Aluno', 'data_registro' => now()->subDay(),
            'data_atendimento' => now()->subDay()->toDateString(), 'hora_atendimento' => '09:15:00',
        ]);
        $sessao = ProntuarioSessao::create(['aluno_id' => $aluno->id, 'horario_id' => $horario->id,
            'data_sessao' => now()->toDateString(), 'hora_sessao' => '09:15:00', 'anotacoes' => 'Teste de preservação.']);
        $this->mock(SuapService::class, function ($mock) {
            $mock->shouldReceive('autenticar')->once()->with('00123456789', 'senha-suap')->andReturn('token-teste');
            $mock->shouldReceive('meusDados')->once()->with('token-teste')
                ->andReturn(['vinculo' => ['situacao' => 'Matriculado', 'nome' => 'Nome Validado no SUAP']]);
        });
        $this->mock(EmailPessoalService::class, fn ($mock) => $mock->shouldReceive('resolver')->once()->andReturn('validado@example.test'));
        $this->mock(TurmaAlunoService::class, fn ($mock) => $mock->shouldReceive('resolver')->once()->andReturn('20261.3.18.1I'));
        $this->post('/login', ['matricula' => $aluno->matricula, 'senha' => 'senha-suap',
            '_operation_id' => (string) Str::uuid()])->assertRedirect('/');
        $this->assertAuthenticatedAs($aluno->fresh());
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertFalse($aluno->fresh()->cadastro_provisorio);
        $this->assertSame('Nome Validado no SUAP', $horario->fresh()->nome);
        $this->assertDatabaseHas('registros_atendimentos', [
            'matricula' => $aluno->matricula, 'nome' => 'Nome Validado no SUAP', 'status' => 'Cancelado pelo Aluno',
        ]);
        $this->assertSame($aluno->id, $sessao->fresh()->aluno_id);
        $this->assertSame('Teste de preservação.', $sessao->fresh()->anotacoes);
        $this->get('/')->assertOk()->assertViewHas('horarios', function ($eventos) {
            return $eventos[0]['extendedProps']['matricula_agendada'] === '00123456789';
        })->assertViewHas('registros', fn ($registros) => $registros->count() === 2);
        $this->get('/agenda/eventos')->assertRedirect('/login');
        $this->get('/prontuarios/aluno/'.$aluno->id)->assertRedirect('/login');
        $this->psicologa();
        $this->get('/agenda/eventos')->assertOk()->assertJsonPath('0.extendedProps.nome', 'Nome Validado no SUAP');
        $this->get('/agenda')->assertOk()->assertViewHas('agendamentosHoje', fn ($agendamentos) =>
            $agendamentos->first()->nome === 'Nome Validado no SUAP');
        $this->post('/agenda/relatorio', ['situacao' => 'todos', 'aluno_nome' => 'Validado no SUAP'])
            ->assertOk()->assertSee('Nome Validado no SUAP');
        $outro = $this->usuario('22222222222');
        $this->actingAs($outro)->withSession(['nome' => $outro->nome, 'tipo' => 'estudante',
            'usuario_tipo' => 'aluno', 'matricula' => $outro->matricula]);
        $this->get('/')->assertViewHas('horarios', fn ($eventos) =>
            $eventos[0]['extendedProps']['matricula_agendada'] === null
            && $eventos[0]['extendedProps']['nome_agendado'] === null);
    }

    public function test_provisorio_nao_entra_pelo_fallback_local_mesmo_com_hash_correspondente(): void
    {
        app(AtendimentoEmergencial::class)->registrar($this->dados());
        Usuario::where('matricula', '00123456789')->update(['senha' => Hash::make('senha-teste')]);
        $this->mock(SuapService::class, fn ($mock) => $mock->shouldReceive('autenticar')->once()->andReturn(null));
        $this->post('/login', ['matricula' => '00123456789', 'senha' => 'senha-teste',
            '_operation_id' => (string) Str::uuid()])->assertSessionHasErrors('matricula');
        $this->assertGuest();
    }

    public function test_conclusao_e_cancelamento_seguem_fluxo_normal(): void
    {
        $this->psicologa();
        $this->postJson('/agenda/acao', $this->dados())->assertOk();
        $horario = Horario::sole();
        $this->postJson('/agenda/acao', ['action' => 'confirmar', 'id' => $horario->id,
            'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid()])->assertOk();
        $this->assertDatabaseHas('registros_atendimentos', ['matricula' => '00123456789', 'status' => 'Realizado']);
        $this->assertSame(1, (int) $horario->fresh()->confirmado);
        $this->postJson('/agenda/acao', $this->dados(['data' => now()->addDay()->toDateString()]))->assertOk();
        $outro = Horario::orderByDesc('id')->first();
        $this->postJson('/agenda/acao', ['action' => 'cancel_by_psicologa', 'id' => $outro->id,
            'versao' => $outro->versao(), 'justificativa' => 'Teste de cancelamento.',
            '_operation_id' => (string) Str::uuid()])->assertOk();
        $this->assertDatabaseHas('registros_atendimentos', ['matricula' => '00123456789', 'status' => 'Cancelado pela Psicóloga']);
        $this->assertNull($outro->fresh()->matricula);
        $this->assertSame(1, (int) $outro->fresh()->disponivel);
    }

    public function test_migration_corrige_nomes_ja_desatualizados_sem_misturar_matriculas_ou_alterar_outros_dados(): void
    {
        $aluno = $this->usuario('00123456789');
        $aluno->update(['nome' => 'Samuel Nome Completo']);
        $outro = $this->usuario('00234567890');
        $outro->update(['nome' => 'Samuel Outro Sobrenome']);
        foreach ([$aluno, $outro] as $pessoa) {
            $horario = Horario::create(['data' => now()->toDateString(),
                'hora' => $pessoa->id === $aluno->id ? '09:00:00' : '10:00:00',
                'nome' => 'samuel', 'matricula' => $pessoa->matricula, 'disponivel' => 0, 'confirmado' => 1]);
            DB::table('registros_atendimentos')->insert([
                'id_horario_original' => $horario->id, 'nome' => 'samuel', 'matricula' => $pessoa->matricula,
                'status' => 'Realizado', 'observacao' => 'Observação fictícia preservada.',
                'data_registro' => now(), 'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora,
            ]);
        }
        $legado = DB::table('registros_atendimentos')->insertGetId([
            'id_horario_original' => 0, 'nome' => 'Sem cadastro vinculado', 'matricula' => '00999999999',
            'status' => 'Cancelado pelo Aluno', 'data_registro' => now(),
        ]);
        $antes = [];
        foreach (['horarios', 'registros_atendimentos'] as $tabela) {
            $antes[$tabela] = DB::table($tabela)->orderBy('id')->get()->map(function ($linha) {
                unset($linha->nome);
                return (array) $linha;
            })->all();
        }
        $migration = require database_path('migrations/2026_10_04_010000_sincroniza_nomes_dos_atendimentos.php');
        $migration->up();
        $migration->up(); // Repetir a correção não duplica nem remove registros.
        foreach (['horarios', 'registros_atendimentos'] as $tabela) {
            foreach ([$aluno, $outro] as $pessoa) {
                $this->assertDatabaseHas($tabela, ['matricula' => $pessoa->matricula, 'nome' => $pessoa->nome]);
            }
            $depois = DB::table($tabela)->orderBy('id')->get()->map(function ($linha) {
                unset($linha->nome);
                return (array) $linha;
            })->all();
            $this->assertSame($antes[$tabela], $depois);
        }
        $this->assertDatabaseHas('registros_atendimentos', ['id' => $legado, 'nome' => 'Sem cadastro vinculado']);
    }
}
