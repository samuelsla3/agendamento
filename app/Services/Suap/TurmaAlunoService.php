<?php

namespace App\Services\Suap;

use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Throwable;

class TurmaAlunoService
{
    private const BASE_URL = 'https://suap.ifba.edu.br';

    public function resolver(array $dados, string $matricula, string $senha, ?string $turmaSalva = null): ?string
    {
        $vinculo = is_array($dados['vinculo'] ?? null) ? $dados['vinculo'] : [];

        foreach ([$vinculo['turma_atual'] ?? null, $dados['turma_atual'] ?? null, $dados['turma'] ?? null] as $valor) {
            if (($codigo = TurmaAlunoScraper::normalizar($valor)) !== null) {
                return $codigo;
            }
        }

        // A ausência de turma é complementar: não impede a autenticação.
        return $this->buscar($matricula, $senha) ?? $turmaSalva;
    }

    public function buscar(string $matricula, string $senha): ?string
    {
        $browser = null;

        try {
            // Cookies exclusivos desta consulta; a senha não é persistida.
            $browser = $this->criarBrowser();
            $pagina = $browser->request('GET', self::BASE_URL . '/accounts/login/');
            if ($browser->getResponse()->getStatusCode() !== 200) {
                return $this->falha('pagina_login_indisponivel');
            }

            $formularios = $pagina->filter('form')->reduce(fn (Crawler $form) =>
                $form->filter('input[name="username"]')->count() > 0
                && $form->filter('input[name="password"]')->count() > 0
            );
            if ($formularios->count() !== 1) {
                return $this->falha('formulario_login_nao_encontrado');
            }

            // Preserva CSRF e cookies recebidos do próprio SUAP.
            $form = $formularios->form(['username' => $matricula, 'password' => $senha]);
            if (!TurmaAlunoBrowser::urlPermitida($form->getUri()) || strtoupper($form->getMethod()) !== 'POST') {
                return $this->falha('formulario_login_inesperado');
            }

            $pagina = $browser->submit($form);
            if ($browser->getResponse()->getStatusCode() !== 200
                || $pagina->filter('input[name="password"]')->count() > 0
                || str_starts_with(parse_url($browser->getRequest()->getUri(), PHP_URL_PATH) ?? '', '/accounts/login')) {
                return $this->falha('login_web_nao_confirmado');
            }

            $path = '/edu/aluno/' . rawurlencode($matricula) . '/';
            $pagina = $browser->request('GET', self::BASE_URL . $path);
            if ($browser->getResponse()->getStatusCode() !== 200
                || (parse_url($browser->getRequest()->getUri(), PHP_URL_PATH) ?? '') !== $path
                || $pagina->filter('input[name="password"]')->count() > 0) {
                return $this->falha('pagina_aluno_indisponivel');
            }

            $codigo = (new TurmaAlunoScraper())->codigoAtual($pagina);
            if ($codigo === null) {
                return $this->falha('turma_ausente_ou_ambigua');
            }

            Log::info('SUAP turma: consulta concluida.');
            return $codigo;
        } catch (Throwable $e) {
            // Não registrar HTML, senha, token, matrícula ou texto da exceção.
            Log::warning('SUAP turma: consulta indisponivel.', ['tipo' => get_class($e)]);
            return null;
        } finally {
            if ($browser !== null) {
                $browser->restart();
            }
        }
    }

    protected function criarBrowser(): TurmaAlunoBrowser
    {
        $browser = new TurmaAlunoBrowser(HttpClient::create([
            'timeout' => 3,
            'max_duration' => 5,
            'headers' => ['User-Agent' => 'Agendamento-IFBA/1.0'],
        ]));
        $browser->setMaxRedirects(3);
        return $browser;
    }

    private function falha(string $motivo): ?string
    {
        Log::info('SUAP turma: mantendo valor anterior.', ['motivo' => $motivo]);
        return null;
    }
}
