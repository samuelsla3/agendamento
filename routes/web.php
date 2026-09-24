<?php

use App\Http\Controllers\CalendarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PsicologaController;
use App\Http\Controllers\ProntuarioController;

// Rota principal da agenda
Route::get('/', [CalendarioController::class, 'index'])->name('agenda.index');

// Rotas de Autenticação (SUAP + Local)
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'logar'])->name('login.post');

/* 
 * As rotas de registro foram desativadas pois o aluno 
 * autentica diretamente com as credenciais do SUAP.
 *
 * Route::get('/registro', [AuthController::class, 'mostrarRegistro'])->name('register');
 * Route::post('/registro', [AuthController::class, 'registrar'])->name('registrar');
 */

// Cancelamento direto via link assinado do e-mail
Route::get(
    '/cancelar-confirmar/{id}',
    [CalendarioController::class, 'exibirTelaCancelamento']
)
    ->name('agendamento.cancelarDirect')
    ->middleware('signed');

Route::post(
    '/cancelar-executar/{id}',
    [CalendarioController::class, 'executarCancelamentoDireto']
)
    ->name('agendamento.cancelar.executar')
    ->middleware('signed');

// Rotas protegidas por login
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'redirecionarUsuario'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/agendar', [CalendarioController::class, 'processarAcao'])->name('agenda.agendar');
    Route::post('/cancelar', [CalendarioController::class, 'processarAcao'])->name('agenda.cancelar');

    // Área exclusiva da Psicóloga
    Route::middleware(['verificar.psicologa'])->group(function () {
        
        Route::get('/agenda', [PsicologaController::class, 'index'])->name('psicologa.index');
        Route::get('/agenda/eventos', [PsicologaController::class, 'listarEventos'])->name('agenda.eventos');
        Route::post('/agenda/acao', [PsicologaController::class, 'processarAcao'])->name('agenda.acao');
        Route::post('/agenda/relatorio', [PsicologaController::class, 'gerarRelatorio'])->name('agenda.relatorio');

        Route::get('/prontuarios', [ProntuarioController::class, 'index'])->name('prontuarios.index');
        Route::post('/prontuarios/validar-senha', [ProntuarioController::class, 'validarSenha'])->name('prontuarios.validar-senha');
        Route::get('/prontuarios/aluno/{alunoId}', [ProntuarioController::class, 'show'])->name('prontuarios.aluno');
        Route::post('/prontuarios/aluno/{alunoId}', [ProntuarioController::class, 'store'])->name('prontuarios.store');
        Route::put('/prontuarios/sessao/{id}', [ProntuarioController::class, 'update'])->name('prontuarios.update');
        Route::delete('/prontuarios/sessao/{id}', [ProntuarioController::class, 'destroy'])->name('prontuarios.destroy');
    });
    
});