<?php

namespace App\Services\Suap;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class EmailPessoalService
{
    private const BASE_URL = 'https://suap.ifba.edu.br';

    public function resolver(array $dados, string $matricula, string $senha, ?string $emailSalvo = null): string
    {
        // Campos explicitamente pessoais, quando disponíveis na API.
        foreach ([
            $dados['email_pessoal'] ?? null,
            $dados['email_secundario'] ?? null,
            $dados['vinculo']['email_pessoal'] ?? null,
        ] as $candidato) {
            if ($email = $this->validar($candidato)) {
                return $email;
            }
        }

        // O campo genérico "email" pode ser institucional: não pular o scraper.
        if ($email = $this->buscar($matricula, $senha)) {
            return $email;
        }

        $emailApi = $this->validar($dados['email'] ?? null);
        $emailSalvo = $this->validar($emailSalvo);

        // Sem campo próprio de origem no banco, endereço externo é uma heurística.
        foreach ([$emailApi, $emailSalvo] as $candidato) {
            if ($candidato !== null && !$this->institucional($candidato)) {
                return $candidato;
            }
        }

        return $emailApi ?? $emailSalvo ?? ($matricula . '@ifba.edu.br');
    }

    public function buscar(string $matricula, string $senha): ?string
    {
        $browser = null;

        try {
            // Instância por tentativa: cookies nunca são compartilhados entre alunos.
            $browser = $this->criarBrowser();
            $pagina = $browser->request('GET', self::BASE_URL . '/accounts/login/');

            if ($browser->getResponse()->getStatusCode() !== 200) {
                return $this->falha('pagina_login_indisponivel');
            }

            $formulario = $pagina->filter('form')->reduce(fn (Crawler $form) =>
                $form->filter('input[name="username"]')->count() > 0
                && $form->filter('input[name="password"]')->count() > 0
            );

            if ($formulario->count() !== 1) {
                return $this->falha('formulario_login_nao_encontrado');
            }

            // Mantém campos ocultos (inclusive CSRF) e os cookies recebidos no GET.
            $form = $formulario->form(['username' => $matricula, 'password' => $senha]);
            if (!Browser::urlPermitida($form->getUri()) || strtoupper($form->getMethod()) !== 'POST') {
                return $this->falha('formulario_login_inesperado');
            }

            $pagina = $browser->submit($form);
            if ($browser->getResponse()->getStatusCode() !== 200
                || $pagina->filter('input[name="password"]')->count() > 0
                || str_starts_with(parse_url($browser->getRequest()->getUri(), PHP_URL_PATH) ?? '', '/accounts/login')) {
                return $this->falha('login_web_nao_confirmado');
            }

            $path = '/edu/aluno/' . rawurlencode($matricula) . '/';
            $pagina = $browser->request('GET', self::BASE_URL . $path . '?tab=dados_pessoais');

            if ($browser->getResponse()->getStatusCode() !== 200
                || (parse_url($browser->getRequest()->getUri(), PHP_URL_PATH) ?? '') !== $path
                || $pagina->filter('input[name="password"]')->count() > 0) {
                return $this->falha('pagina_aluno_indisponivel');
            }

            $email = (new EmailPessoalScraper())->extrair($pagina);
            if ($email === null) {
                return $this->falha('campo_pessoal_ausente_ou_invalido');
            }

            Log::info('SUAP email pessoal: extraido com sucesso.');

            return $email;
        } catch (Throwable $e) {
            // Não registrar mensagem da exceção, HTML, credenciais nem dados pessoais.
            Log::warning('SUAP email pessoal: consulta indisponivel.', ['tipo' => get_class($e)]);

            return null;
        } finally {
            if ($browser !== null) {
                $browser->restart();
            }
        }
    }

    protected function criarBrowser(): Browser
    {
        $browser = new Browser(HttpClient::create([
            'timeout' => 5,
            'max_duration' => 8,
            'headers' => ['User-Agent' => 'Agendamento-IFBA/1.0'],
        ]));
        $browser->setMaxRedirects(3);

        return $browser;
    }

    private function validar(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false ? $valor : null;
    }

    private function institucional(string $email): bool
    {
        $dominio = strtolower(substr(strrchr($email, '@'), 1));

        return $dominio === 'ifba.edu.br' || str_ends_with($dominio, '.ifba.edu.br');
    }

    private function falha(string $motivo): ?string
    {
        Log::info('SUAP email pessoal: usando alternativa.', ['motivo' => $motivo]);

        return null;
    }
}
