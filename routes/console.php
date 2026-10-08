<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; #importar o Schedule

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

#aponta para a $signature, e configura a automoção
Schedule::command('atendimentos:enviar-lembretes')
    ->everyMinute()
    ->when(fn () => now(config('app.timezone'))->format('H:i')
        === \App\Models\ConfiguracaoSistema::horarioLembretes())
    ->withoutOverlapping(60);