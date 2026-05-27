<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPsicologa
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(\Illuminate\Http\Request $request, \Closure $next)
    {
        if (session('usuario_tipo') !== 'psicologa') {
            return redirect()->route('login')->with('erro', 'Acesso negado.');
        }

        return $next($request);
    }
}
