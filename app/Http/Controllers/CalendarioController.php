<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agendamento;
use App\Models\Horario;
use App\Models\RegistroAtendimento;
use App\Mail\ConfirmacaoAgendamentoMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CalendarioController extends Controller
{
    public function index()
{
    // Captura os dados da sessão/autenticação
    $nome = session('nome') ?? session('usuario_nome') ?? (auth()->check() ? auth()->user()->nome : null);
    $tipo = session('tipo') ?? session('usuario_tipo') ?? (auth()->check() ? auth()->user()->tipo : null);
    $matricula = session('matricula') ?? session('usuario_matricula') ?? (auth()->check() ? (auth()->user()->matricula ?? auth()->user()->id) : null);

    // Se for PSICÓLOGA, carrega TODOS os horários (passados, presentes e futuros)
    if ($tipo === 'psicologa') {
        $horariosBrutos = Horario::all();
    } else {
        // Se for ESTUDANTE ou VISITANTE, carrega apenas de HOJE em diante
        $horariosBrutos = Horario::where('data', '>=', now()->toDateString())->get();
    }

    $horarios = [];
    foreach ($horariosBrutos as $horario) {
        $horaFormatada = Carbon::parse($horario->hora)->format('i') === '00' 
            ? Carbon::parse($horario->hora)->format('H\h') 
            : Carbon::parse($horario->hora)->format('H\h\m');

        // Checa se o agendamento pertence ao usuário logado
        $ehMeuAgendamento = ($matricula && $horario->matricula === $matricula);

        $horarios[] = [
            'id'    => $horario->id,
            'title' => $horaFormatada,
            'start' => $horario->data . 'T' . $horario->hora,
            'extendedProps' => [
                'disponivel' => (int)$horario->disponivel,
                'confirmado' => (int)($horario->confirmado ?? 0),
                
                // LGPD / SEGURANÇA: 
                // Se for Psicóloga, exibe tudo. 
                // Se for Aluno, exibe matrícula e nome APENAS se o agendamento for DELE MESMO.
                'matricula_agendada' => ($tipo === 'psicologa' || $ehMeuAgendamento) ? $horario->matricula : null,
                'nome_agendado'      => ($tipo === 'psicologa' || $ehMeuAgendamento) ? $horario->nome : null,
            ]
        ];
    }

    $registros = collect();
    
    if ($nome && ($tipo === 'estudante' || $tipo === 'aluno')) {
        $registrosBrutos = RegistroAtendimento::where('matricula', $matricula)
                                            ->orderBy('data_registro', 'desc')
                                            ->get();

        $historicoCancelados = $registrosBrutos->map(function($reg) {
            $horarioOriginal = Horario::find($reg->id_horario_original);
            
            $reg->data_atendimento = $horarioOriginal ? $horarioOriginal->data : $reg->data_registro;
            $reg->hora_atendimento = $horarioOriginal ? $horarioOriginal->hora : $reg->data_registro;
            
            return $reg;
        });

        $ativosBrutos = Horario::where('matricula', $matricula)
                               ->where('disponivel', 0)
                               ->where(function($query) {
                                   $query->where('confirmado', 0)->orWhereNull('confirmado');
                               })
                               ->get();

        $historicoAtivos = $ativosBrutos->map(function($ativo) {
            return (object)[
                'data_atendimento' => $ativo->data,
                'hora_atendimento' => $ativo->hora,
                'status'           => 'Agendado',
                'observacao'       => null
            ];
        });

        $registros = collect($historicoAtivos)->merge($historicoCancelados);
    }

    return view('index', compact('horarios', 'registros'));
}

    public function processarAcao(Request $request)
    {
        $nome = session('nome') ?? session('usuario_nome') ?? (auth()->check() ? auth()->user()->nome : null);
        $tipo = session('tipo') ?? session('usuario_tipo') ?? (auth()->check() ? auth()->user()->tipo : null);
        $matricula = session('matricula') ?? session('usuario_matricula') ?? (auth()->check() ? (auth()->user()->matricula ?? auth()->user()->id) : null);
        $email = session('email') ?? (auth()->check() ? auth()->user()->email : null);

        $isLoggedIn = $nome && ($tipo === 'estudante' || $tipo === 'aluno');

        if (!$isLoggedIn) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Você precisa estar logado para agendar ou cancelar um horário.'
            ]);
        }

        $action = $request->input('action');

        if ($action === 'agendar') {
            $horario = Horario::find($request->input('id_horario'));

            if ($horario) {
                $dataHoraAgendamento = \Carbon\Carbon::parse($horario->data . ' ' . $horario->hora);
                if ($dataHoraAgendamento->isPast()) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'Não é possível realizar agendamentos para um horário que já passou.'
                    ]);
                }
            }

            #consulta no bd na tabela horarios
            $jaTemAgendamentoNoDia = Horario::where('matricula', $matricula)
            ->where('data', $horario->data)
            ->where('disponivel', 0) #considera apenas horarios ocupados (valor 0)
            ->exists(); #retorna true se encontrar 1 registro

            #se retornar true aparecerá na tela a mensagem de erro
            if ($jaTemAgendamentoNoDia) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você já possui um atendimento agendado para esta data.'
                ]);
            }
        
            if ($horario && $horario->disponivel == 1) {
                $horario->update([
                    'disponivel' => 0,
                    'nome' => $nome,
                    'matricula' => $matricula
                ]);

                try {
                    if ($email) {
                        $dataFormato = date('d/m/Y', strtotime($horario->data));
                        $horaFormato = date('H:i', strtotime($horario->hora));

                        $corpoHtml = "Olá, <strong>{$nome}</strong>!<br><br>" .
                                     "Seu agendamento de consulta foi confirmado com sucesso.<br><br>" .
                                     "<strong>Data:</strong> {$dataFormato}<br>" .
                                     "<strong>Hora:</strong> {$horaFormato}<br><br>" .
                                     "Atenciosamente,<br>" .
                                     "<strong>Serviço de Psicologia - IFBA Seabra</strong>";

                        Mail::html($corpoHtml, function ($message) use ($email, $nome) {
                            $message->to($email, $nome)->subject('Setor de Psicologia IFBA: Confirmação de Agendamento');
                        });
                    }
                } catch (\Exception $e) {
                    Log::error('Falha ao enviar e-mail de confirmação: ' . $e->getMessage());
                }

                return response()->json(['status' => 'success', 'message' => 'Agendamento realizado!']);
            }

            return response()->json(['status' => 'error', 'message' => 'Erro: Horário pode não estar mais disponível.']);
        }

        if ($action === 'cancelar') {
            $horario = Horario::find($request->input('id_horario'));

            if (!$horario) {
    return response()->json(['status' => 'error', 'message' => 'Erro: Horário não encontrado.']);
}

// ADICIONE ESTA TRAVA AQUI
if ($horario->confirmado == 1) {
    return response()->json(['status' => 'error', 'message' => 'Atendimentos já realizados não podem ser cancelados.']);
}

            if ($matricula !== $horario->matricula) {
                return response()->json(['status' => 'error', 'message' => 'Você só pode cancelar seu próprio agendamento.']);
            }

            $justificativa = $request->input('justificativa', '');

            DB::transaction(function () use ($horario, $justificativa, $nome, $matricula) {
                RegistroAtendimento::create([
                    'id_horario_original' => $horario->id,
                    'nome'                => $nome,
                    'matricula'           => $matricula,
                    'status'              => 'Cancelado pelo Aluno',
                    'observacao'          => $justificativa,
                    'data_registro'       => now()
                ]);

                $horario->update([
                    'disponivel' => 1,
                    'nome' => null,
                    'matricula' => null,
                    'confirmado' => 0,
                    'justificativa_cancelamento' => $justificativa
                ]);
            });

            try {
                if ($email) {
                    // Também joga o e-mail de cancelamento para a fila em segundo plano
                    Mail::to($email, $nome)->queue(new \App\Mail\ConfirmacaoAtendimentoMail($horario));
                }
            } catch (\Exception $e) {
                Log::error('Falha ao agendar e-mail de cancelamento na fila: ' . $e->getMessage());
            }

            return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado com sucesso!']);
        }

        return response()->json(['status' => 'error', 'message' => 'Ação inválida.']);
    }

