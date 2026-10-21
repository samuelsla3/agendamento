<?php
namespace Tests\Feature;

use App\Models\Usuario;
use App\Services\ComunicacaoPrivada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\{DB, Event, Hash, Mail};
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailContatoTest extends TestCase
{
    use RefreshDatabase;
    private Usuario $aluno;
    private int $conversa;
    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:'
            || !empty(config('database.connections.sqlite.url'))) {
            throw new \RuntimeException('Os testes exigem SQLite em memória.');
        }
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'mail.default' => 'array',
            'mail.from.address' => 'sistema@example.test', 'mail.from.name' => 'Psicologia IFBA',
            'psicologia.matriculas_suap' => ['99999999999']]);
        Mail::purge('array');
        DB::table('configuracoes_sistema')->where('id', 1)->update(['atendimento_email' => 'psicologia@example.test']);
        $this->aluno = $this->usuario('11111111111');
        $this->conversa = DB::table('mensagens_conversas')->insertGetId(['aluno_id' => $this->aluno->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->entrar($this->aluno);
    }
    private function usuario(string $matricula, string $tipo = 'estudante'): Usuario
    {
        $email = $matricula.'@example.test';
        return Usuario::forceCreate(['nome' => 'Pessoa de Teste '.$matricula, 'matricula' => $matricula, 'email' => $email,
            'tipo' => $tipo, 'senha' => Hash::make('senha-teste'), 'password' => Hash::make('senha-teste'), 'email_confirmado_em' => now(),
            'email_confirmado_hash' => hash('sha256', $email),
            'psicologa_configurada_em' => $tipo === 'psicologa' ? now() : null,
            'psicologa_senha_local_definida' => $tipo === 'psicologa', 'psicologa_autenticacao_versao' => 1,
            'psicologa_prontuario_versao' => 1, 'senha_prontuario' => $tipo === 'psicologa' ? Hash::make('senha-prontuario-teste') : null]);
    }
    private function entrar(Usuario $usuario, bool $desbloqueado = true): void
    {
        $this->actingAs($usuario)->withHeader('X-Mensagens-Contexto', ComunicacaoPrivada::contexto($usuario));
        if ($usuario->tipo === 'psicologa') $this->withSession(['psicologa_autenticacao_versao' => 1,
            'prontuario_autorizado' => $desbloqueado, 'prontuario_usuario_id' => $usuario->id,
            'prontuario_versao' => 1, 'prontuario_ultimo_acesso' => now()->timestamp]);
    }
    private function dados(array $mais = []): array
    {
        return array_merge(['assunto' => 'Contato de teste', 'texto' => "Olá! Informação com acentuação.\n<script>alert(1)</script>",
            'chave_envio' => (string) Str::uuid()], $mais);
    }
    private function url(): string { return '/mensagens/conversas/'.$this->conversa.'/email'; }
    private function mensagens() { return Mail::mailer('array')->getSymfonyTransport()->messages(); }
    public function test_aluno_envia_mime_com_anexos_reais_e_reply_to_confirmado(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jP0kAAAAASUVORK5CYII=');
        $this->post($this->url(), $this->dados(['arquivos' => [UploadedFile::fake()->createWithContent('documento.pdf', $pdf),
            UploadedFile::fake()->createWithContent('imagem.png', $png)], 'destinatario' => 'estranho@example.test']),
            ['Accept' => 'application/json'])->assertOk()->assertJsonPath('estado', 'enviado');
        $this->assertCount(1, $this->mensagens()); $email = $this->mensagens()->first()->getOriginalMessage();
        $this->assertSame('psicologia@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('sistema@example.test', $email->getFrom()[0]->getAddress());
        $this->assertSame($this->aluno->email, $email->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('&lt;script&gt;', $email->getHtmlBody());
        $this->assertStringContainsString('Informação com acentuação.', $email->getTextBody());
        $this->assertSame(['documento.pdf', 'imagem.png'], array_map(fn ($a) => $a->getFilename(), $email->getAttachments()));
        $this->assertSame($pdf, $email->getAttachments()[0]->getBody()); $this->assertSame($png, $email->getAttachments()[1]->getBody());
        $this->assertStringContainsString('application/pdf', $email->toString());
        $this->assertDatabaseCount('mensagens_internas', 0); $this->assertDatabaseCount('emails_contato', 1);
        $this->assertStringNotContainsString('Contato de teste', json_encode(DB::table('emails_contato')->first()));
    }
    public function test_psicologa_envia_para_aluno_e_resposta_aponta_para_setor(): void
    {
        $this->entrar($this->usuario('99999999999', 'psicologa'));
        $this->postJson($this->url(), $this->dados())->assertOk()->assertJsonPath('estado', 'enviado');
        $email = $this->mensagens()->first()->getOriginalMessage();
        $this->assertSame($this->aluno->email, $email->getTo()[0]->getAddress());
        $this->assertSame('psicologia@example.test', $email->getReplyTo()[0]->getAddress());
    }
    public function test_repetir_chave_ou_consultar_status_nao_reenvia(): void
    {
        $dados = $this->dados();
        $this->postJson($this->url(), $dados)->assertOk()->assertJsonPath('estado', 'enviado');
        $this->postJson($this->url(), $dados)->assertOk()->assertJsonPath('estado', 'enviado');
        $this->getJson($this->url().'/'.$dados['chave_envio'])->assertOk()->assertJsonPath('estado', 'enviado');
        $this->assertCount(1, $this->mensagens()); $this->assertDatabaseCount('emails_contato', 1);
    }
    public function test_chave_reutilizada_com_outro_conteudo_e_recusada(): void
    {
        $dados = $this->dados(); $this->postJson($this->url(), $dados)->assertOk();
        $this->postJson($this->url(), array_merge($dados, ['texto' => 'Outro conteúdo']))->assertStatus(409);
        $this->assertCount(1, $this->mensagens());
    }
    public function test_aluno_nao_acessa_conversa_alheia(): void
    {
        $this->entrar($this->usuario('22222222222'));
        $this->postJson($this->url(), $this->dados())->assertNotFound();
        $this->getJson($this->url().'/'.Str::uuid())->assertNotFound(); $this->assertCount(0, $this->mensagens());
    }
    public function test_contexto_de_outra_conta_e_recusado(): void
    {
        $this->withHeader('X-Mensagens-Contexto', 'contexto-incorreto');
        $this->postJson($this->url(), $this->dados())->assertForbidden(); $this->assertDatabaseCount('emails_contato', 0);
    }
    public function test_psicologa_precisa_desbloquear_o_acesso(): void
    {
        $this->entrar($this->usuario('99999999999', 'psicologa'), false);
        $this->postJson($this->url(), $this->dados())->assertStatus(423); $this->assertCount(0, $this->mensagens());
    }
    public function test_email_do_aluno_precisa_estar_confirmado_nas_duas_direcoes(): void
    {
        $this->aluno->forceFill(['email_confirmado_em' => null])->save();
        $this->postJson($this->url(), $this->dados())->assertUnprocessable();
        $this->entrar($this->usuario('99999999999', 'psicologa'));
        $this->postJson($this->url(), $this->dados())->assertUnprocessable(); $this->assertCount(0, $this->mensagens());
    }
    public function test_arquivo_com_extensao_falsa_e_recusado(): void
    {
        $this->post($this->url(), $this->dados(['arquivos' => [UploadedFile::fake()->createWithContent('arquivo.pdf', 'arquivo de texto')]]),
            ['Accept' => 'application/json'])->assertUnprocessable(); $this->assertCount(0, $this->mensagens());
    }
    public function test_anexos_acima_do_total_permitido_sao_recusados(): void
    {
        $pdf = "%PDF-1.4\n".str_repeat(' ', 8 * 1024 * 1024);
        $this->post($this->url(), $this->dados(['arquivos' => [UploadedFile::fake()->createWithContent('um.pdf', $pdf),
            UploadedFile::fake()->createWithContent('dois.pdf', $pdf)]]), ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertDatabaseCount('emails_contato', 0);
    }
    public function test_falha_incerta_preserva_recibo_e_nao_repete_smtp(): void
    {
        $tentativas = 0;
        Event::listen(MessageSending::class, function () use (&$tentativas) { $tentativas++; throw new \RuntimeException('Falha simulada'); });
        $dados = $this->dados();
        $this->postJson($this->url(), $dados)->assertOk()->assertJsonPath('estado', 'revisar');
        $this->postJson($this->url(), $dados)->assertOk()->assertJsonPath('estado', 'revisar');
        $this->getJson($this->url().'/'.$dados['chave_envio'])->assertOk()->assertJsonPath('estado', 'revisar');
        $this->assertSame(1, $tentativas); $this->assertDatabaseCount('emails_contato', 1);
    }
    public function test_email_cancelado_por_evento_nao_e_marcado_como_enviado(): void
    {
        Event::listen(MessageSending::class, fn () => false);
        $this->postJson($this->url(), $this->dados())->assertOk()->assertJsonPath('estado', 'falhou'); $this->assertCount(0, $this->mensagens());
    }
    public function test_cota_por_hora_e_aplicada_sem_impedir_consulta(): void
    {
        config(['comunicacao.emails_por_hora_aluno' => 1]); $dados = $this->dados();
        $this->postJson($this->url(), $dados)->assertOk()->assertJsonPath('estado', 'enviado');
        $this->postJson($this->url(), $this->dados())->assertStatus(429);
        $this->getJson($this->url().'/'.$dados['chave_envio'])->assertOk()->assertJsonPath('estado', 'enviado');
        $this->assertCount(1, $this->mensagens());
    }
}
