<?php
namespace Tests\Feature;

use App\Models\{Horario, ProntuarioSessao, Usuario};
use App\Services\SuapService;
use App\Services\Suap\{EmailPessoalService, TurmaAlunoService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, DB, Hash, Http, Mail};
use Illuminate\Support\Str;
use Tests\TestCase;

class AgendarAlunoTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $psi;
    private Horario $vaga;
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) throw new \RuntimeException('Use somente SQLite em memória.');
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'psicologia.matriculas_suap' => ['99999999999']]);
        Http::preventStrayRequests(); Mail::fake(); $this->withoutVite();
        $this->psi = $this->usuario('99999999999', 'psicologa');
        $this->actingAs($this->psi)->withSession(['usuario_tipo' => 'psicologa', 'psicologa_autenticacao_versao' => 1,
            'prontuario_autorizado' => true, 'prontuario_usuario_id' => $this->psi->id,
            'prontuario_versao' => 1, 'prontuario_ultimo_acesso' => now()->timestamp]);
        $this->vaga = Horario::create(['data' => now()->addDays(2)->toDateString(), 'hora' => '09:00:00', 'disponivel' => 1, 'confirmado' => 0]);
    }
    private function usuario(string $matricula, string $tipo = 'estudante'): Usuario
    {
        return Usuario::forceCreate(['nome' => 'Aluno de Teste '.$matricula, 'matricula' => $matricula,
            'email' => $matricula.'@example.test', 'tipo' => $tipo, 'turma_codigo' => '3.18.1I',
            'senha' => Hash::make('teste'), 'password' => Hash::make('teste'),
            'psicologa_configurada_em' => $tipo === 'psicologa' ? now() : null,
            'psicologa_senha_local_definida' => $tipo === 'psicologa', 'psicologa_autenticacao_versao' => 1,
            'psicologa_prontuario_versao' => 1, 'senha_prontuario' => $tipo === 'psicologa' ? Hash::make('teste-prontuario') : null]);
    }
    private function dados(array $mais = []): array
    {
        return array_merge(['action' => 'agendar_aluno', 'id' => $this->vaga->id, 'versao' => $this->vaga->fresh()->versao(),
            '_operation_id' => (string) Str::uuid(), 'data' => $this->vaga->data, 'hora' => '09:00',
            'nome' => 'Aluno Novo Teste', 'matricula' => '00123456789', 'turma_codigo' => '4.18.1I', 'email' => ''], $mais);
    }
    public function test_reserva_exatamente_a_vaga_usando_cadastro_existente(): void
    {
        $aluno = $this->usuario('00123456789');
        $this->postJson('/agenda/acao', $this->dados(['aluno_id' => $aluno->id, 'nome' => 'Nome incorreto',
            'email' => 'outro@example.test', 'data' => now()->addDays(8)->toDateString(), 'hora' => '18:00']))->assertOk();
        $this->assertDatabaseCount('horarios', 1); $this->assertDatabaseCount('usuarios', 2);
        $this->assertSame($aluno->nome, $this->vaga->fresh()->nome);
        $this->assertSame('09:00:00', $this->vaga->fresh()->hora);
        $this->assertSame($this->vaga->data, $this->vaga->fresh()->data);
        $this->assertSame($aluno->id, $this->vaga->fresh()->usuario->id);
        $this->assertSame('Agendado', $this->vaga->fresh()->situacao_atendimento);
        $this->assertSame($aluno->email, $aluno->fresh()->email);
        $this->assertDatabaseCount('avisos_email', 1);
    }
    public function test_aluno_novo_mantem_id_atendimento_e_prontuario_no_primeiro_login_suap(): void
    {
        $this->postJson('/agenda/acao', $this->dados())->assertOk();
        $aluno = Usuario::where('matricula', '00123456789')->sole();
        $this->assertTrue($aluno->cadastro_provisorio); $this->assertNull($aluno->email);
        $sessao = ProntuarioSessao::create(['aluno_id' => $aluno->id, 'horario_id' => $this->vaga->id,
            'data_sessao' => $this->vaga->data, 'hora_sessao' => '09:00', 'anotacoes' => 'Registro fictício preservado.']);
        $this->mock(SuapService::class, function ($m) {
            $m->shouldReceive('autenticar')->once()->with('00123456789', 'senha-suap')->andReturn('token-teste');
            $m->shouldReceive('meusDados')->once()->with('token-teste')->andReturn(['vinculo' => ['situacao' => 'Matriculado', 'nome' => 'Nome Validado no SUAP']]);
        });
        $this->mock(EmailPessoalService::class, fn ($m) => $m->shouldReceive('resolver')->once()->andReturn('validado@example.test'));
        $this->mock(TurmaAlunoService::class, fn ($m) => $m->shouldReceive('resolver')->once()->andReturn('20261.4.18.1I'));
        Auth::logout(); $this->flushSession();
        $this->post('/login', ['matricula' => $aluno->matricula, 'senha' => 'senha-suap', '_operation_id' => (string) Str::uuid()])->assertRedirect('/');
        $this->assertAuthenticatedAs($aluno->fresh()); $this->assertDatabaseCount('usuarios', 2);
        $this->assertFalse($aluno->fresh()->cadastro_provisorio);
        $this->assertSame($aluno->id, $this->vaga->fresh()->usuario->id);
        $this->assertSame('Nome Validado no SUAP', $this->vaga->fresh()->nome);
        $this->assertSame($aluno->id, $sessao->fresh()->aluno_id);
        $this->assertSame('Registro fictício preservado.', $sessao->fresh()->anotacoes);
    }
    public function test_repeticao_da_operacao_nao_duplica_cadastro_ou_reserva(): void
    {
        $dados = $this->dados();
        $this->postJson('/agenda/acao', $dados)->assertOk();
        $this->postJson('/agenda/acao', $dados)->assertOk()->assertHeader('X-Operation-Replayed', 'true');
        $this->assertDatabaseCount('horarios', 1); $this->assertDatabaseCount('usuarios', 2); $this->assertDatabaseCount('avisos_email', 1);
    }
    public function test_versao_desatualizada_nao_cria_cadastro(): void
    {
        $dados = $this->dados(); $this->vaga->update(['hora' => '10:00:00']);
        $this->postJson('/agenda/acao', $dados)->assertStatus(409);
        $this->assertDatabaseCount('usuarios', 1); $this->assertSame(1, (int) $this->vaga->fresh()->disponivel);
    }
    public function test_vaga_ocupada_nao_e_sobrescrita_mesmo_com_versao_atual(): void
    {
        $this->vaga->update(['disponivel' => 0, 'nome' => 'Reserva existente', 'matricula' => '77777777777']);
        $this->postJson('/agenda/acao', $this->dados())->assertStatus(409);
        $this->assertSame('77777777777', $this->vaga->fresh()->matricula); $this->assertDatabaseCount('usuarios', 1);
    }
    public function test_horario_passado_e_recusado_sem_criar_aluno(): void
    {
        $this->vaga->update(['data' => now()->subDay()->toDateString()]);
        $this->postJson('/agenda/acao', $this->dados(['data' => now()->addDay()->toDateString()]))->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 1); $this->assertSame(1, (int) $this->vaga->fresh()->disponivel);
    }
    public function test_nao_permite_segundo_agendamento_no_mesmo_dia(): void
    {
        $aluno = $this->usuario('00123456789');
        Horario::create(['data' => $this->vaga->data, 'hora' => '10:00:00', 'disponivel' => 0,
            'nome' => $aluno->nome, 'matricula' => $aluno->matricula, 'confirmado' => 0]);
        $this->postJson('/agenda/acao', $this->dados())->assertUnprocessable();
        $this->assertSame(1, (int) $this->vaga->fresh()->disponivel); $this->assertDatabaseCount('avisos_email', 0);
    }
    public function test_id_de_aluno_diferente_da_matricula_e_recusado(): void
    {
        $aluno = $this->usuario('00234567890');
        $this->postJson('/agenda/acao', $this->dados(['aluno_id' => $aluno->id]))->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 2); $this->assertSame(1, (int) $this->vaga->fresh()->disponivel);
    }
    public function test_aluno_nao_pode_fazer_agendamento_administrativo(): void
    {
        $aluno = $this->usuario('00123456789');
        Auth::logout(); $this->flushSession(); $this->actingAs($aluno);
        $this->postJson('/agenda/acao', $this->dados())->assertForbidden();
        $this->assertSame(1, (int) $this->vaga->fresh()->disponivel);
    }
    public function test_modal_inclui_botao_e_busca_por_matricula(): void
    {
        $this->get('/agenda')->assertOk()->assertSee('Agendar aluno(a)')->assertSee('emergencial-matriculas')
            ->assertSee('O primeiro acesso será pelo SUAP');
    }

    public function test_emergencial_continua_permitindo_registro_passado_sem_ocupar_a_vaga_selecionada(): void
    {
        $this->postJson('/agenda/acao', $this->dados(['action' => 'atendimento_emergencial',
            'data' => now()->subDay()->toDateString(), 'hora' => '10:30']))->assertOk();
        $this->assertDatabaseCount('horarios', 2);
        $this->assertSame(1, (int) $this->vaga->fresh()->disponivel);
        $this->assertDatabaseHas('horarios', ['data' => now()->subDay()->toDateString(),
            'hora' => '10:30:00', 'matricula' => '00123456789', 'disponivel' => 0]);
    }

    public function test_reutiliza_vaga_cancelada_preservando_historico_anterior(): void
    {
        $this->vaga->update(['justificativa_cancelamento' => 'Motivo anterior fictício.']);
        $registro = DB::table('registros_atendimentos')->insertGetId(['id_horario_original' => $this->vaga->id,
            'nome' => 'Aluno da reserva anterior', 'matricula' => '00888888888', 'status' => 'Cancelado pelo Aluno',
            'data_registro' => now()->subHour(), 'data_atendimento' => $this->vaga->data,
            'hora_atendimento' => $this->vaga->hora, 'observacao' => 'Motivo anterior fictício.']);
        $this->postJson('/agenda/acao', $this->dados())->assertOk();
        $this->assertSame('00123456789', $this->vaga->fresh()->matricula);
        $this->assertNull($this->vaga->fresh()->justificativa_cancelamento);
        $this->assertDatabaseHas('registros_atendimentos', ['id' => $registro, 'matricula' => '00888888888',
            'status' => 'Cancelado pelo Aluno', 'observacao' => 'Motivo anterior fictício.']);
    }
}
