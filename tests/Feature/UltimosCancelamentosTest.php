<?php

namespace Tests\Feature;

use App\Models\{Horario, Usuario};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Http, Mail};
use Tests\TestCase;

class UltimosCancelamentosTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Use somente SQLite em memória.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'app.timezone' => 'America/Bahia', 'psicologia.matriculas_suap' => ['99999999999']]);
        Carbon::setTestNow(Carbon::parse('2026-10-23 12:00:00', 'America/Bahia'));
        Http::preventStrayRequests(); Mail::fake(); $this->withoutVite();
        $usuario = Usuario::forceCreate(['nome' => 'Profissional de Teste', 'matricula' => '99999999999',
            'email' => 'profissional@example.test', 'tipo' => 'psicologa',
            'senha' => Hash::make('SenhaLocalTeste123'), 'password' => Hash::make('SenhaLocalTeste123'),
            'psicologa_conta_teste' => false, 'psicologa_configurada_em' => now(),
            'psicologa_senha_local_definida' => true, 'psicologa_autenticacao_versao' => 1,
            'senha_prontuario' => Hash::make('SenhaClinicaTeste123')]);
        $this->actingAs($usuario)->withSession(['psicologa_autenticacao_versao' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); parent::tearDown();
    }

    private function cancelamento(string $dia, string $hora, string $canceladoEm,
        array $vaga = [], string $status = 'Cancelado pelo Aluno'): int
    {
        // A vaga atual foi editada: a listagem deve considerar a data histórica.
        $horario = Horario::create(array_merge(['data' => '2026-12-10', 'hora' => '15:00:00',
            'disponivel' => 1, 'confirmado' => 0], $vaga));
        return DB::table('registros_atendimentos')->insertGetId(['id_horario_original' => $horario->id,
            'nome' => 'Aluno de Teste', 'matricula' => '11111111111', 'status' => $status,
            'data_atendimento' => $dia, 'hora_atendimento' => $hora, 'data_registro' => $canceladoEm]);
    }

    public function test_ordena_por_data_e_hora_originais_incluindo_horas_passadas_de_hoje(): void
    {
        $amanha = $this->cancelamento('2026-10-24', '08:00:00', '2026-10-23 11:59:00');
        $hojeTarde = $this->cancelamento('2026-10-23', '16:00:00', '2026-10-22 09:00:00');
        $hojeCedo = $this->cancelamento('2026-10-23', '09:00:00', '2026-10-20 09:00:00');
        $depois = $this->cancelamento('2026-10-25', '08:00:00', '2026-10-23 11:58:00');
        $this->cancelamento('2026-10-22', '17:00:00', '2026-10-23 11:57:00');
        $this->cancelamento('2026-10-23', '07:00:00', '2026-10-23 11:56:00', ['disponivel' => 0]);
        $this->cancelamento('2026-10-23', '08:00:00', '2026-10-23 11:55:00',
            ['justificativa_indisponibilidade' => 'Reunião']);
        $this->cancelamento('2026-10-23', '10:00:00', '2026-10-23 11:54:00', [], 'Cancelado pela Psicóloga');

        $this->get('/agenda')->assertOk()->assertViewHas('ultimosCancelamentos',
            fn ($itens) => $itens->pluck('id')->all() === [$hojeCedo, $hojeTarde, $amanha, $depois]);
    }

    public function test_limite_de_dez_prioriza_os_atendimentos_mais_proximos(): void
    {
        $esperados = [];
        for ($dias = 11; $dias >= 0; $dias--) {
            $dia = now()->addDays($dias)->toDateString();
            $id = $this->cancelamento($dia, '09:00:00', now()->subDays(11 - $dias)->toDateTimeString());
            array_unshift($esperados, $id);
        }
        $esperados = array_slice($esperados, 0, 10);
        $this->get('/agenda')->assertOk()->assertViewHas('ultimosCancelamentos',
            fn ($itens) => $itens->pluck('id')->all() === $esperados);
    }
}
