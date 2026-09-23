<?php

namespace App\Services\Suap;

use Symfony\Component\DomCrawler\Crawler;

class EmailPessoalScraper
{
    public function extrair(Crawler $pagina): ?string
    {
        foreach ($pagina->filter('table.info tr') as $tr) {
            $celulas = (new Crawler($tr))->children('th, td');

            for ($i = 0; $i + 1 < $celulas->count(); $i++) {
                $rotulo = preg_replace('/[\s\x{00A0}]+/u', ' ', $celulas->eq($i)->text());
                $rotulo = mb_strtolower(rtrim(trim($rotulo), ': '), 'UTF-8');

                if (!in_array($rotulo, ['e-mail pessoal', 'email pessoal'], true)) {
                    continue;
                }

                $valor = trim($celulas->eq($i + 1)->text());
                if (filter_var($valor, FILTER_VALIDATE_EMAIL) !== false) {
                    return $valor;
                }
            }
        }

        return null;
    }
}
