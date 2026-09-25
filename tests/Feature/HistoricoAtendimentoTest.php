<?php

namespace Tests\Feature;

use App\Models\Horario;
use App\Models\RegistroAtendimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class HistoricoAtendimentoTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Use phpunit-operacoes.xml com SQLite em memória.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
    }

    private function usuario(string $tipo = 'estudante'): Usuario
    {
        $matricula = (string) random_int(10000000000, 99999999999);
        return Usuario::forceCreate([
            'nome' => 'Pessoa de Teste', 'matricula' => $matricula,
            'email' => $matricula.'@example.test', 'tipo' => $tipo,
            'senha' => Hash::make('senha-teste'), 'password' => Hash::make('senha-teste'),
        ]);
    }

    private function reservar(): array
    {
        $aluno = $this->usuario();
        $horario = Horario::create([
            'data' => now()->addDays(3)->toDateString(), 'hora' => '09:00:00', 'disponivel' => 1,
        ]);
        $this->actingAs($aluno)->postJson('/agendar', [
            'action' => 'agendar', 'id_horario' => $horario->id,
            'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
        ])->assertOk()->assertJson(['status' => 'success']);
        return [$aluno, $horario->fresh()];
    }

    private function conferirHistoricoAposExcluir(Usuario $aluno, Horario $horario, string $status): void
    {
        $data = $horario->data;
        $hora = $horario->hora;
        $this->assertDatabaseHas('registros_atendimentos', [
            'id_horario_original' => $horario->id, 'status' => $status,
            'data_atendimento' => $data, 'hora_atendimento' => $hora,
        ]);

        // Simula mudança posterior da vaga, inclusive de ocupante.
        $horario->update([
            'data' => now()->addDays(10)->toDateString(), 'hora' => '16:00:00',
            'disponivel' => 0, 'confirmado' => 0, 'matricula' => 'OUTRA-MATRICULA',
            'nome' => 'Outro aluno', 'token_cancelamento' => Str::random(64),
        ]);
        $this->actingAs($aluno)->withSession(['usuario_tipo' => 'aluno'])->get('/')->assertOk()->assertViewHas('registros', function ($registros) use ($data, $hora) {
            $reg = $registros->first();
            return $registros->count() === 1 && $reg->data_atendimento === $data && $reg->hora_atendimento === $hora;
        });

        $horario->delete();
        $this->assertDatabaseCount('registros_atendimentos', 1);
        $this->get('/')->assertOk()->assertSee(
            \Carbon\Carbon::parse($data)->format('d/m/Y').' às 09:00'
        );
    }

    public function test_cancelamento_pelo_aluno_preserva_data_apos_alteracao_e_exclusao(): void
    {
        [$aluno, $horario] = $this->reservar();
        $this->postJson('/cancelar', [
            'action' => 'cancelar', 'id_horario' => $horario->id,
            'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
        ])->assertOk()->assertJson(['status' => 'success']);
        $this->conferirHistoricoAposExcluir($aluno, $horario, 'Cancelado pelo Aluno');
    }

    public function test_cancelamento_por_email_preserva_data_apos_alteracao_e_exclusao(): void
    {
        [$aluno, $horario] = $this->reservar();
        $url = URL::temporarySignedRoute('agendamento.cancelar.executar', now()->addHours(48), [
            'id' => $horario->id, 'token' => $horario->token_cancelamento,
        ]);
        $this->post($url)->assertOk();
        $this->conferirHistoricoAposExcluir($aluno, $horario, 'Cancelado pelo Aluno');
    }

    public function test_cancelamento_pela_psicologa_preserva_data_apos_alteracao_e_exclusao(): void
    {
        [$aluno, $horario] = $this->reservar();
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo' => 'psicologa'])
            ->postJson('/agenda/acao', [
                'action' => 'cancel_by_psicologa', 'id' => $horario->id,
                'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
            ])->assertOk()->assertJson(['status' => 'success']);
        $this->conferirHistoricoAposExcluir($aluno, $horario, 'Cancelado pela Psicóloga');
    }

    public function test_conclusao_preserva_data_e_semantica_anterior_do_relatorio(): void
    {
        [$aluno, $horario] = $this->reservar();
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo' => 'psicologa'])
            ->postJson('/agenda/acao', [
                'action' => 'confirmar', 'id' => $horario->id,
                'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
            ])->assertOk()->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('registros_atendimentos', [
            'data_registro' => $horario->data.' '.$horario->hora,
        ]);
        $this->conferirHistoricoAposExcluir($aluno, $horario, 'Realizado');
    }

    public function test_registro_antigo_nao_inventa_data_a_partir_da_vaga_atual(): void
    {
        [$aluno, $horario] = $this->reservar();
        $horario->update(['disponivel' => 1, 'nome' => null, 'matricula' => null, 'token_cancelamento' => null]);
        $registro = RegistroAtendimento::create([
            'id_horario_original' => $horario->id, 'nome' => $aluno->nome,
            'matricula' => $aluno->matricula, 'status' => 'Cancelado pelo Aluno',
            'observacao' => 'Registro legado', 'data_registro' => now()->subDays(5),
        ]);
        $original = $registro->fresh()->getRawOriginal();
        $texto = 'Data/hora não preservadas neste registro antigo';
        $this->get('/')->assertOk()->assertSee($texto);
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo' => 'psicologa'])
            ->get('/agenda')->assertOk()->assertSee($texto);
        $this->assertSame($original, $registro->fresh()->getRawOriginal());
    }

    public function test_painel_da_psicologa_usa_horario_salvo_no_cancelamento(): void
    {
        [$aluno, $horario] = $this->reservar();
        $data = $horario->data;
        $this->postJson('/cancelar', [
            'action' => 'cancelar', 'id_horario' => $horario->id,
            'versao' => $horario->versao(), '_operation_id' => (string) Str::uuid(),
        ])->assertOk();
        $horario->update(['data' => now()->addDays(10)->toDateString(), 'hora' => '16:00:00']);
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo' => 'psicologa'])
            ->get('/agenda')->assertOk()
            ->assertSee('Dia '.\Carbon\Carbon::parse($data)->format('d/m/Y').' às 09:00h');
    }
}
