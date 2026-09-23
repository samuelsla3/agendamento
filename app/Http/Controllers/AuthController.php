<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Services\SuapService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        if (Auth::check()) {
            return $this->redirecionarUsuario();
        }
        return view('login');
    }

    public function logar(Request $request, SuapService $suapService)
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

        if ($token) {
            $dadosSuap = $suapService->meusDados($token);

            // Extrai situação e tenta resgatar a turma do payload principal
            $situacaoVinculo = $dadosSuap['vinculo']['situacao'] 
                            ?? $dadosSuap['situacao'] 
                            ?? $dadosSuap['tipo_vinculo'] 
                            ?? null;

            $turmaAtual = $dadosSuap['vinculo']['turma_atual'] 
                       ?? $dadosSuap['turma_atual'] 
                       ?? $dadosSuap['turma'] 
                       ?? null;

            // Se não veio no payload principal, tenta buscar no endpoint acadêmico do SUAP
            if (empty($turmaAtual) && method_exists($suapService, 'obterTurmaAtual')) {
                $turmaAtual = $suapService->obterTurmaAtual($token);
            }

            // FALLBACK INTELIGENTE: Se a API não entregar a turma, infere pelo ano e curso (INF x MAM)
            if (empty($turmaAtual) && !empty($matricula)) {
                $anoIngresso = substr($matricula, 0, 4); // Pega o ano inicial da matrícula (Ex: '2023')
                $curso = strtoupper($dadosSuap['vinculo']['curso'] ?? '');

                // Mapeia entre os dois cursos do campus (Informática = 18 / INF | Meio Ambiente = 28 / MAM)
                if (str_contains($curso, 'MEIO AMBIENTE') || str_contains($curso, '28') || str_contains($curso, 'MAM')) {
                    $siglaCurso = 'MAM';
                } else {
                    $siglaCurso = 'INF'; // Padrão Informática (18 / INF)
                }

                $turmaAtual = "{$anoIngresso} - {$siglaCurso}";
            }

            Log::info("Login SUAP - Matrícula {$matricula}:", [
                'situacao' => $situacaoVinculo,
                'turma'    => $turmaAtual,
                'payload'  => $dadosSuap
            ]);

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

            // PRIORIDADE DE E-MAIL: Pessoal > Institucional > Máscara @ifba.edu.br (com validação anti-string vazia)
            $emailPessoal = $dadosSuap['email_pessoal'] 
                         ?? $dadosSuap['email_secundario'] 
                         ?? $dadosSuap['vinculo']['email_pessoal'] 
                         ?? null;

            $emailGeral = !empty($dadosSuap['email']) ? trim($dadosSuap['email']) : null;
            $emailBruto = $emailPessoal ?? $emailGeral;

            if (!empty($emailBruto) && filter_var($emailBruto, FILTER_VALIDATE_EMAIL)) {
                $emailFinal = $emailBruto;
            } else {
                $emailFinal = $matricula . '@ifba.edu.br';
            }

            // Salva ou atualiza no banco local
            $usuario = Usuario::updateOrCreate(
                ['matricula' => $matricula],
                [
                    'nome'         => $nome,
                    'email'        => $emailFinal,
                    'turma_codigo' => $turmaAtual,
                    'tipo'         => 'estudante',
                    'senha'        => Hash::make($senha)
                ]
            );

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

        if ($usuarioLocal && Hash::check($senha, $usuarioLocal->senha)) {
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