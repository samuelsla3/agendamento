<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Horario;
use App\Models\RegistroAtendimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CalendarioController extends Controller
{
    public function index()
    {
        $horariosBrutos = Horario::where('data', '>=', now()->toDateString())->get();

        $horarios = [];
            foreach ($horariosBrutos as $horario) {
                $horaFormatada = Carbon::parse($horario->hora)->format('i') === '00' 
                    ? Carbon::parse($horario->hora)->format('H\h') 
                    : Carbon::parse($horario->hora)->format('H\h\m');

                $horarios[] = [
                    'id' => $horario->id,
                    'title' => $horaFormatada,
                    'start' => $horario->data . 'T' . $horario->hora,
                    'extendedProps' => [
                        'disponivel' => (int)$horario->disponivel,
                        'matricula_agendada' => $horario->matricula
                    ]
                ];
            }

        $nome = session('nome') ?? session('usuario_nome') ?? (auth()->check() ? auth()->user()->nome : null);
        $tipo = session('tipo') ?? session('usuario_tipo') ?? (auth()->check() ? auth()->user()->tipo : null);
        $matricula = session('matricula') ?? session('usuario_matricula') ?? (auth()->check() ? (auth()->user()->matricula ?? auth()->user()->id) : null);

        $registros = collect();
        
        if ($nome && ($tipo === 'estudante' || $tipo === 'aluno')) {
            $registros = Horario::where('matricula', $matricula)
                            ->orderBy('data', 'desc')
                            ->orderBy('hora', 'desc')
                            ->get();
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

            if ($horario && $horario->disponivel == 1) {
                $horario->update([
                    'disponivel' => 0,
                    'nome' => $nome,
                    'matricula' => $matricula
                ]);

                try {
                    $data_formatada = Carbon::parse($horario->data)->format('d/m/Y');
                    $hora_formatada = Carbon::parse($horario->hora)->format('H:i');

                    if ($email) {
                        $corpo_html = "Olá, <strong>{$nome}</strong>!<br><br>Seu agendamento de consulta foi confirmado com sucesso.<br><br><strong>Data:</strong> {$data_formatada}<br><strong>Hora:</strong> {$hora_formatada}<br><br>Atenciosamente,<br>Serviço de Psicologia - IFBA";
                        
                        Mail::html($corpo_html, function ($message) use ($email, $nome) {
                            $message->to($email, $nome)
                                    ->subject('Confirmação de Agendamento de Consulta');
                        });
                    }
                } catch (\Exception $e) {
                    Log::error('Falha ao enviar e-mail de agendamento: ' . $e->getMessage());
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

            if ($matricula !== $horario->matricula) {
                return response()->json(['status' => 'error', 'message' => 'Você só pode cancelar seu próprio agendamento.']);
            }

            $justificativa = $request->input('justificativa', '');

            DB::transaction(function () use ($horario, $justificativa) {
                RegistroAtendimento::create([
                    'status' => 'Cancelado pelo Aluno',
                    'observacao' => $justificativa,
                    'data_registro' => now()
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
                $data_formatada = Carbon::parse($horario->data)->format('d/m/Y');
                $hora_formatada = Carbon::parse($horario->hora)->format('H:i');

                if ($email) {
                    $corpo_html = "Olá!<br><br>O agendamento do(a) aluno(a) <strong>{$nome}</strong> para a data <strong>{$data_formatada}</strong> às <strong>{$hora_formatada}</strong> foi cancelado por você.<br><br><strong>Justificativa informada:</strong> {$justificativa}<br><br>Este horário está novamente disponível na agenda.";
                    
                    Mail::html($corpo_html, function ($message) use ($email, $nome) {
                        $message->to($email, $nome)
                                ->subject('Aviso de Cancelamento de Consulta');
                    });
                }
            } catch (\Exception $e) {
                Log::error('Falha ao enviar e-mail de cancelamento: ' . $e->getMessage());
            }

            return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado com sucesso!']);
        }

        return response()->json(['status' => 'error', 'message' => 'Ação inválida.']);
    }
}