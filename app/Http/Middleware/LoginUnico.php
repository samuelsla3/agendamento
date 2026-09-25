<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoginUnico
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }
        $request->validate(['_operation_id' => 'required|uuid', 'matricula' => 'required|string', 'senha' => 'required|string']);
        // Nunca usar senha como parte de chave, log, cache ou fila.
        $cache = Cache::store('database');
        $lock = $cache->lock('login-ativo:'.hash('sha256', $request->session()->getId()), 180);
        if (!$lock->get()) {
            return $this->ocupado('Já existe uma tentativa de entrada em andamento. Aguarde a primeira resposta.');
        }
        try {
            $tentativa = 'login-tentativa:'.hash('sha256', $request->input('_operation_id'));
            if (!$cache->add($tentativa, true, now()->addMinutes(10))) {
                return $this->ocupado('Esta tentativa já foi processada. Atualize a página para entrar novamente.');
            }
            return $next($request);
        } finally {
            $lock->release();
        }
    }
    private function ocupado(string $mensagem)
    {
        return response()->view('agendamentos.status_cancelamento', [
            'titulo' => 'Entrada em processamento', 'mensagem' => $mensagem,
        ], 409)->header('Cache-Control', 'no-store');
    }
}
