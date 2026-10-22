<?php

namespace Tests\Feature;

use App\Mail\RecuperacaoSenhaPsicologaMail;
use App\Models\{ProntuarioSessao, Usuario};
use App\Services\RecuperacaoSenhaPsicologa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, DB, Hash, Http, Mail};
use Tests\TestCase;

class RecuperacaoSenhaPsicologaTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $psi;
    private string $local = 'LocalAnterior123';
    private string $clinica = 'ClinicaAnterior123';
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) throw new \RuntimeException('Use somente SQLite em memória.');
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'app.url' => 'https://institucional.example',
            'mail.default' => 'array', 'psicologia.matriculas_suap' => ['99999999999']]);
        $this->withoutVite(); Mail::fake(); Http::preventStrayRequests();
        DB::table('configuracoes_sistema')->where('id', 1)->update(['atendimento_email' => 'contato@example.test']);
        $this->psi = Usuario::forceCreate(['nome' => 'Psicóloga Teste', 'matricula' => '99999999999',
            'email' => 'outro-email@example.test', 'tipo' => 'psicologa', 'senha' => Hash::make($this->local),
            'password' => Hash::make($this->local), 'senha_prontuario' => Hash::make($this->clinica),
            'psicologa_configurada_em' => now(), 'psicologa_senha_local_definida' => true,
            'psicologa_autenticacao_versao' => 1, 'psicologa_prontuario_versao' => 1]);
    }
    private function emitir(string $tipo = 'local'): array
    {
        $this->post('/recuperar-senha-psicologa', ['matricula' => $this->psi->matricula, 'tipo' => $tipo,
            'email' => 'injetado@example.test'])->assertRedirectToRoute('seguranca.recuperacao.form');
        $mail = Mail::sent(RecuperacaoSenhaPsicologaMail::class)->last();
        $this->assertNotNull($mail);
        parse_str(parse_url($mail->urlRecuperacao, PHP_URL_QUERY), $query);
        return ['id' => basename(parse_url($mail->urlRecuperacao, PHP_URL_PATH)), 'token' => $query['token'], 'mail' => $mail];
    }
    private function abrir(array $emitido): void
    {
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token='.$emitido['token'])
            ->assertRedirectToRoute('seguranca.recuperacao.redefinir', ['id' => $emitido['id']])
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'])->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertDontSee($emitido['token']);
    }
    private function salvar(array $emitido, string $senha = 'SenhaNovaSegura123')
    {
        return $this->post('/recuperar-senha-psicologa/'.$emitido['id'], ['nova_senha' => $senha, 'nova_senha_confirmation' => $senha]);
    }
    private function entrar(): void
    {
        $this->actingAs($this->psi)->withSession(['usuario_tipo' => 'psicologa', 'psicologa_autenticacao_versao' => 1,
            'prontuario_autorizado' => true, 'prontuario_usuario_id' => $this->psi->id,
            'prontuario_versao' => 1, 'prontuario_ultimo_acesso' => now()->timestamp]);
    }
    public function test_formulario_publico_e_atalhos_ficam_disponiveis(): void
    {
        $this->get('/recuperar-senha-psicologa')->assertOk()->assertSee('Matrícula da psicóloga')->assertSee('Senha do prontuário');
        $this->get('/login')->assertOk()->assertSee('Psicóloga: recuperar senha local ou do prontuário');
        $this->entrar();
        $this->get('/configuracoes/seguranca')->assertOk()->assertSee('Recuperar senha por e-mail')
            ->assertDontSee('Procure o responsável técnico pelo sistema para confirmar sua identidade');
    }
    public function test_destinatario_e_contato_configurado_e_token_nao_e_guardado_em_claro(): void
    {
        $emitido = $this->emitir();
        $this->assertTrue($emitido['mail']->hasTo('contato@example.test'));
        $this->assertFalse($emitido['mail']->hasTo('injetado@example.test'));
        $this->assertFalse($emitido['mail']->hasTo($this->psi->email));
        $registro = DB::table('recuperacoes_senha_psicologa')->sole();
        $this->assertSame(hash('sha256', $emitido['token']), $registro->token_hash);
        $this->assertStringNotContainsString($emitido['token'], json_encode($registro));
        $this->assertStringNotContainsString('contato@example.test', json_encode($registro));
        $this->assertSame('local', $registro->tipo);
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
    }
    public function test_mensagem_publica_nao_revela_existencia_ou_email_e_nao_envia_para_aluno(): void
    {
        $this->post('/recuperar-senha-psicologa', ['matricula' => '88888888888', 'tipo' => 'local'])
            ->assertSessionHas('sucesso', RecuperacaoSenhaPsicologa::MENSAGEM);
        $aluno = Usuario::forceCreate(['nome' => 'Aluno', 'tipo' => 'estudante', 'matricula' => '11111111111',
            'senha' => Hash::make('Senha12345678'), 'password' => Hash::make('Senha12345678')]);
        $this->post('/recuperar-senha-psicologa', ['matricula' => $aluno->matricula, 'tipo' => 'local'])
            ->assertSessionHas('sucesso', RecuperacaoSenhaPsicologa::MENSAGEM);
        DB::table('configuracoes_sistema')->where('id', 1)->update(['atendimento_email' => null]);
        $this->post('/recuperar-senha-psicologa', ['matricula' => $this->psi->matricula, 'tipo' => 'local'])
            ->assertSessionHas('sucesso', RecuperacaoSenhaPsicologa::MENSAGEM);
        Mail::assertNothingSent(); $this->assertDatabaseCount('recuperacoes_senha_psicologa', 0);
    }
    public function test_redefinicao_local_revoga_sessoes_e_preserva_senha_clinica(): void
    {
        $emitido = $this->emitir(); $this->abrir($emitido);
        $clinica = $this->psi->senha_prontuario;
        $this->salvar($emitido)->assertRedirectToRoute('login');
        $usuario = $this->psi->fresh();
        $this->assertTrue(Hash::check('SenhaNovaSegura123', $usuario->senha));
        $this->assertFalse(Hash::check($this->local, $usuario->password)); $this->assertSame($clinica, $usuario->senha_prontuario);
        $this->assertSame(2, $usuario->psicologa_autenticacao_versao); $this->assertSame(2, $usuario->psicologa_prontuario_versao);
        $this->assertNotNull(DB::table('recuperacoes_senha_psicologa')->value('usado_em'));
        $this->assertGuest();
        $this->actingAs($usuario)->withSession(['psicologa_autenticacao_versao' => 1]);
        $this->getJson('/agenda/eventos')->assertUnauthorized();
        $this->post('/login', ['matricula' => $usuario->matricula, 'senha' => 'SenhaNovaSegura123',
            '_operation_id' => (string) \Illuminate\Support\Str::uuid()])->assertRedirectToRoute('psicologa.index');
        $this->assertAuthenticatedAs($usuario);
        $this->assertNull(session('prontuario_autorizado'));
    }
    public function test_redefinicao_clinica_preserva_anotacoes_e_senha_local(): void
    {
        $aluno = Usuario::forceCreate(['nome' => 'Aluno fictício', 'tipo' => 'estudante', 'matricula' => '11111111111',
            'senha' => Hash::make('Senha12345678'), 'password' => Hash::make('Senha12345678')]);
        $sessao = ProntuarioSessao::create(['aluno_id' => $aluno->id, 'anotacoes' => 'Anotação fictícia preservada.', 'data_sessao' => now()->toDateString()]);
        $criptografado = DB::table('prontuario_sessoes')->where('id', $sessao->id)->value('anotacoes');
        $emitido = $this->emitir('prontuario'); $this->abrir($emitido);
        $local = $this->psi->senha;
        $this->salvar($emitido)->assertRedirectToRoute('login');
        $usuario = $this->psi->fresh();
        $this->assertSame($local, $usuario->senha); $this->assertSame(1, $usuario->psicologa_autenticacao_versao);
        $this->assertSame(2, $usuario->psicologa_prontuario_versao);
        $this->assertTrue(Hash::check('SenhaNovaSegura123', $usuario->senha_prontuario));
        $this->assertSame($criptografado, DB::table('prontuario_sessoes')->where('id', $sessao->id)->value('anotacoes'));
        $this->assertSame('Anotação fictícia preservada.', $sessao->fresh()->anotacoes);
        $this->actingAs($usuario)->withSession(['psicologa_autenticacao_versao' => 1,
            'prontuario_autorizado' => true, 'prontuario_usuario_id' => $usuario->id,
            'prontuario_versao' => 1, 'prontuario_ultimo_acesso' => now()->timestamp]);
        $this->getJson('/prontuarios/acesso/status')->assertOk()->assertJsonPath('valido', false);
        $this->get('/prontuarios/aluno/'.$aluno->id)->assertRedirectToRoute('psicologa.index');
        $this->post('/prontuarios/validar-senha', ['senha' => 'SenhaNovaSegura123'])->assertRedirect();
        $this->getJson('/prontuarios/acesso/status')->assertOk()->assertJsonPath('valido', true);
    }
    public function test_abrir_email_nao_consume_link_e_uso_posterior_e_unico(): void
    {
        $emitido = $this->emitir(); $this->abrir($emitido);
        $this->assertNull(DB::table('recuperacoes_senha_psicologa')->value('usado_em'));
        $this->abrir($emitido);
        $this->salvar($emitido)->assertRedirectToRoute('login');
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token='.$emitido['token'])->assertStatus(410);
        $this->salvar($emitido, 'OutraSenhaNova123')->assertStatus(410);
        $this->assertTrue(Hash::check('SenhaNovaSegura123', $this->psi->fresh()->senha));
    }
    public function test_token_incorreto_e_id_sem_prova_nao_abrem_formulario(): void
    {
        $emitido = $this->emitir();
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'])->assertStatus(410);
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token='.str_repeat('a', 64))->assertStatus(410);
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token[]=invalido')->assertStatus(410);
        $this->salvar($emitido)->assertStatus(410);
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
    }
    public function test_expira_em_quinze_minutos_inclusive_apos_abrir_formulario(): void
    {
        $emitido = $this->emitir(); $this->abrir($emitido);
        $this->travel(16)->minutes();
        $this->salvar($emitido)->assertStatus(410);
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
    }
    public function test_novo_pedido_substitui_link_anterior_e_respeita_intervalo(): void
    {
        $anterior = $this->emitir();
        $this->post('/recuperar-senha-psicologa', ['matricula' => $this->psi->matricula, 'tipo' => 'local'])->assertRedirect();
        Mail::assertSentCount(1);
        $this->travel(61)->seconds();
        $novo = $this->emitir();
        Mail::assertSentCount(2);
        $this->get('/recuperar-senha-psicologa/'.$anterior['id'].'?token='.$anterior['token'])->assertStatus(410);
        $this->abrir($novo);
    }
    public function test_regras_de_senha_e_confirmacao_nao_consumem_link_em_erro(): void
    {
        $emitido = $this->emitir(); $this->abrir($emitido);
        foreach (['curta', str_repeat('a', 12), str_repeat('a', 65).'1', $this->local, $this->clinica, str_repeat('á', 40).'1'] as $senha) {
            $this->salvar($emitido, $senha)->assertSessionHasErrors('nova_senha');
        }
        $this->post('/recuperar-senha-psicologa/'.$emitido['id'], ['nova_senha' => 'SenhaNovaSegura123',
            'nova_senha_confirmation' => 'ConfirmacaoDiferente123'])->assertSessionHasErrors('nova_senha');
        $this->assertNull(DB::table('recuperacoes_senha_psicologa')->value('usado_em'));
        $this->salvar($emitido)->assertRedirectToRoute('login');
    }
    public function test_alterar_tipo_no_post_nao_redefine_a_outra_senha(): void
    {
        $emitido = $this->emitir('prontuario'); $this->abrir($emitido);
        $this->post('/recuperar-senha-psicologa/'.$emitido['id'], ['tipo' => 'local', 'nova_senha' => 'SenhaNovaSegura123',
            'nova_senha_confirmation' => 'SenhaNovaSegura123'])->assertRedirectToRoute('login');
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
        $this->assertTrue(Hash::check('SenhaNovaSegura123', $this->psi->fresh()->senha_prontuario));
    }
    public function test_troca_manual_de_senha_ou_remocao_da_autorizacao_invalida_link(): void
    {
        $emitido = $this->emitir();
        $this->psi->forceFill(['psicologa_prontuario_versao' => 2])->save();
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token='.$emitido['token'])->assertStatus(410);
        $this->travel(61)->seconds(); $outro = $this->emitir('prontuario');
        config(['psicologia.matriculas_suap' => []]);
        $this->get('/recuperar-senha-psicologa/'.$outro['id'].'?token='.$outro['token'])->assertStatus(410);
    }
    public function test_troca_do_email_de_contato_invalida_link(): void
    {
        $emitido = $this->emitir();
        DB::table('configuracoes_sistema')->where('id', 1)->update(['atendimento_email' => 'novo@example.test']);
        $this->get('/recuperar-senha-psicologa/'.$emitido['id'].'?token='.$emitido['token'])->assertStatus(410);
    }
    public function test_url_do_email_ignora_host_injetado_e_preserva_subpasta(): void
    {
        config(['app.url' => 'https://institucional.example/nova-pasta']);
        $this->withServerVariables(['HTTP_HOST' => 'invasor.example']);
        $emitido = $this->emitir();
        $this->assertStringStartsWith('https://institucional.example/nova-pasta/recuperar-senha-psicologa/', $emitido['mail']->urlRecuperacao);
        $this->assertStringNotContainsString('invasor.example', $emitido['mail']->urlRecuperacao);
    }
    public function test_falha_smtp_invalida_link_e_mantem_mensagem_publica(): void
    {
        Mail::shouldReceive('to')->once()->with('contato@example.test')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('falha fictícia'));
        $this->post('/recuperar-senha-psicologa', ['matricula' => $this->psi->matricula, 'tipo' => 'local'])
            ->assertSessionHas('sucesso', RecuperacaoSenhaPsicologa::MENSAGEM);
        $this->assertNotNull(DB::table('recuperacoes_senha_psicologa')->value('usado_em'));
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
    }
    public function test_limite_de_requisicoes_nao_bloqueia_login(): void
    {
        for ($i = 0; $i < 5; $i++) $this->post('/recuperar-senha-psicologa', ['matricula' => '77777777777', 'tipo' => 'local'])->assertRedirect();
        $this->post('/recuperar-senha-psicologa', ['matricula' => '77777777777', 'tipo' => 'local'])->assertStatus(429);
        $this->assertSame(1, $this->psi->fresh()->psicologa_autenticacao_versao);
        $this->assertTrue(Hash::check($this->local, $this->psi->fresh()->senha));
    }
    public function test_sessao_antiga_nao_impede_recuperacao_e_nao_e_autenticada_automaticamente(): void
    {
        $emitido = $this->emitir();
        $this->actingAs($this->psi)->withSession(['psicologa_autenticacao_versao' => 0]);
        $this->abrir($emitido);
        $this->salvar($emitido)->assertRedirectToRoute('login'); $this->assertGuest();
    }
    public function test_alteracao_do_contato_exige_senha_clinica_e_nao_a_expoe_na_sessao(): void
    {
        $emitido = $this->emitir(); $this->entrar();
        $dados = ['atendimento_email' => 'novo@example.test', 'versao' => DB::table('configuracoes_sistema')->where('id', 1)->value('versao')];
        $this->post('/configuracoes/informacoes', $dados)->assertSessionHasErrors('senha_prontuario_contato');
        $this->assertSame('contato@example.test', RecuperacaoSenhaPsicologa::emailContato());
        $this->post('/configuracoes/informacoes', $dados + ['senha_prontuario_contato' => 'errada'])
            ->assertSessionHasErrors('senha_prontuario_contato')->assertSessionMissing('_old_input.senha_prontuario_contato');
        $this->post('/configuracoes/informacoes', $dados + ['senha_prontuario_contato' => $this->clinica])->assertRedirectToRoute('configuracoes.index');
        $this->assertSame('novo@example.test', RecuperacaoSenhaPsicologa::emailContato());
        $this->assertNotNull(DB::table('recuperacoes_senha_psicologa')->where('id', $emitido['id'])->value('usado_em'));
    }
    public function test_outros_dados_do_atendimento_continuam_editaveis_sem_senha_clinica(): void
    {
        $this->entrar();
        $this->post('/configuracoes/informacoes', ['atendimento_email' => 'contato@example.test', 'atendimento_local' => 'Sala 02',
            'versao' => DB::table('configuracoes_sistema')->where('id', 1)->value('versao')])->assertRedirectToRoute('configuracoes.index');
        $this->assertDatabaseHas('configuracoes_sistema', ['id' => 1, 'atendimento_local' => 'Sala 02']);
    }
}
