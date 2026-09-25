<?php

use App\Http\Controllers\CalendarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PsicologaController;
use App\Http\Controllers\ProntuarioController;
use App\Http\Middleware\RequisicaoUnica;
use App\Http\Middleware\LoginUnico;

// Rota principal da agenda
Route::get('/', [CalendarioController::class, 'index'])->name('agenda.index');

// Rotas de Autenticação (SUAP + Local)
Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
Route::post('/login', [AuthController::class, 'logar'])->name('login.post')->middleware(LoginUnico::class);

/* 
 * As rotas de registro foram desativadas pois o aluno 
 * autentica diretamente com as credenciais do SUAP.
 *
 * Route::get('/registro', [AuthController::class, 'mostrarRegistro'])->name('register');
 * Route::post('/registro', [AuthController::class, 'registrar'])->name('registrar');
 */

// Cancelamento direto via link assinado do e-mail
Route::get('/cancelar-confirmar/{id}', [CalendarioController::class, 'exibirTelaCancelamento'])
    ->name('agendamento.cancelarDirect')
    ->middleware('signed');

Route::post('/cancelar-executar/{id}', [CalendarioController::class, 'executarCancelamentoDireto'])
    ->name('agendamento.cancelar.executar')->middleware('signed');

// Rotas protegidas por login
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [AuthController::class, 'redirecionarUsuario'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/agendar', [CalendarioController::class, 'processarAcao'])->name('agenda.agendar')->middleware(RequisicaoUnica::class.':agenda');
    Route::post('/cancelar', [CalendarioController::class, 'processarAcao'])->name('agenda.cancelar')->middleware(RequisicaoUnica::class.':agenda');

    // Área exclusiva da Psicóloga
    Route::middleware(['verificar.psicologa'])->group(function () {
        
        Route::get('/agenda', [PsicologaController::class, 'index'])->name('psicologa.index');
        Route::get('/agenda/eventos', [PsicologaController::class, 'listarEventos'])->name('agenda.eventos');
        Route::post('/agenda/acao', [PsicologaController::class, 'processarAcao'])->name('agenda.acao')->middleware(RequisicaoUnica::class.':agenda');
        Route::post('/agenda/relatorio', [PsicologaController::class, 'gerarRelatorio'])->name('agenda.relatorio');

        Route::get('/prontuarios', [ProntuarioController::class, 'index'])->name('prontuarios.index');
        Route::post(
    '/prontuarios/validar-senha',
    [ProntuarioController::class, 'validarSenha']
)
    ->name('prontuarios.validar-senha')
    ->middleware('throttle:5,30,senha-prontuario:');
        Route::get('/prontuarios/aluno/{alunoId}', [ProntuarioController::class, 'show'])->name('prontuarios.aluno');
        Route::post('/prontuarios/aluno/{alunoId}', [ProntuarioController::class, 'store'])->name('prontuarios.store')->middleware(RequisicaoUnica::class.':prontuario');
        Route::put('/prontuarios/sessao/{id}', [ProntuarioController::class, 'update'])->name('prontuarios.update')->middleware(RequisicaoUnica::class.':prontuario');
        Route::delete('/prontuarios/sessao/{id}', [ProntuarioController::class, 'destroy'])->name('prontuarios.destroy')->middleware(RequisicaoUnica::class.':prontuario');
    });
    
});
