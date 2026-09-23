<?php

namespace Tests\Unit;

use App\Services\Suap\Browser;
use App\Services\Suap\EmailPessoalScraper;
use App\Services\Suap\EmailPessoalService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\TestCase;

class EmailSuapTest extends TestCase
{
    #[DataProvider('paginas')]
    public function test_extrai_apenas_o_campo_pessoal(string $html, ?string $esperado): void
    {
        $this->assertSame($esperado, (new EmailPessoalScraper())->extrair(new Crawler($html)));
    }

    public static function paginas(): array
    {
        return [
            ['<table class="info"><tr><td>E-mail Pessoal</td><td>aluno@example.com</td></tr></table>', 'aluno@example.com'],
            ['<table class="info"><tr><th>E-MAIL PESSOAL:</th><td><a href="mailto:aluno@example.com">aluno@example.com</a></td></tr></table>', 'aluno@example.com'],
            ['<table class="info"><tr><td>E-mail&nbsp;Pessoal</td><td>aluno@example.com</td></tr></table>', 'aluno@example.com'],
            ['<table class="info"><tr><td>E-mail Institucional</td><td>123@ifba.edu.br</td><td>E-mail Pessoal</td><td>-</td></tr></table>', null],
            ['<table class="info"><tr><td>E-mail Pessoal</td><td>email invalido</td></tr></table>', null],
            ['<form><input name="password"></form>', null],
        ];
    }

    #[DataProvider('prioridades')]
    public function test_prioridade_e_preservacao(array $dados, ?string $scraping, ?string $salvo, string $esperado, bool $consultar): void
    {
        $service = $this->getMockBuilder(EmailPessoalService::class)->onlyMethods(['buscar'])->getMock();
        $service->expects($consultar ? $this->once() : $this->never())
            ->method('buscar')->willReturn($scraping);

        $this->assertSame($esperado, $service->resolver($dados, '123', 'senha-ficticia', $salvo));
    }

    public static function prioridades(): array
    {
        return [
            [['email_pessoal' => '', 'email_secundario' => ' pessoal@example.com '], null, null, 'pessoal@example.com', false],
            [['email' => '123@ifba.edu.br'], 'pessoal@example.com', null, 'pessoal@example.com', true],
            [['email' => 'outro@example.com'], 'pessoal@example.com', null, 'pessoal@example.com', true],
            [['email' => 'api@example.com'], null, null, 'api@example.com', true],
            [['email' => '123@ifba.edu.br'], null, 'salvo@example.com', 'salvo@example.com', true],
            [['email' => ''], null, 'salvo@example.com', 'salvo@example.com', true],
            [['email' => 'invalido'], null, null, '123@ifba.edu.br', true],
        ];
    }

    public function test_fluxo_web_envia_csrf_e_cookie_e_consulta_a_aba_correta(): void
    {
        $requisicoes = [];
        $client = new MockHttpClient(function ($method, $url, $options) use (&$requisicoes) {
            $requisicoes[] = [$method, $url, $options];
            $headers = ['content-type: text/html; charset=UTF-8'];
            return match (count($requisicoes)) {
                1 => new MockResponse('<form method="post" action="/accounts/login/"><input name="csrfmiddlewaretoken" value="csrf-ficticio"><input name="username"><input type="password" name="password"><button>Acessar</button></form>', [
                    'response_headers' => [...$headers, 'set-cookie: csrftoken=csrf-ficticio; Path=/; Secure'],
                ]),
                2 => new MockResponse('', ['http_code' => 302, 'response_headers' => [
                    ...$headers, 'location: /', 'set-cookie: sessionid=sessao-ficticia; Path=/; Secure',
                ]]),
                3 => new MockResponse('<html><body>Início</body></html>', ['response_headers' => $headers]),
                4 => new MockResponse('<table class="info"><tr><td>E-mail Pessoal</td><td>aluno@example.com</td></tr></table>', ['response_headers' => $headers]),
                default => throw new \RuntimeException('Requisição inesperada.'),
            };
        });
        $browser = new Browser($client);
        $service = $this->serviceComBrowser($browser);

        $this->assertSame('aluno@example.com', $service->buscar('123', 'senha-ficticia'));
        $this->assertCount(4, $requisicoes);
        $this->assertSame('POST', $requisicoes[1][0]);
        $body = $requisicoes[1][2]['body'];
        if (is_string($body)) {
            parse_str($body, $body);
        }
        $this->assertSame('csrf-ficticio', $body['csrfmiddlewaretoken']);
        $this->assertSame('123', $body['username']);
        $this->assertSame('senha-ficticia', $body['password']);
        $this->assertStringContainsString('csrftoken=csrf-ficticio', implode("\n", $requisicoes[1][2]['headers']));
        $this->assertSame('https://suap.ifba.edu.br/edu/aluno/123/?tab=dados_pessoais', $requisicoes[3][1]);
        $this->assertStringContainsString('sessionid=sessao-ficticia', implode("\n", $requisicoes[3][2]['headers']));
        $this->assertCount(0, $browser->getCookieJar()->all());
    }

    public function test_formulario_externo_nao_recebe_a_senha(): void
    {
        $contador = 0;
        $browser = new Browser(new MockHttpClient(function () use (&$contador) {
            $contador++;
            return new MockResponse('<form method="post" action="https://example.com/login"><input name="username"><input name="password"></form>', [
                'response_headers' => ['content-type: text/html'],
            ]);
        }));

        $this->assertNull($this->serviceComBrowser($browser)->buscar('123', 'senha-ficticia'));
        $this->assertSame(1, $contador);
    }

    public function test_erro_de_rede_nao_interrompe_fallback(): void
    {
        $browser = new Browser(new MockHttpClient(fn () => throw new \RuntimeException('Falha simulada.')));
        $service = $this->serviceComBrowser($browser);
        $this->assertSame('123@ifba.edu.br', $service->resolver(['email' => ''], '123', 'senha-ficticia'));
    }

    public function test_retorno_a_tela_de_login_nao_e_sucesso(): void
    {
        $html = '<form method="post" action="/accounts/login/"><input name="username"><input name="password"></form>';
        $browser = new Browser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['content-type: text/html']]),
            new MockResponse($html, ['response_headers' => ['content-type: text/html']]),
        ]));
        $this->assertNull($this->serviceComBrowser($browser)->buscar('123', 'senha-ficticia'));
    }

    private function serviceComBrowser(Browser $browser): EmailPessoalService
    {
        return new class($browser) extends EmailPessoalService {
            public function __construct(private Browser $browser) {}

            protected function criarBrowser(): Browser
            {
                return $this->browser;
            }
        };
    }
}
