<?php

namespace App\Http\Middleware;

use App\Services\SegurancaPsicologa;
use Closure;
use Illuminate\Http\Request;

class VerificarPsicologa
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && SegurancaPsicologa::sessaoAtual($request, $request->user()), 403,
            'Acesso restrito à psicóloga autorizada.');
        return $next($request);
    }
}
