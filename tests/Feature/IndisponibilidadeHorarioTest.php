<?php
namespace Tests\Feature;

use App\Models\{Horario, Usuario};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, DB, Hash, Http, Mail};
use Illuminate\Support\Str;
use Tests\TestCase;

class IndisponibilidadeHorarioTest extends TestCase
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
    private function justificar(array $horarios = [], string $texto = 'Reunião COTEP'): array
    {
        return ['action' => 'justificar_indisponibilidade', '_operation_id' => (string) Str::uuid(),
            'justificativa' => $texto, 'horarios' => $horarios ?: [['id' => $this->vaga->id, 'versao' => $this->vaga->fresh()->versao()]]];
    }
    private function acao(string $acao, array $mais = []): array
    {
        return array_merge(['action' => $acao, 'id' => $this->vaga->id,
            'versao' => $this->vaga->fresh()->versao(), '_operation_id' => (string) Str::uuid()], $mais);
    }
    private function outraVaga(array $mais = []): Horario
    {
        return Horario::create(array_merge(['data' => $this->vaga->data, 'hora' => '10:00:00',
            'disponivel' => 1, 'confirmado' => 0], $mais));
    }
    public function test_justificativa_nao_cria_reserva_registro_ou_email(): void
    {
        $this->postJson('/agenda/acao', $this->justificar())->assertOk()->assertJsonPath('status', 'success');
        $vaga = $this->vaga->fresh();
        $this->assertSame('Reunião COTEP', $vaga->justificativa_indisponibilidade);
        $this->assertSame('Indisponível', $vaga->situacao_atendimento);
        $this->assertNull($vaga->matricula); $this->assertNull($vaga->nome); $this->assertNull($vaga->token_cancelamento);
        $this->assertDatabaseCount('registros_atendimentos', 0); $this->assertDatabaseCount('avisos_email', 0);
        $this->assertDatabaseCount('usuarios', 1); $this->assertDatabaseCount('horarios', 1);
    }
    public function test_mesma_operacao_pode_ser_repetida_sem_alterar_a_versao_novamente(): void
    {
        $dados = $this->justificar();
        $this->postJson('/agenda/acao', $dados)->assertOk();
        $versao = $this->vaga->fresh()->versao();
        $this->postJson('/agenda/acao', $dados)->assertOk()->assertHeader('X-Operation-Replayed', 'true');
        $this->assertSame($versao, $this->vaga->fresh()->versao());
    }
    public function test_aplica_mesma_justificativa_a_varias_vagas_futuras(): void
    {
        $outra = $this->outraVaga();
        $this->postJson('/agenda/acao', $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()],
            ['id' => $outra->id, 'versao' => $outra->versao()],
        ]))->assertOk()->assertJsonPath('message', 'Indisponibilidade aplicada a 2 horários.');
        $this->assertSame('Reunião COTEP', $outra->fresh()->justificativa_indisponibilidade);
        $this->assertSame('Reunião COTEP', $this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertDatabaseCount('registros_atendimentos', 0);
    }
    public function test_uma_vaga_ocupada_impede_todo_o_lote_sem_cancelar_o_aluno(): void
    {
        $outra = $this->outraVaga(['disponivel' => 0, 'nome' => 'Aluno preservado', 'matricula' => '11111111111']);
        $this->postJson('/agenda/acao', $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()],
            ['id' => $outra->id, 'versao' => $outra->versao()],
        ]))->assertStatus(409);
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertNull($outra->fresh()->justificativa_indisponibilidade);
        $this->assertSame('11111111111', $outra->fresh()->matricula);
        $this->assertDatabaseCount('registros_atendimentos', 0); $this->assertDatabaseCount('avisos_email', 0);
    }
    public function test_versao_desatualizada_impede_todo_o_lote(): void
    {
        $outra = $this->outraVaga();
        $dados = $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()],
            ['id' => $outra->id, 'versao' => $outra->versao()],
        ]);
        // Mantém o timestamp para testar que a própria justificativa participa da versão.
        DB::table('horarios')->where('id', $outra->id)->update(['justificativa_indisponibilidade' => 'Outro compromisso']);
        $this->postJson('/agenda/acao', $dados)->assertStatus(409);
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertSame('Outro compromisso', $outra->fresh()->justificativa_indisponibilidade);
    }
    public function test_vaga_excluida_impede_todo_o_lote(): void
    {
        $outra = $this->outraVaga(); $id = $outra->id; $versao = $outra->versao(); $outra->delete();
        $this->postJson('/agenda/acao', $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()], ['id' => $id, 'versao' => $versao],
        ]))->assertStatus(409);
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
    }
    public function test_horario_passado_impede_todo_o_lote(): void
    {
        $outra = $this->outraVaga(['data' => now()->subDay()->toDateString()]);
        $this->postJson('/agenda/acao', $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()], ['id' => $outra->id, 'versao' => $outra->versao()],
        ]))->assertUnprocessable();
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertNull($outra->fresh()->justificativa_indisponibilidade);
    }
    public function test_nao_sobrescreve_justificativa_existente_em_lote(): void
    {
        $outra = $this->outraVaga(['justificativa_indisponibilidade' => 'Outro compromisso']);
        $this->postJson('/agenda/acao', $this->justificar([
            ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()], ['id' => $outra->id, 'versao' => $outra->versao()],
        ]))->assertStatus(409);
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertSame('Outro compromisso', $outra->fresh()->justificativa_indisponibilidade);
    }
    public function test_justificativa_pode_ser_editada_individualmente_e_liberada(): void
    {
        $this->postJson('/agenda/acao', $this->justificar())->assertOk();
        $this->postJson('/agenda/acao', $this->justificar([], 'Reunião de equipe'))->assertOk();
        $this->assertSame('Reunião de equipe', $this->vaga->fresh()->justificativa_indisponibilidade);
        $this->postJson('/agenda/acao', $this->acao('liberar_indisponibilidade'))->assertOk();
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->assertSame('Disponível', $this->vaga->fresh()->situacao_atendimento);
        $aluno = $this->usuario('00123456789');
        Auth::logout(); $this->flushSession(); $this->actingAs($aluno);
        $this->postJson('/agendar', ['action' => 'agendar', 'id_horario' => $this->vaga->id,
            'versao' => $this->vaga->fresh()->versao(), '_operation_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame($aluno->matricula, $this->vaga->fresh()->matricula);
    }
    public function test_aluno_nao_pode_reservar_bloqueio_mesmo_com_versao_atual(): void
    {
        $this->postJson('/agenda/acao', $this->justificar())->assertOk();
        $aluno = $this->usuario('00123456789');
        Auth::logout(); $this->flushSession(); $this->actingAs($aluno);
        $this->postJson('/agendar', ['action' => 'agendar', 'id_horario' => $this->vaga->id,
            'versao' => $this->vaga->fresh()->versao(), '_operation_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('status', 'error');
        $this->assertNull($this->vaga->fresh()->matricula); $this->assertDatabaseCount('avisos_email', 0);
        $this->assertSame('Reunião COTEP', $this->vaga->fresh()->justificativa_indisponibilidade);
    }
    public function test_psi_nao_reserva_bloqueio_pelo_atalho_ou_emergencial(): void
    {
        $this->postJson('/agenda/acao', $this->justificar())->assertOk();
        $dados = ['data' => $this->vaga->data, 'hora' => '09:00', 'matricula' => '00123456789', 'nome' => 'Aluno fictício'];
        $this->postJson('/agenda/acao', $this->acao('agendar_aluno', $dados))->assertStatus(409);
        $this->postJson('/agenda/acao', $this->acao('atendimento_emergencial', $dados))->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 1); $this->assertDatabaseCount('avisos_email', 0);
    }
    public function test_nao_confunde_bloqueio_com_atendimento_ao_editar_confirmar_ou_cancelar(): void
    {
        $this->postJson('/agenda/acao', $this->justificar())->assertOk();
        foreach (['edit', 'confirmar', 'registrar_falta', 'cancel_by_psicologa'] as $acao) {
            $this->postJson('/agenda/acao', $this->acao($acao, ['data' => $this->vaga->data, 'hora' => '11:00']))->assertStatus(409);
        }
        $this->assertSame('09:00:00', $this->vaga->fresh()->hora);
        $this->assertDatabaseCount('registros_atendimentos', 0); $this->assertDatabaseCount('avisos_email', 0);
    }
    public function test_validacao_limita_texto_e_recusa_ids_duplicados(): void
    {
        $this->postJson('/agenda/acao', $this->justificar([], str_repeat('a', 301)))->assertUnprocessable();
        $this->postJson('/agenda/acao', $this->justificar([], '   '))->assertUnprocessable();
        $item = ['id' => $this->vaga->id, 'versao' => $this->vaga->versao()];
        $this->postJson('/agenda/acao', $this->justificar([$item, $item]))->assertUnprocessable();
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
        $this->postJson('/agenda/acao', $this->justificar([], str_repeat('á', 300)))->assertOk();
    }
    public function test_avisos_visiveis_no_calendario_publico_e_da_psi_sem_expor_cancelamento(): void
    {
        $this->vaga->update(['justificativa_cancelamento' => 'Motivo particular anterior']);
        $texto = '<script>alert("teste")</script> Reunião COTEP';
        $this->postJson('/agenda/acao', $this->justificar([], $texto))->assertOk();
        $this->getJson('/agenda/eventos')->assertOk()->assertJsonPath('0.extendedProps.disponivel', 0)
            ->assertJsonPath('0.extendedProps.justificativa_indisponibilidade', $texto);
        Auth::logout(); $this->flushSession();
        $this->get('/')->assertOk()->assertViewHas('horarios', function ($horarios) use ($texto) {
            $this->assertSame(0, $horarios[0]['extendedProps']['disponivel']);
            $this->assertSame($texto, $horarios[0]['extendedProps']['justificativa_indisponibilidade']);
            $this->assertNull($horarios[0]['extendedProps']['nome_agendado']);
            return !array_key_exists('justificativa_cancelamento', $horarios[0]['extendedProps']);
        })->assertDontSee($texto, false)->assertDontSee('Motivo particular anterior');
    }
    public function test_apenas_psicologa_pode_bloquear_ou_liberar_horarios(): void
    {
        $aluno = $this->usuario('00123456789');
        Auth::logout(); $this->flushSession(); $this->actingAs($aluno);
        $this->postJson('/agenda/acao', $this->justificar())->assertForbidden();
        $this->postJson('/agenda/acao', $this->acao('liberar_indisponibilidade'))->assertForbidden();
        Auth::logout(); $this->flushSession();
        $this->postJson('/agenda/acao', $this->justificar())->assertUnauthorized();
        $this->assertNull($this->vaga->fresh()->justificativa_indisponibilidade);
    }
    public function test_bloqueio_e_liberacao_preservam_cancelamento_anterior(): void
    {
        $this->vaga->update(['justificativa_cancelamento' => 'Motivo fictício anterior']);
        DB::table('registros_atendimentos')->insert(['id_horario_original' => $this->vaga->id,
            'nome' => 'Aluno anterior', 'matricula' => '00123456789', 'status' => 'Cancelado pelo Aluno',
            'observacao' => 'Motivo fictício anterior', 'data_registro' => now()]);
        $this->postJson('/agenda/acao', $this->justificar())->assertOk();
        $this->postJson('/agenda/acao', $this->acao('liberar_indisponibilidade'))->assertOk();
        $this->assertSame('Motivo fictício anterior', $this->vaga->fresh()->justificativa_cancelamento);
        $this->assertDatabaseCount('registros_atendimentos', 1);
        $this->assertDatabaseHas('registros_atendimentos', ['matricula' => '00123456789', 'observacao' => 'Motivo fictício anterior']);
    }
}
