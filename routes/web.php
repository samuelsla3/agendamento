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
Route::post('/login', [AuthController::class, 'logar'])->name('login.post')->middleware([\App\Http\Middleware\LimitarLogin::class, LoginUnico::class, 'throttle:100,1,login-ip:']);

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

    // Contato privado. Anexos nunca usam o disco publico nem URLs de storage.
    Route::get('/mensagens/resumo', [\App\Http\Controllers\MensagensController::class, 'resumo'])->name('mensagens.resumo')->middleware('throttle:120,1,mensagens-leitura:');
    Route::post('/mensagens/desbloquear', [\App\Http\Controllers\MensagensController::class, 'desbloquear'])->name('mensagens.desbloquear')->middleware('throttle:5,30,senha-prontuario:');
    Route::post('/mensagens/conversas/abrir', [\App\Http\Controllers\MensagensController::class, 'abrir'])->name('mensagens.abrir')->middleware('throttle:30,1,mensagens-abrir:');
    Route::get('/mensagens/conversas/{conversa}', [\App\Http\Controllers\MensagensController::class, 'historico'])->whereNumber('conversa')->name('mensagens.historico')->middleware('throttle:120,1,mensagens-leitura:');
    Route::post('/mensagens/conversas/{conversa}/enviar', [\App\Http\Controllers\MensagensController::class, 'enviar'])->whereNumber('conversa')->name('mensagens.enviar');
    Route::post('/mensagens/conversas/{conversa}/lidas', [\App\Http\Controllers\MensagensController::class, 'lidas'])->whereNumber('conversa')->name('mensagens.lidas')->middleware('throttle:120,1,mensagens-lidas:');
    Route::get('/mensagens/contatos', [\App\Http\Controllers\MensagensController::class, 'contatos'])->name('mensagens.contatos')->middleware('throttle:120,1,mensagens-leitura:');
    Route::get('/mensagens/alunos', [\App\Http\Controllers\MensagensController::class, 'alunos'])->name('mensagens.alunos')->middleware('throttle:120,1,mensagens-leitura:');
    Route::get('/mensagens/anexos/{anexo}', [\App\Http\Controllers\MensagensController::class, 'anexo'])->whereUuid('anexo')->name('mensagens.anexo')->middleware('throttle:30,1,mensagens-anexos:');

    Route::post('/sugestoes', [\App\Http\Controllers\SugestaoController::class, 'enviar'])->name('sugestoes.enviar');

    Route::get('/dashboard', [AuthController::class, 'redirecionarUsuario'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/contato/email', [\App\Http\Controllers\EmailContatoController::class, 'status'])->name('contato.email.status');
    Route::post('/contato/email/enviar', [\App\Http\Controllers\EmailContatoController::class, 'enviar'])
        ->name('contato.email.enviar')->middleware([RequisicaoUnica::class . ':agenda', 'throttle:3,10,email-enviar:']);
    Route::post('/contato/email/confirmar', [\App\Http\Controllers\EmailContatoController::class, 'confirmar'])
        ->name('contato.email.confirmar')->middleware([RequisicaoUnica::class . ':agenda', 'throttle:10,10,email-confirmar:']);

    Route::post('/agendar', [CalendarioController::class, 'processarAcao'])->name('agenda.agendar')->middleware(RequisicaoUnica::class.':agenda');
    Route::post('/cancelar', [CalendarioController::class, 'processarAcao'])->name('agenda.cancelar')->middleware(RequisicaoUnica::class.':agenda');

    Route::middleware(['verificar.psicologa'])->group(function () {
        Route::get('/seguranca/primeiro-acesso', [\App\Http\Controllers\SegurancaController::class, 'primeiro'])->name('seguranca.primeiro');
        Route::post('/seguranca/primeiro-acesso', [\App\Http\Controllers\SegurancaController::class, 'cadastrar'])
            ->name('seguranca.primeiro.salvar')->middleware(['throttle:5,30,senha-inicial:', RequisicaoUnica::class . ':agenda']);
        Route::get('/configuracoes/seguranca', [\App\Http\Controllers\SegurancaController::class, 'index'])->name('seguranca.index');
        Route::post('/configuracoes/seguranca/local', [\App\Http\Controllers\SegurancaController::class, 'alterarLocal'])
            ->name('seguranca.local')->middleware(['throttle:5,30,senha-local:', RequisicaoUnica::class . ':agenda']);
        Route::post('/configuracoes/seguranca/prontuario', [\App\Http\Controllers\SegurancaController::class, 'alterarProntuario'])
            ->name('seguranca.prontuario')->middleware(['throttle:5,30,senha-trocar-prontuario:', RequisicaoUnica::class . ':agenda']);
    });

    // Área exclusiva da Psicóloga
    Route::middleware(['verificar.psicologa'])->group(function () {
        
        Route::get('/agenda', [PsicologaController::class, 'index'])->name('psicologa.index');
        Route::get('/agenda/eventos', [PsicologaController::class, 'listarEventos'])->name('agenda.eventos');
        Route::post('/agenda/acao', [PsicologaController::class, 'processarAcao'])->name('agenda.acao')->middleware(RequisicaoUnica::class.':agenda');
        Route::get('/emails', [\App\Http\Controllers\EmailsController::class, 'index'])->name('emails.index');
        Route::post('/emails/{aviso}/tentar-novamente', [\App\Http\Controllers\EmailsController::class, 'tentarNovamente'])
            ->whereNumber('aviso')->name('emails.tentar')
            ->middleware([RequisicaoUnica::class . ':emails', 'throttle:20,1']);

        Route::post('/agenda/relatorio', [PsicologaController::class, 'gerarRelatorio'])->name('agenda.relatorio');

        Route::get('/configuracoes/sugestoes', [\App\Http\Controllers\SugestaoController::class, 'listar'])->name('sugestoes.listar');

        Route::get('/configuracoes', [\App\Http\Controllers\ConfiguracaoController::class, 'index'])
    ->name('configuracoes.index');
        Route::post('/configuracoes/calendario', [\App\Http\Controllers\ConfiguracaoController::class, 'atualizarCalendario'])
            ->name('configuracoes.calendario.atualizar');
Route::post('/configuracoes', [\App\Http\Controllers\ConfiguracaoController::class, 'atualizar'])
    ->name('configuracoes.atualizar');
    Route::post('/configuracoes/aviso', [\App\Http\Controllers\ConfiguracaoController::class, 'atualizarAviso'])
    ->name('configuracoes.aviso.atualizar');
    Route::post('/configuracoes/antecedencia', [\App\Http\Controllers\ConfiguracaoController::class, 'atualizarAntecedencia'])
    ->name('configuracoes.antecedencia.atualizar');
    Route::post('/configuracoes/informacoes', [\App\Http\Controllers\ConfiguracaoController::class, 'atualizarInformacoes'])
    ->name('configuracoes.informacoes.atualizar');

        Route::get('/prontuarios', [ProntuarioController::class, 'index'])->name('prontuarios.index');
        Route::get('/prontuarios/acesso/status', [ProntuarioController::class, 'statusAcesso'])->name('prontuarios.acesso.status');
        Route::post('/prontuarios/acesso/renovar', [ProntuarioController::class, 'renovarAcesso'])
            ->name('prontuarios.acesso.renovar')->middleware('throttle:120,1,atividade-prontuario:');
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