// 1. Apenas exibe a tela pedindo a confirmação do aluno (Substitui o cancelamento direto pelo clique)
    public function exibirTelaCancelamento($id)
    {
        $horario = Horario::find($id);

        if (!$horario || $horario->disponivel == 1) {
            return response("
                <div style='text-align: center; margin-top: 50px; font-family: sans-serif;'>
                    <h2>Este agendamento já foi cancelado ou não existe mais.</h2>
                    <a href='".url('/')."' style='color: #1c7ed6; text-decoration: none; font-weight: bold;'>Voltar para o site</a>
                </div>
            ");
        }

        return view('agendamentos.confirmar_cancelamento', compact('horario'));
    }

    // 2. Executa o cancelamento e salva o registro após o aluno clicar no botão da tela
    public function executarCancelamentoDireto(Request $request, $id)
{
    $horario = Horario::findOrFail($id);

    if ($horario->disponivel == 0) {
        DB::transaction(function () use ($horario) {
            // Registra o cancelamento na tabela de histórico
            RegistroAtendimento::create([
                'id_horario_original' => $horario->id,
                'nome'                => $horario->nome,
                'matricula'           => $horario->matricula,
                'status'              => 'Cancelado pelo Aluno', // Ajustado para bater exatamente com o filtro da index()
                'observacao'          => 'Cancelamento confirmado via link do e-mail',
                'data_registro'       => now()
            ]);

            // Libera o horário na tabela principal
            $horario->update([
                'disponivel' => 1,
                'nome' => null,
                'matricula' => null,
                'confirmado' => 0,
                'justificativa_cancelamento' => 'Cancelado pelo aluno via e-mail'
            ]);
        });
    }

    return response("
        <div style='text-align: center; margin-top: 50px; font-family: sans-serif;'>
            <h2 style='color: #2b8a3e;'>Agendamento Cancelado com Sucesso!</h2>
            <p>Seu horário foi liberado no sistema do IFBA Seabra.</p>
            <a href='".url('/')."' style='color: #1c7ed6; text-decoration: none; font-weight: bold;'>Voltar para o site</a>
        </div>
    ");
}
}