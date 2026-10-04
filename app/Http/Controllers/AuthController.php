<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Services\SuapService;
use App\Services\Suap\EmailPessoalService;
use App\Services\Suap\TurmaAlunoService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        if (Auth::check()) {
            return $this->redirecionarUsuario();
        }
        return view('login');
    }

    public function logar(Request $request, SuapService $suapService, EmailPessoalService $emailService, TurmaAlunoService $turmaService)
    {
        $credenciais = $request->validate([
            'matricula' => 'required|string',
            'senha'     => 'required|string',
        ]);

        $matricula = $credenciais['matricula'];
        $senha = $credenciais['senha'];

        // ---------------------------------------------------------------------
        // 1. TENTA AUTENTICAR NO SUAP VIA API JWT (Fluxo Principal para Alunos)
        // ---------------------------------------------------------------------

        $token = $suapService->autenticar($matricula, $senha);

$dadosSuap = $token
    ? $suapService->meusDados($token)
    : null;

// Só utiliza o fluxo SUAP quando os dados foram obtidos.
// Caso contrário, segue para o login local existente abaixo.
if ($token && is_array($dadosSuap) && !empty($dadosSuap)) {

            // Extrai a situação do vínculo; turma será consultada após as validações.
            $situacaoVinculo = $dadosSuap['vinculo']['situacao'] 
                            ?? $dadosSuap['situacao'] 
                            ?? $dadosSuap['tipo_vinculo'] 
                            ?? null;

            Log::info('Login SUAP: dados recebidos.');

            // Bloqueia egressos/inativos
            $situacoesInativas = ['Concluído', 'Formado', 'Evadido', 'Cancelado', 'Desligado', 'Transferido'];

            if ($situacaoVinculo && in_array($situacaoVinculo, $situacoesInativas)) {
                return back()->withErrors([
                    'matricula' => 'Acesso não permitido. Este sistema é exclusivo para alunos regularmente matriculados.'
                ])->withInput($request->only('matricula'));
            }

            // Captura do Nome Completo (Prioriza 'vinculo' -> 'nome')
            $nome = $dadosSuap['vinculo']['nome'] 
                 ?? $dadosSuap['nome_completo'] 
                 ?? $dadosSuap['nome_usual'] 
                 ?? $dadosSuap['nome'] 
                 ?? 'Estudante';

            $usuarioExistente = Usuario::where('matricula', $matricula)->first();
            $emailFinal = $emailService->resolver(
                $dadosSuap,
                $matricula,
                $senha,
                $usuarioExistente?->email
            );

            // A tabela exige e-mail único; um contato compartilhado não pode derrubar o login.
            $emailEmUso = fn (string $email): bool => Usuario::where('email', $email)
                ->where('matricula', '<>', $matricula)->exists();

            if ($emailEmUso($emailFinal)) {
                Log::warning('SUAP email pessoal: contato ja vinculado a outra conta.');
                $emailAtual = trim($usuarioExistente?->email ?? '');
                $emailFinal = filter_var($emailAtual, FILTER_VALIDATE_EMAIL) !== false
                    && !$emailEmUso($emailAtual)
                    ? $emailAtual
                    : $matricula . '@ifba.edu.br';

                if ($emailEmUso($emailFinal)) {
                    return back()->withErrors([
                        'matricula' => 'Existe um conflito de e-mail no cadastro. Procure o responsável pelo sistema.'
                    ])->withInput($request->only('matricula'));
                }
            }

            // Consulta confirmada no SUAP; em caso de falha, conserva o cadastro.
            // Não infere turma usando o ano da matrícula ou o nome do curso.
            $turmaAtual = $turmaService->resolver(
                $dadosSuap,
                $matricula,
                $senha,
                $usuarioExistente?->turma_codigo
            );

            // Salva ou atualiza no banco local
            $usuario = DB::transaction(function () use ($matricula, $nome, $emailFinal, $turmaAtual, $senha) {
                // Apenas a gravação local participa da trava; SUAP fica fora da transação.
                $trava = DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first();
                abort_unless($trava, 503, 'Execute as migrations antes de usar o sistema.');
                $usuario = Usuario::updateOrCreate(
                    ['matricula' => $matricula],
                    [
                        'nome'         => $nome,
                        'email'        => $emailFinal,
                        'turma_codigo' => $turmaAtual,
                        'tipo'         => 'estudante',
                        'cadastro_provisorio' => false,
                        'senha'        => Hash::make($senha)
                    ]
                );

                // Agendamentos e histórico também guardam uma cópia do nome.
                // A matrícula identifica a pessoa, inclusive depois do cadastro provisório.
                // Mantém datas, status, tokens e anotações exatamente como estavam.
                foreach (['horarios', 'registros_atendimentos'] as $tabela) {
                    DB::table($tabela)->where('matricula', $usuario->matricula)
                        ->update(['nome' => $usuario->nome]);
                }

                return $usuario;
            });

            Auth::login($usuario);
            $request->session()->regenerate();

            session([
                'suap_jwt'     => $token,
                'usuario_tipo' => 'aluno',
                'usuario_nome' => $usuario->nome,
                'nome'         => $usuario->nome,
                'tipo'         => 'estudante',
                'matricula'    => $usuario->matricula,
                'email'        => $usuario->email
            ]);

            return $this->redirecionarUsuario();
        }

        // ---------------------------------------------------------------------
        // 2. FALLBACK LOCAL (Servidores/Psicóloga OU Alunos quando SUAP estiver OFF)
        // ---------------------------------------------------------------------
        $usuarioLocal = Usuario::where('matricula', $matricula)->first();

        if ($usuarioLocal && !$usuarioLocal->cadastro_provisorio && Hash::check($senha, $usuarioLocal->senha)) {
            Auth::login($usuarioLocal);
            $request->session()->regenerate();

            if ($usuarioLocal->tipo === 'psicologa') {
                session([
                    'usuario_tipo' => 'psicologa',
                    'usuario_nome' => $usuarioLocal->nome,
                    'nome'         => $usuarioLocal->nome,
                    'tipo'         => 'psicologa'
                ]);
            } else {
                session([
                    'usuario_tipo' => 'aluno',
                    'usuario_nome' => $usuarioLocal->nome,
                    'nome'         => $usuarioLocal->nome,
                    'tipo'         => 'estudante',
                    'matricula'    => $usuarioLocal->matricula,
                    'email'        => $usuarioLocal->email
                ]);
            }

            return $this->redirecionarUsuario();
        }

        // ---------------------------------------------------------------------
        // 3. FALHA TOTAL
        // ---------------------------------------------------------------------
        return back()->withErrors([
            'matricula' => 'A matrícula/senha fornecida está incorreta (ou o SUAP está fora do ar e este é seu primeiro acesso).',
        ])->withInput($request->only('matricula'));
    }

    public function redirecionarUsuario()
    {
        $usuario = Auth::user();

        if ($usuario) {
            if ($usuario->tipo === 'psicologa') {
                return redirect()->route('psicologa.index');
            }

            if ($usuario->tipo === 'estudante' || $usuario->tipo === 'aluno') {
                return redirect()->route('agenda.index');
            }
        }

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
