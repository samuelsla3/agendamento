<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LimitarLogin
{
    public function handle(Request $request, Closure $next)
    {
        $matricula = $request->input('matricula');
        if (!is_string($matricula)) return $next($request);
        $chave = 'entrada:' . hash_hmac('sha256', trim($matricula) . '|' . $request->ip(), config('app.key'));
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            return back()->withErrors(['matricula' => 'Limite de tentativas excedido. Aguarde alguns minutos para tentar novamente.'])
                ->withInput($request->only('matricula'));
        }
        $response = $next($request);
        if (auth()->check()) RateLimiter::clear($chave);
        else RateLimiter::hit($chave, 900);
        return $response;
    }
}
