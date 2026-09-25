<?php
namespace Tests\Feature;

use App\Jobs\EnviarAvisoEmail;
use App\Mail\AvisoSistemaMail;
use App\Models\Horario;
use App\Models\Usuario;
use App\Services\AvisosEmail;
use App\Services\SuapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProtecaoOperacoesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t',32))]);
        Http::preventStrayRequests();
        Mail::fake();
    }
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Estes testes exigem SQLite em memória. Use phpunit-operacoes.xml e limpe o cache de configuração.');
        }
    }
    private function usuario(string $tipo = 'estudante'): Usuario
    {
        $m = (string) random_int(10000000000,99999999999);
        return Usuario::forceCreate(['nome' => 'Pessoa de Teste', 'matricula' => $m,
            'email' => $m.'@example.test', 'senha' => Hash::make('senha-teste'),
            'password' => Hash::make('senha-teste'), 'tipo' => $tipo]);
    }
    private function horario(): Horario
    {
        return Horario::create(['data' => now()->addDays(2)->toDateString(), 'hora' => '09:00:00', 'disponivel' => 1]);
    }
    private function reservar(Usuario $u, Horario $h): array
    {
        $dados = ['action' => 'agendar', 'id_horario' => $h->id, 'versao' => $h->versao(), '_operation_id' => (string) Str::uuid()];
        $this->actingAs($u)->postJson('/agendar', $dados)->assertOk()->assertJson(['status' => 'success']);
        return $dados;
    }
    public function test_reserva_repetida_envia_um_email_sem_worker(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $dados=$this->reservar($u,$h);
        $token=$h->fresh()->token_cancelamento;
        $this->postJson('/agendar',$dados)->assertOk()->assertHeader('X-Operation-Replayed','true');
        $this->assertSame($token,$h->fresh()->token_cancelamento);
        $this->assertDatabaseCount('avisos_email',1);
        $this->assertDatabaseCount('jobs',0);
        Mail::assertSent(AvisoSistemaMail::class,1);
        $this->assertDatabaseHas('avisos_email',['estado'=>'enviado']);
    }
    public function test_mesma_chave_com_outros_dados_e_recusada(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $dados=$this->reservar($u,$h);
        $dados['action']='cancelar';
        $this->postJson('/agendar',$dados)->assertStatus(409);
        $this->assertSame(0,(int)$h->fresh()->disponivel);
    }
    public function test_duas_reservas_no_mesmo_dia_sao_recusadas(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $this->reservar($u,$h);
        $outro=Horario::create(['data'=>$h->data,'hora'=>'10:00:00','disponivel'=>1]);
        $this->postJson('/agendar',['action'=>'agendar','id_horario'=>$outro->id,'versao'=>$outro->versao(),'_operation_id'=>(string)Str::uuid()])
            ->assertJson(['status'=>'error']);
        $this->assertDatabaseCount('avisos_email',1);
    }
    public function test_conclusao_duplicada_cria_um_unico_historico(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $this->reservar($u,$h);
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa']);
        $dados=['action'=>'confirmar','id'=>$h->id,'versao'=>$h->fresh()->versao(),'_operation_id'=>(string)Str::uuid()];
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $this->postJson('/agenda/acao',$dados)->assertOk()->assertHeader('X-Operation-Replayed','true');
        $dados['_operation_id']=(string)Str::uuid();
        $this->postJson('/agenda/acao',$dados)->assertStatus(409);
        $this->assertDatabaseCount('registros_atendimentos',1);
        $this->assertNull($h->fresh()->token_cancelamento);
    }
    public function test_cancelar_pelo_painel_nao_repete_historico_e_aviso(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $this->reservar($u,$h);
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa']);
        $dados=['action'=>'cancel_by_psicologa','id'=>$h->id,'versao'=>$h->fresh()->versao(),'justificativa'=>'Teste','_operation_id'=>(string)Str::uuid()];
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $this->assertDatabaseCount('registros_atendimentos',1);
        $this->assertDatabaseCount('avisos_email',2); // confirmação + cancelamento
        $this->assertDatabaseCount('jobs',0);
        Mail::assertSent(AvisoSistemaMail::class,2);
    }
    public function test_link_antigo_nao_cancela_nova_reserva(): void
    {
        $u=$this->usuario(); $h=$this->horario(); $this->reservar($u,$h);
        $token=$h->fresh()->token_cancelamento;
        $get=URL::temporarySignedRoute('agendamento.cancelarDirect',now()->addHours(48),['id'=>$h->id,'token'=>$token]);
        $post=URL::temporarySignedRoute('agendamento.cancelar.executar',now()->addHours(48),['id'=>$h->id,'token'=>$token]);
        $this->get($get)->assertOk(); $this->get($get)->assertOk();
        $this->assertSame($token,$h->fresh()->token_cancelamento);
        $this->post($post)->assertOk();
        $this->post($post)->assertStatus(410);
        $this->reservar($this->usuario(),$h->fresh());
        $this->get($get)->assertStatus(410);
        $this->post($post)->assertStatus(410);
        $this->assertSame(0,(int)$h->fresh()->disponivel);
        $this->assertDatabaseCount('registros_atendimentos',1);
        $this->post('/cancelar-executar/'.$h->id)->assertForbidden();
    }
    public function test_geracao_repetida_com_chaves_diferentes_nao_duplica_vagas(): void
    {
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa']);
        $dados=['action'=>'generate_default','data_inicio'=>now()->addDays(3)->toDateString(),
            'data_fim'=>now()->addDays(3)->toDateString(),'dias_semana'=>[1,2,3,4,5,6,7],
            'horas_selecionadas'=>['09:00:00','14:00:00'],'_operation_id'=>(string)Str::uuid()];
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $dados['_operation_id']=(string)Str::uuid();
        $this->postJson('/agenda/acao',$dados)->assertOk();
        $this->assertDatabaseCount('horarios',2);
    }
    public function test_anotacao_repetida_nao_duplica_e_nao_vaza_no_controle(): void
    {
        $aluno=$this->usuario(); $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa','prontuario_autorizado'=>true]);
        $dados=['anotacoes'=>'Conteúdo clínico fictício reservado','data_sessao'=>now()->toDateString(),'_operation_id'=>(string)Str::uuid()];
        $this->post('/prontuarios/aluno/'.$aluno->id,$dados)->assertRedirect();
        $this->post('/prontuarios/aluno/'.$aluno->id,$dados)->assertRedirect()->assertHeader('X-Operation-Replayed','true');
        $this->assertDatabaseCount('prontuario_sessoes',1);
        $this->assertStringNotContainsString('Conteúdo clínico',json_encode(DB::table('operacoes_http')->get()));
        $this->withSession(['prontuario_autorizado'=>false])->post('/prontuarios/aluno/'.$aluno->id,$dados)->assertForbidden();
    }
    public function test_rollback_remove_aviso_e_nao_envia_email(): void
    {
        try {
            DB::transaction(function () {
                AvisosEmail::registrar('teste-rollback','teste@example.test','Teste','Corpo');
                throw new \RuntimeException('Reverter');
            });
        } catch (\RuntimeException $e) {}
        $this->assertDatabaseCount('avisos_email',0);
        $this->assertDatabaseCount('jobs',0);
        Mail::assertNothingSent();
    }
    public function test_aviso_repetido_e_job_antigo_nao_reenviam_email(): void
    {
        AvisosEmail::registrar('teste-envio','teste@example.test','Teste','Corpo');
        AvisosEmail::registrar('teste-envio','teste@example.test','Teste','Corpo');
        $id=DB::table('avisos_email')->value('id');
        (new EnviarAvisoEmail($id))->handle();
        (new EnviarAvisoEmail($id))->handle();
        Mail::assertSent(AvisoSistemaMail::class,1);
        $this->assertDatabaseHas('avisos_email',['id'=>$id,'estado'=>'enviado','conteudo'=>'']);
    }
    public function test_falha_de_envio_nao_e_repetida_automaticamente(): void
    {
        Mail::swap(\Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class));
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Resposta incerta'));
        AvisosEmail::registrar('teste-falha','teste@example.test','Teste','Corpo');
        AvisosEmail::registrar('teste-falha','teste@example.test','Teste','Corpo');
        $id=DB::table('avisos_email')->value('id');
        (new EnviarAvisoEmail($id))->handle();
        $this->assertDatabaseHas('avisos_email',['id'=>$id,'estado'=>'revisar']);
    }
    public function test_login_repetido_nao_consulta_suap_novamente(): void
    {
        $this->mock(SuapService::class, function ($mock) { $mock->shouldReceive('autenticar')->once()->andReturn(null); });
        $dados=['matricula'=>'99999999999','senha'=>'invalida','_operation_id'=>(string)Str::uuid()];
        $this->post('/login',$dados)->assertRedirect();
        $this->post('/login',$dados)->assertStatus(409);
    }
    public function test_scheduler_repetido_registra_um_lembrete(): void
    {
        $u=$this->usuario();
        Horario::create(['data'=>now()->addDay()->toDateString(),'hora'=>'09:00:00','disponivel'=>0,
            'nome'=>$u->nome,'matricula'=>$u->matricula,'token_cancelamento'=>Str::random(64)]);
        $this->artisan('atendimentos:enviar-lembretes')->assertExitCode(0);
        $this->artisan('atendimentos:enviar-lembretes')->assertExitCode(0);
        $this->assertDatabaseCount('avisos_email',1);
        $this->assertDatabaseCount('jobs',0);
        Mail::assertSent(AvisoSistemaMail::class,1);
    }

    public function test_lembrete_pendente_e_descartado_apos_cancelamento(): void
    {
        $u=$this->usuario();
        $h=Horario::create(['data'=>now()->addDay()->toDateString(),'hora'=>'09:00:00','disponivel'=>0,
            'nome'=>$u->nome,'matricula'=>$u->matricula,'token_cancelamento'=>Str::random(64)]);
        // Simula um aviso legado que já estava pendente antes da atualização.
        $dados=['email'=>$u->email,'assunto'=>'Lembrete','html'=>'Teste',
            'condicao'=>['horario_id'=>$h->id,'reserva_hash'=>hash('sha256',$h->token_cancelamento),'data'=>$h->data]];
        $id=DB::table('avisos_email')->insertGetId([
            'evento'=>hash('sha256','lembrete-legado'),
            'conteudo'=>\Illuminate\Support\Facades\Crypt::encryptString(json_encode($dados,JSON_THROW_ON_ERROR)),
            'estado'=>'pendente','created_at'=>now(),'updated_at'=>now(),
        ]);
        $h->update(['disponivel'=>1,'token_cancelamento'=>null]);
        (new EnviarAvisoEmail($id))->handle();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('avisos_email',['id'=>$id,'estado'=>'descartado']);
    }
    public function test_prontuario_renderiza_json_e_identificador_de_operacao(): void
    {
        $this->withoutVite();
        $u=$this->usuario();
        $texto="Aspas ' e \"\n</script><script>alert(1)</script>";
        \App\Models\ProntuarioSessao::create(['aluno_id'=>$u->id,'data_sessao'=>now()->toDateString(),'anotacoes'=>$texto]);
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa','prontuario_autorizado'=>true]);
        $res=$this->get('/prontuarios/aluno/'.$u->id)->assertOk();
        $crawler=new \Symfony\Component\DomCrawler\Crawler($res->getContent());
        $dados=json_decode($crawler->filter('[data-sessao]')->attr('data-sessao'),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame($texto,$dados['anotacoes']);
        $this->assertGreaterThan(0,$crawler->filter('input[name="_operation_id"]')->count());
        $this->assertStringNotContainsString('cdn.tailwindcss.com',$res->getContent());
    }

    public function test_validacao_html_nao_consumiu_operacao_antes_da_correcao(): void
    {
        $aluno=$this->usuario();
        $this->actingAs($this->usuario('psicologa'))->withSession(['usuario_tipo'=>'psicologa','prontuario_autorizado'=>true]);
        $dados=['anotacoes'=>'','data_sessao'=>now()->toDateString(),'_operation_id'=>(string)Str::uuid()];
        $this->post('/prontuarios/aluno/'.$aluno->id,$dados)->assertSessionHasErrors('anotacoes');
        $this->assertDatabaseCount('operacoes_http',0);
        $dados['anotacoes']='Texto corrigido';
        $this->post('/prontuarios/aluno/'.$aluno->id,$dados)->assertRedirect();
        $this->assertDatabaseCount('prontuario_sessoes',1);
        $this->assertDatabaseCount('operacoes_http',1);
    }

    public function test_envio_aguarda_confirmacao_da_transacao_externa(): void
    {
        $nivelInicial=DB::transactionLevel();
        Mail::swap(\Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class));
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andReturnUsing(function () use ($nivelInicial) {
            // Resta somente a transação isoladora do próprio teste.
            $this->assertSame($nivelInicial,DB::transactionLevel());
            $this->assertDatabaseHas('avisos_email',['estado'=>'em_envio']);
        });
        DB::transaction(function () {
            AvisosEmail::registrar('confirmar-primeiro','teste@example.test','Teste','Corpo');
            $this->assertDatabaseHas('avisos_email',['estado'=>'pendente']);
            DB::transaction(function () {
                $this->assertDatabaseHas('avisos_email',['estado'=>'pendente']);
            });
        });
        $this->assertDatabaseHas('avisos_email',['estado'=>'enviado']);
        $this->assertDatabaseCount('jobs',0);
    }

    public function test_falha_no_email_preserva_reserva_e_resposta_sem_reenvio(): void
    {
        Mail::swap(\Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class));
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andReturnUsing(function () {
            $this->assertNotNull(DB::table('operacoes_http')->value('resposta'));
            throw new \RuntimeException('SMTP indisponível');
        });
        $u=$this->usuario(); $h=$this->horario(); $dados=$this->reservar($u,$h);
        $this->postJson('/agendar',$dados)->assertOk()->assertJson(['status'=>'success'])
            ->assertHeader('X-Operation-Replayed','true');
        $this->assertSame(0,(int)$h->fresh()->disponivel);
        $this->assertNotEmpty($h->fresh()->token_cancelamento);
        $this->assertDatabaseCount('operacoes_http',1);
        $this->assertDatabaseCount('avisos_email',1);
        $this->assertDatabaseHas('avisos_email',['estado'=>'revisar']);
        $this->assertDatabaseCount('jobs',0);
    }

}
