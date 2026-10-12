<?php

namespace App\Services;

use App\Models\ConfiguracaoSistema;

class InformacoesAtendimento
{
    public static function complementarEmail(string $html): string
    {
        // Os dados são consultados no momento do envio, inclusive após uma mudança de sala.
        $informacoes = ConfiguracaoSistema::informacoesAtendimento();
        unset($informacoes['resumo']);
        if ($informacoes === []) {
            return $html;
        }

        $rodape = view('emails.partials.informacoes_atendimento', compact('informacoes'))->render();
        if (preg_match('/<\/body\s*>/i', $html)) {
            return preg_replace_callback('/<\/body\s*>/i', fn ($trecho) => $rodape . $trecho[0], $html, 1);
        }

        return $html . $rodape;
    }
}
