<?php

namespace App\Providers;

use App\Models\ConfiguracaoSistema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['login', 'index', 'agenda'], function (\Illuminate\View\View $view) {
            $view->with('avisoPublico', ConfiguracaoSistema::avisoPublico());
        });
    }
}