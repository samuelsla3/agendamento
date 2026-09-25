<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class SuapService
{
    private string $baseUrl = 'https://suap.ifba.edu.br/api/v2';

    public function autenticar(string $matricula, string $senha): ?string
    {
        try {
            $response = Http::asJson()
                ->connectTimeout(3)->timeout(5)
                ->post($this->baseUrl . '/autenticacao/token/', [
                    'username' => $matricula,
                    'password' => $senha,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json()['token'] ?? null;
        } catch (ConnectionException $e) {
            return null;
        }
    }

    public function meusDados(string $jwt): ?array
    {
        try {
            $response = Http::connectTimeout(3)->timeout(5)->withHeaders([
                'Authorization' => 'JWT ' . $jwt,
                'Content-Type'  => 'application/json',
            ])->get($this->baseUrl . '/minhas-informacoes/meus-dados/');

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        } catch (ConnectionException $e) {
            return null;
        }
    }

    /**
     * Busca a turma atual do aluno nos dados acadêmicos do SUAP
     */
    /**
 * Busca a turma atual do aluno nos dados acadêmicos do SUAP
 */
public function obterTurmaAtual(string $jwt): ?string
{
    try {
        $anoAtual = date('Y');

        // 1. Tenta buscar no boletim passando o ano letivo atual
        $response = Http::connectTimeout(3)->timeout(5)
            ->withHeaders([
                'Authorization' => 'JWT ' . $jwt,
                'Content-Type'  => 'application/json',
            ])
            ->get($this->baseUrl . "/minhas-informacoes/boletim-atividades/{$anoAtual}/1/");

        if ($response->successful()) {
            $dados = $response->json();
            if (!empty($dados['turma'])) {
                return $dados['turma'];
            }
        }

        // 2. Se falhar, tenta o endpoint geral de vínculos do aluno
        $responseVinculos = Http::connectTimeout(3)->timeout(5)
            ->withHeaders([
                'Authorization' => 'JWT ' . $jwt,
                'Content-Type'  => 'application/json',
            ])
            ->get($this->baseUrl . '/minhas-informacoes/meus-vinculos/');

        if ($responseVinculos->successful()) {
            $vinculos = $responseVinculos->json();
            foreach ($vinculos as $vinculo) {
                if (!empty($vinculo['turma_atual'])) {
                    return $vinculo['turma_atual'];
                }
            }
        }
    } catch (\Exception $e) {
        Log::warning("Erro ao buscar turma no SUAP: " . $e->getMessage());
    }

    return null;
}
}
