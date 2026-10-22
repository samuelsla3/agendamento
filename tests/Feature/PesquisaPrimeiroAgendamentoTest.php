<?php

namespace Tests\Feature;

use App\Models\{Horario, ProntuarioSessao, Usuario};
use App\Services\PesquisaPrimeiroAgendamento as Pesquisa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, Crypt, DB, Hash, Http, Mail};
use Illuminate\Support\Str;
use Tests\TestCase;

class PesquisaPrimeiroAgendamentoTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $aluno;
    private Horario $vaga;
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) throw new \RuntimeException('Use somente SQLite em memória.');
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'app.timezone' => 'America/Bahia',
            'psicologia.matriculas_suap' => ['99999999999']]);
        Carbon::setTestNow(Carbon::parse('2026-10-22 12:00:00', 'America/Bahia'));
        Http::preventStrayRequests(); Mail::fake(); $this->withoutVite();
        $this->aluno = $this->usuario('11111111111'); $this->actingAs($this->aluno);
        $this->vaga = $this->novaVaga('2026-11-30'); // a janela depende da reserva feita hoje, não da data da consulta.
    }
    protected function tearDown(): void
    {
        Carbon::setTestNow(); parent::tearDown();
    }
    private function usuario(string $matricula, string $tipo = 'estudante'): Usuario
    {
        return Usuario::forceCreate(['nome' => 'Pessoa de Teste '.$matricula, 'matricula' => $matricula,
            'email' => $matricula.'@example.test', 'tipo' => $tipo,
            'senha' => Hash::make('SenhaLocalTeste123'), 'password' => Hash::make('SenhaLocalTeste123'),
            'psicologa_configurada_em' => $tipo === 'psicologa' ? now() : null,
            'psicologa_senha_local_definida' => $tipo === 'psicologa', 'psicologa_autenticacao_versao' => 1,
            'psicologa_prontuario_versao' => 1, 'senha_prontuario' => $tipo === 'psicologa' ? Hash::make('SenhaClinicaTeste123') : null]);
    }
    private function novaVaga(string $dia, string $hora = '09:00:00'): Horario
    {
        return Horario::create(['data' => $dia, 'hora' => $hora, 'disponivel' => 1, 'confirmado' => 0]);
    }
    private function dadosReserva(?Horario $vaga = null): array
    {
        $vaga ??= $this->vaga;
        return ['action' => 'agendar', 'id_horario' => $vaga->id, 'versao' => $vaga->fresh()->versao(), '_operation_id' => (string) Str::uuid()];
    }
    private function reservar(?Horario $vaga = null)
    {
        return $this->postJson('/agendar', $this->dadosReserva($vaga));
    }
    private function respostas(array $extras = []): array
    {
        return array_merge(['p1' => 'sim', 'p2' => 'facil', 'p3' => 'nao', 'p4' => 'boa', 'p5' => 'mais_facil',
            'estrelas' => 4, 'sugestao' => 'Sugestão fictícia de teste.'], $extras);
    }
    private function convidar(): void
    {
        $this->reservar()->assertOk()->assertJsonPath('pesquisa', true);
        $this->postJson('/pesquisa/convite')->assertOk()->assertJsonPath('exibir', true);
    }
    private function pesquisador(): Usuario
    {
        $usuario = $this->usuario('20231180003'); Auth::logout(); $this->flushSession(); $this->actingAs($usuario); return $usuario;
    }
    public function test_primeiro_agendamento_confirmado_cria_convite_e_replay_nao_duplica(): void
    {
        $dados = $this->dadosReserva();
        $this->postJson('/agendar', $dados)->assertOk()->assertJsonPath('pesquisa', true);
        $this->postJson('/agendar', $dados)->assertOk()->assertJsonPath('pesquisa', true)->assertHeader('X-Operation-Replayed', 'true');
        $this->assertDatabaseCount('pesquisa_participacoes', 1);
        $this->assertDatabaseHas('pesquisa_participacoes', ['usuario_id' => $this->aluno->id, 'estado' => 'convidado']);
        $this->assertDatabaseCount('pesquisa_respostas', 0);
        $this->postJson('/pesquisa/convite')->assertOk()->assertJsonPath('exibir', true);
        $this->postJson('/pesquisa/convite')->assertOk()->assertJsonPath('exibir', false);
    }
    public function test_segundo_agendamento_nao_oferece_pesquisa_mesmo_sem_responder_a_primeira(): void
    {
        $this->reservar()->assertJsonPath('pesquisa', true);
        $outra = $this->novaVaga('2026-12-01');
        $this->reservar($outra)->assertOk()->assertJsonPath('pesquisa', false);
        $this->assertDatabaseCount('pesquisa_participacoes', 1);
    }
    public function test_cancelamento_anterior_impede_convite_mesmo_com_vaga_ja_liberada(): void
    {
        DB::table('registros_atendimentos')->insert(['id_horario_original' => 999999, 'matricula' => $this->aluno->matricula,
            'nome' => 'Aluno', 'status' => 'Cancelado pelo Aluno', 'data_registro' => now()->subDay()]);
        $this->reservar()->assertOk()->assertJsonPath('pesquisa', false);
        $this->assertDatabaseCount('pesquisa_participacoes', 0);
    }
    public function test_atendimento_antigo_confirmado_impede_convite(): void
    {
        Horario::create(['data' => '2026-09-15', 'hora' => '09:00:00', 'disponivel' => 0, 'confirmado' => 1,
            'matricula' => $this->aluno->matricula, 'nome' => $this->aluno->nome]);
        $this->reservar()->assertOk()->assertJsonPath('pesquisa', false);
    }
    public function test_prontuario_anterior_impede_convite_mesmo_sem_reserva_preservada(): void
    {
        ProntuarioSessao::create(['aluno_id' => $this->aluno->id, 'data_sessao' => '2026-09-15', 'anotacoes' => 'Registro fictício.']);
        $this->reservar()->assertOk()->assertJsonPath('pesquisa', false);
    }
    public function test_reserva_falha_nao_cria_convite(): void
    {
        $this->vaga->update(['justificativa_indisponibilidade' => 'Reunião']);
        $this->reservar()->assertOk()->assertJsonPath('status', 'error');
        $this->assertDatabaseCount('pesquisa_participacoes', 0);
    }
    public function test_inicio_fim_e_fuso_da_campanha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-22 02:59:59', 'UTC')); // ainda 21/10 na Bahia
        $this->reservar()->assertJsonPath('pesquisa', false);
        $this->assertDatabaseCount('pesquisa_participacoes', 0);
        Carbon::setTestNow(Carbon::parse('2026-10-22 03:00:00', 'UTC')); // início exato na Bahia
        $primeiro = $this->usuario('33333333333'); $this->actingAs($primeiro);
        $this->reservar($this->novaVaga('2026-12-02'))->assertJsonPath('pesquisa', true);
        Carbon::setTestNow(Carbon::parse('2026-11-14 02:59:59', 'UTC')); // 13/11 23:59:59 na Bahia
        $novo = $this->usuario('22222222222'); $this->actingAs($novo);
        $this->reservar($this->novaVaga('2026-12-01'))->assertJsonPath('pesquisa', true);
        Carbon::setTestNow(Carbon::parse('2026-11-14 03:00:00', 'UTC')); // fim exato
        $this->postJson('/pesquisa/convite')->assertJsonPath('exibir', false);
        $this->get('/')->assertOk()->assertDontSee('id="pesquisa-modal"', false);
        $this->postJson('/pesquisa/responder', $this->respostas())->assertStatus(410);
        $this->assertDatabaseCount('pesquisa_respostas', 0);
    }
    public function test_primeiro_agendamento_apos_fim_nao_convida(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-14 00:00:00', 'America/Bahia'));
        $this->reservar()->assertOk()->assertJsonPath('pesquisa', false);
        $this->assertDatabaseCount('pesquisa_participacoes', 0);
    }
    public function test_modal_so_aparece_apos_confirmacao_nao_automaticamente_no_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Participação opcional')->assertSee('pesquisa-modal')->assertSee('Agora não');
        $this->postJson('/pesquisa/convite')->assertJsonPath('exibir', false);
        $this->convidar();
        $this->postJson('/pesquisa/convite')->assertJsonPath('exibir', false);
    }
    public function test_envio_anonimo_e_repeticao_nao_duplica_resposta(): void
    {
        $this->convidar(); $dados = $this->respostas();
        $this->postJson('/pesquisa/responder', $dados)->assertOk()->assertJsonPath('message', Pesquisa::AGRADECIMENTO);
        $this->postJson('/pesquisa/responder', $dados)->assertOk();
        $this->assertDatabaseCount('pesquisa_respostas', 1);
        $resposta = DB::table('pesquisa_respostas')->sole();
        $this->assertTrue(Str::isUuid($resposta->id));
        $this->assertEqualsCanonicalizing(['id', 'p1', 'p2', 'p3', 'p4', 'p5', 'estrelas', 'sugestao'], array_keys((array) $resposta));
        $this->assertStringNotContainsString($this->aluno->matricula, json_encode($resposta));
        $this->assertStringNotContainsString($this->aluno->nome, json_encode($resposta));
        $this->assertStringNotContainsString($dados['sugestao'], $resposta->sugestao);
        $this->assertSame($dados['sugestao'], Crypt::decryptString($resposta->sugestao));
        $participacao = (array) DB::table('pesquisa_participacoes')->sole();
        $this->assertEqualsCanonicalizing(['usuario_id', 'estado'], array_keys($participacao));
        $this->assertSame('respondido', $participacao['estado']);
    }
    public function test_zero_estrelas_e_sugestao_vazia_sao_validos(): void
    {
        $this->convidar();
        $this->postJson('/pesquisa/responder', $this->respostas(['estrelas' => 0, 'sugestao' => '']))->assertOk();
        $this->assertDatabaseHas('pesquisa_respostas', ['estrelas' => 0, 'sugestao' => null]);
    }
    public function test_perguntas_1_a_6_obrigatorias_e_opcoes_validadas(): void
    {
        $this->convidar();
        $dados = $this->respostas(); unset($dados['p1']);
        $this->postJson('/pesquisa/responder', $dados)->assertUnprocessable();
        $this->postJson('/pesquisa/responder', $this->respostas(['p2' => '<script>invalido</script>']))->assertUnprocessable();
        $this->postJson('/pesquisa/responder', $this->respostas(['estrelas' => 6]))->assertUnprocessable();
        $this->postJson('/pesquisa/responder', $this->respostas(['estrelas' => -1]))->assertUnprocessable();
        $this->postJson('/pesquisa/responder', $this->respostas(['sugestao' => str_repeat('a', 2001)]))->assertUnprocessable();
        $this->assertDatabaseCount('pesquisa_respostas', 0);
        $this->postJson('/pesquisa/responder', $this->respostas())->assertOk();
    }
    public function test_fechar_sem_responder_nao_reabre_no_proximo_agendamento(): void
    {
        $this->convidar();
        $this->postJson('/pesquisa/dispensar')->assertOk();
        $this->postJson('/pesquisa/responder', $this->respostas())->assertForbidden();
        $this->reservar($this->novaVaga('2026-12-01'))->assertJsonPath('pesquisa', false);
        $this->assertDatabaseCount('pesquisa_respostas', 0);
    }
    public function test_sem_convite_nao_pode_inventar_respostas(): void
    {
        $this->postJson('/pesquisa/responder', $this->respostas())->assertForbidden();
        $this->assertDatabaseCount('pesquisa_respostas', 0);
    }
    public function test_outro_aluno_nao_pode_usar_convite_nem_ver_resultados(): void
    {
        $this->convidar();
        $outro = $this->usuario('22222222222'); $this->actingAs($outro);
        $this->postJson('/pesquisa/convite')->assertJsonPath('exibir', false);
        $this->postJson('/pesquisa/responder', $this->respostas())->assertForbidden();
        $this->get('/pesquisa')->assertForbidden();
        $this->delete('/pesquisa/respostas/'.Str::uuid())->assertForbidden();
    }
    public function test_visitante_nao_pode_coletar_consultar_ou_excluir(): void
    {
        Auth::logout(); $this->flushSession();
        $this->get('/pesquisa')->assertRedirectToRoute('login');
        $this->postJson('/pesquisa/convite')->assertUnauthorized();
        $this->postJson('/pesquisa/responder', $this->respostas())->assertUnauthorized();
        $this->deleteJson('/pesquisa/respostas/'.Str::uuid())->assertUnauthorized();
    }
    public function test_pesquisador_consulta_e_exclui_resposta_sem_reabrir_convite(): void
    {
        $this->convidar(); $this->postJson('/pesquisa/responder', $this->respostas())->assertOk();
        $resposta = DB::table('pesquisa_respostas')->sole(); $autorId = $this->aluno->id;
        $this->pesquisador();
        $this->get('/pesquisa')->assertOk()->assertSee('Sugestão fictícia de teste.')->assertSee('4,00')
            ->assertDontSee($this->aluno->nome)->assertDontSee($this->aluno->matricula);
        $this->delete('/pesquisa/respostas/'.$resposta->id)->assertRedirectToRoute('pesquisa.index');
        $this->assertDatabaseCount('pesquisa_respostas', 0);
        $this->assertDatabaseHas('pesquisa_participacoes', ['usuario_id' => $autorId, 'estado' => 'respondido']);
        $this->get('/pesquisa')->assertOk()->assertSee('Ainda não há respostas registradas.');
    }
    public function test_psicologa_autorizada_pode_consultar_sem_desbloquear_prontuario(): void
    {
        $psi = $this->usuario('99999999999', 'psicologa');
        Auth::logout(); $this->flushSession(); $this->actingAs($psi)->withSession(['psicologa_autenticacao_versao' => 1]);
        $this->get('/pesquisa')->assertOk();
        $this->withSession(['psicologa_autenticacao_versao' => 0])->getJson('/pesquisa')->assertUnauthorized();
    }
    public function test_texto_e_exibido_sem_executar_html(): void
    {
        $this->convidar();
        $this->postJson('/pesquisa/responder', $this->respostas(['sugestao' => '<script>alert(1)</script>']))->assertOk();
        $this->pesquisador();
        $this->get('/pesquisa')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
    public function test_resultados_continuam_disponiveis_apos_campanha_encerrar(): void
    {
        $this->convidar(); $this->postJson('/pesquisa/responder', $this->respostas())->assertOk();
        $this->pesquisador(); Carbon::setTestNow(Carbon::parse('2026-11-15', 'America/Bahia'));
        $this->get('/pesquisa')->assertOk()->assertSee('Sugestão fictícia de teste.');
    }
}
