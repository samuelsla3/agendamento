<?php

namespace App\Http\Middleware;

use App\Services\SegurancaPsicologa;
use Closure;
use Illuminate\Http\Request;

class SessaoPsicologa
{
    public function handle(Request $request, Closure $next)
    {
        $usuario = $request->user();
        if (!$usuario) return $next($request);
        if ($usuario->tipo !== 'psicologa') {
            if ($request->session()->has('psicologa_autenticacao_versao')) return $this->encerrar($request);
            return $next($request);
        }
        if (!SegurancaPsicologa::sessaoAtual($request, $usuario)) return $this->encerrar($request);
        if (!SegurancaPsicologa::configurada($usuario)
            && !$request->routeIs('seguranca.primeiro', 'seguranca.primeiro.salvar', 'logout')) {
            if ($request->expectsJson()) return response()->json(['status' => 'error',
                'message' => 'Conclua o cadastro das senhas antes de acessar o sistema.'], 403);
            return redirect()->route('seguranca.primeiro');
        }
        return $next($request);
    }

    private function encerrar(Request $request)
    {
        SegurancaPsicologa::encerrar($request);
        if ($request->expectsJson()) return response()->json(['status' => 'error',
            'message' => 'Sua sessão precisa ser renovada. Faça login novamente.'], 401);
        return redirect()->route('login')->withErrors(['matricula' => 'Faça login novamente. A sessão foi encerrada ou a autorização mudou.']);
    }
}
