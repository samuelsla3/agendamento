<?php

namespace App\Services\Suap;

use Symfony\Component\DomCrawler\Crawler;

class TurmaAlunoScraper
{
    public static function normalizar(mixed $valor): ?string
    {
        if (!is_string($valor)) {
            return null;
        }

        $codigo = trim($valor);

        // Código, não nome de curso nem estimativa como "2023 - INF".
        if ($codigo === '' || strlen($codigo) > 50
            || !preg_match('/\A[A-Za-z0-9][A-Za-z0-9._\/-]*\z/D', $codigo)
            || !preg_match('/\d/', $codigo)) {
            return null;
        }

        return $codigo;
    }

    public function codigoAtual(Crawler $pagina): ?string
    {
        $codigos = [];

        // Estrutura utilizada pelo outro grupo: turma na 3ª célula e
        // situação na 4ª. Ignora cabeçalhos e linhas incompletas.
        foreach ($pagina->filter('table tbody tr') as $tr) {
            $celulas = (new Crawler($tr))->filter('td');
            if ($celulas->count() < 4) {
                continue;
            }

            $situacao = trim($celulas->eq(3)->text());
            if (strcasecmp($situacao, 'Matriculado') !== 0) {
                continue;
            }

            $codigo = self::normalizar($celulas->eq(2)->text());
            if ($codigo !== null) {
                $codigos[$codigo] = true;
            }
        }

        // Não escolhe arbitrariamente entre duas matrículas ativas distintas.
        return count($codigos) === 1 ? (string) array_key_first($codigos) : null;
    }
}
