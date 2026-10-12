<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\SessaoPsicologa::class]);
        $middleware->alias([
            'verificar.psicologa' => \App\Http\Middleware\VerificarPsicologa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->dontFlash([
        'anotacoes',
        'senha', 'password', 'password_confirmation', 'senha_atual', 'senha_local',
        'senha_local_confirmation', 'senha_prontuario', 'senha_prontuario_confirmation',
        'nova_senha', 'nova_senha_confirmation', 'codigo',
    ]);

    $exceptions->render(function (
        \Illuminate\Http\Exceptions\ThrottleRequestsException $e,
        \Illuminate\Http\Request $request
    ) {
        // Personaliza somente o bloqueio da senha do prontuário.
        if (!$request->routeIs('prontuarios.validar-senha')) {
            return null;
        }

        $segundos = max(
            1,
            (int) ($e->getHeaders()['Retry-After'] ?? 60)
        );

        $minutos = (int) ceil($segundos / 60);
        $unidade = $minutos === 1 ? 'minuto' : 'minutos';

        return redirect()
            ->route('psicologa.index')
            ->with('pedir_senha', true)
            ->withErrors([
                'senha' => "Limite de tentativas excedido. Tente novamente em {$minutos} {$unidade}.",
            ]);
    });
})->create();
