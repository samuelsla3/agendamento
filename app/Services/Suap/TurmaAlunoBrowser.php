<?php

namespace App\Services\Suap;

use RuntimeException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class TurmaAlunoBrowser extends HttpBrowser
{
    protected function doRequest(object $request): Response
    {
        if (!$request instanceof Request || !self::urlPermitida($request->getUri())) {
            throw new RuntimeException('Destino inesperado na consulta da turma.');
        }

        return parent::doRequest($request);
    }

    public static function urlPermitida(string $url): bool
    {
        $partes = parse_url($url);

        return is_array($partes)
            && ($partes['scheme'] ?? '') === 'https'
            && strtolower($partes['host'] ?? '') === 'suap.ifba.edu.br'
            && ($partes['port'] ?? 443) === 443
            && !isset($partes['user'])
            && !isset($partes['pass']);
    }
}
