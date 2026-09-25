<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequisicaoUnica
{
    public function handle(Request $request, Closure $next, string $grupo)
    {
        abort_unless($request->user(), 401);
        if ($grupo === 'prontuario') {
            abort_unless($request->session()->get('prontuario_autorizado'), 403,
                'Valide sua senha para acessar o prontuário.');
        }
        $request->validate(['_operation_id' => 'required|uuid']);
        $chave = hash_hmac('sha256', $request->user()->getAuthIdentifier().'|'
            .$request->method().'|'.$request->path().'|'.$request->input('_operation_id'), config('app.key'));
        $dados = $this->ordenar($request->except(['_token', '_operation_id']));
        $assinatura = hash_hmac('sha256', json_encode($dados, JSON_THROW_ON_ERROR), config('app.key'));

        return DB::transaction(function () use ($request, $next, $grupo, $chave, $assinatura) {
            // Agenda do campus: serializa somente as escritas curtas, nunca SMTP/SUAP.
            $trava = DB::table('travas_operacoes')->where('nome', $grupo)->lockForUpdate()->first();
            abort_unless($trava, 503, 'Execute as migrations antes de usar o sistema.');
            $anterior = DB::table('operacoes_http')->where('chave', $chave)->first();
            if ($anterior) {
                abort_unless(hash_equals($anterior->assinatura, $assinatura), 409,
                    'Esta tentativa já foi usada com outros dados. Atualize a página.');
                $resposta = json_decode($anterior->resposta, true, 512, JSON_THROW_ON_ERROR);
                if ($resposta['tipo'] === 'redirect') {
                    return redirect($resposta['destino'])->with('sucesso', 'Esta operação já foi concluída.')
                        ->header('X-Operation-Replayed', 'true');
                }
                return response()->json($resposta['dados'], $resposta['status'])
                    ->header('X-Operation-Replayed', 'true');
            }
            DB::table('operacoes_http')->insert(['chave' => $chave, 'assinatura' => $assinatura, 'created_at' => now()]);
            $response = $next($request);
            $json = $response instanceof \Illuminate\Http\JsonResponse ? $response->getData(true) : null;
            if (($response->exception ?? null) !== null || $response->getStatusCode() >= 400 || (is_array($json) && ($json['status'] ?? null) === 'error')) {
                // Rollback também do identificador: erros de validação podem ser corrigidos.
                throw new HttpResponseException($response);
            }
            if ($response->isRedirection()) {
                $salvar = ['tipo' => 'redirect', 'destino' => $response->headers->get('Location')];
            } elseif (is_array($json)) {
                // Nunca armazenar HTML de prontuário ou corpo integral da requisição.
                $salvar = ['tipo' => 'json', 'status' => $response->getStatusCode(),
                    'dados' => array_intersect_key($json, array_flip(['status', 'message']))];
            } else {
                throw new \LogicException('Operação protegida deve retornar JSON ou redirecionamento.');
            }
            DB::table('operacoes_http')->where('chave', $chave)->update([
                'resposta' => json_encode($salvar, JSON_THROW_ON_ERROR),
            ]);
            return $response;
        }, 3);
    }
    private function ordenar(array $dados): array
    {
        ksort($dados);
        foreach ($dados as &$valor) {
            if (is_array($valor)) { $valor = $this->ordenar($valor); }
        }
        return $dados;
    }
}
