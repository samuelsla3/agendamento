<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExclusaoHorarioLivreTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Use phpunit-exclusao-livres.xml com SQLite em memória, após config:clear.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        $usuario = Usuario::forceCreate([
            'nome' => 'Psicóloga de Teste', 'matricula' => '99999999999',
            'email' => 'teste@example.test', 'tipo' => 'psicologa',
            'senha' => Hash::make('teste'), 'password' => Hash::make('teste'),
        ]);
        $this->actingAs($usuario)->withSession(['usuario_tipo' => 'psicologa']);
    }

    private function vaga(array $dados = []): Horario
    {
        return Horario::create(array_merge([
            'data' => now()->addDay()->toDateString(), 'hora' => '09:00:00',
            'disponivel' => 1, 'confirmado' => 0,
        ], $dados));
    }

    private function excluir(Horario $horario, ?string $versao = null)
    {
        return $this->postJson('/agenda/acao', [
            'action' => 'delete', 'id' => $horario->id, 'versao' => $versao ?? $horario->versao(),
            '_operation_id' => (string) Str::uuid(),
        ]);
    }

    public function test_permite_excluir_vaga_livre_futura(): void
    {
        $horario = $this->vaga();
        $this->excluir($horario)->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseMissing('horarios', ['id' => $horario->id]);
    }

    public function test_requisicao_direta_nao_exclui_reserva_ocupada_concluida_ou_vaga_inconsistente(): void
    {
        foreach ([
            ['disponivel' => 0, 'nome' => 'Aluno Teste', 'matricula' => '00123'],
            ['disponivel' => 0, 'confirmado' => 1, 'nome' => 'Aluno Teste', 'matricula' => '00123'],
            ['disponivel' => 1, 'confirmado' => 1],
            ['disponivel' => 1, 'matricula' => '00123'],
            ['disponivel' => 1, 'nome' => 'Aluno Teste'],
        ] as $dados) {
            $horario = $this->vaga($dados);
            $antes = DB::table('horarios')->where('id', $horario->id)->first();
            $this->excluir($horario)->assertConflict();
            $this->assertEquals($antes, DB::table('horarios')->where('id', $horario->id)->first());
        }
        $this->assertDatabaseCount('registros_atendimentos', 0);
        $this->assertDatabaseCount('avisos_email', 0);
    }

    public function test_cancelamento_normal_libera_vaga_que_pode_ser_excluida_sem_apagar_historico(): void
    {
        $horario = $this->vaga(['disponivel' => 0, 'nome' => 'Aluno Teste', 'matricula' => '00123',
            'token_cancelamento' => Str::random(64)]);
        $this->postJson('/agenda/acao', ['action' => 'cancel_by_psicologa', 'id' => $horario->id,
            'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
            'justificativa' => 'Teste de cancelamento.'])->assertOk()->assertJsonPath('status', 'success');
        $historico = DB::table('registros_atendimentos')->where('id_horario_original', $horario->id)->first();
        $this->assertSame('Cancelado pela Psicóloga', $historico->status);
        $this->excluir($horario->fresh())->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseMissing('horarios', ['id' => $horario->id]);
        $this->assertEquals($historico, DB::table('registros_atendimentos')->where('id', $historico->id)->first());
    }

    public function test_vaga_que_foi_ocupada_apos_abrir_modal_recusa_versao_antiga(): void
    {
        $horario = $this->vaga();
        $versaoAnterior = $horario->versao();
        $horario->update(['disponivel' => 0, 'nome' => 'Aluno Teste', 'matricula' => '00123',
            'token_cancelamento' => Str::random(64)]);
        $this->excluir($horario, $versaoAnterior)->assertConflict();
        $this->assertDatabaseHas('horarios', ['id' => $horario->id, 'disponivel' => 0, 'matricula' => '00123']);
    }

    public function test_vaga_passada_continua_sem_permitir_exclusao(): void
    {
        $horario = $this->vaga(['data' => now()->subDay()->toDateString()]);
        $this->excluir($horario)->assertJsonPath('status', 'error');
        $this->assertDatabaseHas('horarios', ['id' => $horario->id]);
    }
}
