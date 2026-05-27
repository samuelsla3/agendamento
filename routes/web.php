<?php

use App\Http\Controllers\CalendarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PsicologaController;

Route::get('/', [CalendarioController::class, 'index'])->name('agenda.index');

Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'logar']); // Removido ->name('login') duplicado
Route::get('/registro', [AuthController::class, 'mostrarRegistro'])->name('register');
Route::post('/registro', [AuthController::class, 'registrar'])->name('registrar');


Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'redirecionarUsuario'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/agendar', [CalendarioController::class, 'processarAcao'])->name('agenda.agendar');
    Route::post('/cancelar', [CalendarioController::class, 'processarAcao'])->name('agenda.cancelar');

    Route::middleware(['verificar.psicologa'])->group(function () {
        
        Route::get('/agenda', [PsicologaController::class, 'index'])->name('psicologa.index');
        
        Route::get('/agenda/eventos', [PsicologaController::class, 'listarEventos'])->name('agenda.eventos');
        
        Route::post('/agenda/acao', [PsicologaController::class, 'processarAcao'])->name('agenda.acao');
        
        Route::post('/agenda/relatorio', [PsicologaController::class, 'gerarRelatorio'])->name('agenda.relatorio');
    });
    
});