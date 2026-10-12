<?php

namespace App\Http\Controllers;

use App\Services\SegurancaPsicologa;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SugestaoController extends Controller
{
    public function enviar(Request $request)
    {
        abort_unless(in_array($request->user()?->tipo, ['estudante', 'aluno'], true), 403);
        abort_unless(Schema::hasTable('sugestoes_alunos'), 503, 'O envio de sugestões ainda não está disponível.');
        $texto = $request->input('sugestao');
        if (is_string($texto)) $texto = trim($texto);
        $validacao = Validator::make(['sugestao' => $texto, 'chave_envio' => $request->input('chave_envio')], [
            'sugestao' => ['bail', 'required', 'string', 'min:3', 'max:10000'],
            'chave_envio' => ['required', 'uuid'],
        ], [
            'sugestao.required' => 'Escreva sua sugestão antes de enviar.',
            'sugestao.string' => 'Escreva sua sugestão em texto.',
            'sugestao.min' => 'Escreva pelo menos 3 caracteres.',
            'sugestao.max' => 'A sugestão pode ter até 10.000 caracteres.',
            'chave_envio.required' => 'Atualize a página antes de enviar.',
            'chave_envio.uuid' => 'Atualize a página antes de enviar.',
        ]);
        if ($validacao->fails()) {
            if ($request->expectsJson()) return response()->json([
                'message' => $validacao->errors()->first(), 'errors' => $validacao->errors(),
            ], 422);
            // Não coloca o texto da sugestão na sessão ou em registros vinculados à conta.
            return redirect()->route('agenda.index')->withErrors($validacao, 'sugestoes');
        }
        $dados = $validacao->validated();
        $chave = hash_hmac('sha256', 'sugestao|' . strtolower($dados['chave_envio']), (string) config('app.key'));
        $anterior = DB::table('sugestoes_alunos')->where('chave_envio', $chave)->first();
        if ($anterior) return $this->repeticao($request, $anterior, $dados['sugestao']);

        // Só uma chave opaca temporária de limite. Não é salva junto da sugestão.
        $limite = 'sugestoes:' . hash_hmac('sha256', 'limite|' . $request->user()->getAuthIdentifier(), (string) config('app.key'));
        $resposta = RateLimiter::attempt($limite, 5, function () use ($request, $chave, $dados) {
            try {
                DB::table('sugestoes_alunos')->insert([
                    'chave_envio' => $chave,
                    'conteudo' => Crypt::encryptString($dados['sugestao']),
                    'data_envio' => now('America/Sao_Paulo')->toDateString(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Duas requisições simultâneas da mesma tentativa gravam uma única sugestão.
                $anterior = DB::table('sugestoes_alunos')->where('chave_envio', $chave)->first();
                if (!$anterior) throw $e;
                return $this->repeticao($request, $anterior, $dados['sugestao']);
            }
            return $this->sucesso($request);
        }, 3600);
        if ($resposta !== false) return $resposta;
        $mensagem = 'Você atingiu o limite de sugestões neste intervalo. Tente novamente mais tarde.';
        if ($request->expectsJson()) return response()->json(['message' => $mensagem], 429)
            ->header('Retry-After', max(1, RateLimiter::availableIn($limite)));
        return redirect()->route('agenda.index')->withErrors(['sugestao' => $mensagem], 'sugestoes');
    }

    private function repeticao(Request $request, object $anterior, string $texto)
    {
        if (!hash_equals(Crypt::decryptString($anterior->conteudo), $texto)) {
            $mensagem = 'Esta tentativa já enviou outro texto. Confira sua sugestão e clique novamente para enviar uma nova.';
            if ($request->expectsJson()) return response()->json([
                'message' => $mensagem, 'nova_chave' => (string) Str::uuid(),
            ], 409);
            return redirect()->route('agenda.index')->withErrors(['sugestao' => $mensagem], 'sugestoes');
        }
        return $this->sucesso($request);
    }

    private function sucesso(Request $request)
    {
        $mensagem = 'Sua sugestão foi enviada de forma anônima. Obrigado por ajudar a melhorar o sistema!';
        if ($request->expectsJson()) return response()->json([
            'status' => 'success', 'message' => $mensagem, 'nova_chave' => (string) Str::uuid(),
        ]);
        return redirect()->route('agenda.index')->with('sugestao_sucesso', $mensagem);
    }

    public function listar(Request $request)
    {
        abort_unless($request->user() && SegurancaPsicologa::sessaoAtual($request, $request->user()), 403);
        abort_unless(Schema::hasTable('sugestoes_alunos'), 503, 'Execute a migration de sugestões antes de consultar.');
        $request->validate(['pagina' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $pagina = DB::table('sugestoes_alunos')->orderByDesc('id')
            ->paginate(10, ['conteudo', 'data_envio'], 'pagina');
        $sugestoes = $pagina->getCollection()->map(function (object $registro): array {
            try { $texto = Crypt::decryptString($registro->conteudo); }
            catch (DecryptException $e) { $texto = 'Não foi possível ler esta sugestão. Procure o responsável técnico.'; }
            return ['texto' => $texto, 'dia' => \Carbon\Carbon::parse($registro->data_envio)->format('d/m/Y')];
        });
        return response()->json(['sugestoes' => $sugestoes, 'total' => $pagina->total(),
            'pagina' => $pagina->currentPage(), 'ultima_pagina' => $pagina->lastPage()])
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }
}
