<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Horario;
use App\Models\RegistroAtendimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CalendarioController extends Controller
{
    public function index()
    {
        $nome = session('nome')
            ?? session('usuario_nome')
            ?? (auth()->check() ? auth()->user()->nome : null);

        $tipo = session('tipo')
            ?? session('usuario_tipo')
            ?? (auth()->check() ? auth()->user()->tipo : null);

        $matricula = session('matricula')
            ?? session('usuario_matricula')
            ?? (auth()->check()
                ? (auth()->user()->matricula ?? auth()->user()->id)
                : null);

        // A psicóloga visualiza todos os horários.
        if ($tipo === 'psicologa') {
            $horariosBrutos = Horario::all();
        } else {
            $horariosBrutos = Horario::where(
                'data',
                '>=',
                now()->toDateString()
            )->get();
        }

        $horarios = [];

        foreach ($horariosBrutos as $horario) {
            $horaFormatada = Carbon::parse($horario->hora)->format('i') === '00'
                ? Carbon::parse($horario->hora)->format('H\h')
                : Carbon::parse($horario->hora)->format('H\h\m');

            $ehMeuAgendamento = (
                $matricula
                && $horario->matricula === $matricula
            );

            $horarios[] = [
                'id' => $horario->id,
                'title' => $horaFormatada,
                'start' => $horario->data . 'T' . $horario->hora,
                'extendedProps' => [
                    'disponivel' => (int) $horario->disponivel,
                    'confirmado' => (int) ($horario->confirmado ?? 0),

                    // Dados pessoais apenas para a psicóloga
                    // ou para o próprio aluno.
                    'matricula_agendada' => (
                        $tipo === 'psicologa' || $ehMeuAgendamento
                    ) ? $horario->matricula : null,

                    'nome_agendado' => (
                        $tipo === 'psicologa' || $ehMeuAgendamento
                    ) ? $horario->nome : null,
                ],
            ];
        }

        $registros = collect();

        if ($nome && ($tipo === 'estudante' || $tipo === 'aluno')) {
            $registrosBrutos = RegistroAtendimento::where(
                'matricula',
                $matricula
            )
                ->orderBy('data_registro', 'desc')
                ->get();

            $historicoCancelados = $registrosBrutos->map(function ($reg) {
                $horarioOriginal = Horario::find(
                    $reg->id_horario_original
                );

                $reg->data_atendimento = $horarioOriginal
                    ? $horarioOriginal->data
                    : $reg->data_registro;

                $reg->hora_atendimento = $horarioOriginal
                    ? $horarioOriginal->hora
                    : $reg->data_registro;

                return $reg;
            });

            $ativosBrutos = Horario::where('matricula', $matricula)
                ->where('disponivel', 0)
                ->where(function ($query) {
                    $query->where('confirmado', 0)
                        ->orWhereNull('confirmado');
                })
                ->get();

            $historicoAtivos = $ativosBrutos->map(function ($ativo) {
                return (object) [
                    'data_atendimento' => $ativo->data,
                    'hora_atendimento' => $ativo->hora,
                    'status' => 'Agendado',
                    'observacao' => null,
                ];
            });

            $registros = collect($historicoAtivos)
                ->merge($historicoCancelados);
        }

        return view('index', compact('horarios', 'registros'));
    }

    public function processarAcao(Request $request)
    {
        $nome = session('nome')
            ?? session('usuario_nome')
            ?? (auth()->check() ? auth()->user()->nome : null);

        $tipo = session('tipo')
            ?? session('usuario_tipo')
            ?? (auth()->check() ? auth()->user()->tipo : null);

        $matricula = session('matricula')
            ?? session('usuario_matricula')
            ?? (auth()->check()
                ? (auth()->user()->matricula ?? auth()->user()->id)
                : null);

        $email = session('email')
            ?? (auth()->check() ? auth()->user()->email : null);

        $isLoggedIn = $nome
            && ($tipo === 'estudante' || $tipo === 'aluno');

        if (!$isLoggedIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você precisa estar logado para agendar ou cancelar um horário.',
            ]);
        }

        $action = $request->input('action');

        if ($action === 'agendar') {
            $horario = Horario::find($request->input('id_horario'));

            if (!$horario) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro: Horário não encontrado.',
                ]);
            }

            $dataHoraAgendamento = Carbon::parse(
                $horario->data . ' ' . $horario->hora
            );

            if ($dataHoraAgendamento->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Não é possível realizar agendamentos para um horário que já passou.',
                ]);
            }

            $jaTemAgendamentoNoDia = Horario::where(
                'matricula',
                $matricula
            )
                ->where('data', $horario->data)
                ->where('disponivel', 0)
                ->exists();

            if ($jaTemAgendamentoNoDia) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você já possui um atendimento agendado para esta data.',
                ]);
            }

            if ($horario->disponivel == 1) {
                $dadosReserva = [
                    'disponivel' => 0,
                    'nome' => $nome,
                    'matricula' => $matricula,
                    'token_cancelamento' => Str::random(64),
                ];

                // Só ocupa se a vaga ainda estiver disponível.
                $ocupou = Horario::whereKey($horario->id)
                    ->where('disponivel', 1)
                    ->update($dadosReserva);

                if ($ocupou !== 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Este horário não está mais disponível.',
                    ]);
                }

                $horario->fill($dadosReserva);

                try {
                    if ($email) {
                        $dataFormato = Carbon::parse(
                            $horario->data
                        )->format('d/m/Y');

                        $horaFormato = Carbon::parse(
                            $horario->hora
                        )->format('H:i');

                        $corpoHtml = "Olá, <strong>{$nome}</strong>!<br><br>"
                            . "Seu agendamento de consulta foi confirmado com sucesso.<br><br>"
                            . "<strong>Data:</strong> {$dataFormato}<br>"
                            . "<strong>Hora:</strong> {$horaFormato}<br><br>"
                            . "Atenciosamente,<br>"
                            . "<strong>Serviço de Psicologia - IFBA Seabra</strong>";

                        Mail::html(
                            $corpoHtml,
                            function ($message) use ($email, $nome) {
                                $message->to($email, $nome)
                                    ->subject(
                                        'Setor de Psicologia IFBA: Confirmação de Agendamento'
                                    );
                            }
                        );
                    }
                } catch (\Exception $e) {
                    Log::error(
                        'Falha ao enviar e-mail de confirmação: '
                        . $e->getMessage()
                    );
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Agendamento realizado!',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Erro: Horário pode não estar mais disponível.',
            ]);
        }

        if ($action === 'cancelar') {
            $horario = Horario::find($request->input('id_horario'));

            if (!$horario) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro: Horário não encontrado.',
                ]);
            }

            if ($horario->confirmado == 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Atendimentos já realizados não podem ser cancelados.',
                ]);
            }

            if ($matricula !== $horario->matricula) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Você só pode cancelar seu próprio agendamento.',
                ]);
            }

            $justificativa = $request->input('justificativa', '');

            $horario = DB::transaction(
                function () use ($horario, $justificativa, $nome, $matricula) {
                    $horario = $horario->bloquearReservaAtual();

                    RegistroAtendimento::create([
                        'id_horario_original' => $horario->id,
                        'nome' => $nome,
                        'matricula' => $matricula,
                        'status' => 'Cancelado pelo Aluno',
                        'observacao' => $justificativa,
                        'data_registro' => now(),
                    ]);

                    $horario->update([
                        'disponivel' => 1,
                        'nome' => null,
                        'matricula' => null,
                        'confirmado' => 0,
                        'justificativa_cancelamento' => $justificativa,
                        'token_cancelamento' => null,
                    ]);

                    return $horario;
                }
            );

            // Confirmação de cancelamento, sem botão de cancelar.
            try {
                if ($email) {
                    $dataFormato = Carbon::parse(
                        $horario->data
                    )->format('d/m/Y');

                    $horaFormato = Carbon::parse(
                        $horario->hora
                    )->format('H:i');

                    $texto = "Olá, {$nome}!\n\n"
                        . "Seu atendimento de {$dataFormato} às {$horaFormato} "
                        . "foi cancelado.\n\n"
                        . "Serviço de Psicologia - IFBA Seabra";

                    Mail::raw(
                        $texto,
                        function ($message) use ($email, $nome) {
                            $message->to($email, $nome)
                                ->subject(
                                    'Setor de Psicologia IFBA: Cancelamento confirmado'
                                );
                        }
                    );
                }
            } catch (\Exception $e) {
                Log::error(
                    'Falha ao enviar confirmação de cancelamento: '
                    . $e->getMessage()
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Agendamento cancelado com sucesso!',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Ação inválida.',
        ]);
    }

    /**
     * Exibe a confirmação sem consumir o token.
     * A rota deve usar o middleware "signed".
     */
    public function exibirTelaCancelamento(Request $request, $id)
    {
        $horario = Horario::find($id);
        $token = $request->query('token');

        if (!$this->tokenCancelamentoValido($horario, $token)) {
            return $this->respostaLinkIndisponivel();
        }

        // Preserva a expiração e o token do link original.
        $urlExecutar = URL::temporarySignedRoute(
            'agendamento.cancelar.executar',
            Carbon::createFromTimestamp(
                (int) $request->query('expires')
            ),
            [
                'id' => $horario->id,
                'token' => $token,
            ]
        );

        return response()
            ->view('agendamentos.confirmar_cancelamento', [
                'horario' => $horario,
                'urlExecutar' => $urlExecutar,
            ])
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Cancela o atendimento e consome o token.
     * A rota deve usar o middleware "signed".
     */
    public function executarCancelamentoDireto(Request $request, $id)
    {
        $cancelou = DB::transaction(function () use ($request, $id) {
            $horario = Horario::whereKey($id)
                ->lockForUpdate()
                ->first();

            // Confere novamente após bloquear a linha.
            if (!$this->tokenCancelamentoValido(
                $horario,
                $request->query('token')
            )) {
                return false;
            }

            RegistroAtendimento::create([
                'id_horario_original' => $horario->id,
                'nome' => $horario->nome,
                'matricula' => $horario->matricula,
                'status' => 'Cancelado pelo Aluno',
                'observacao' => 'Cancelamento confirmado via link do e-mail',
                'data_registro' => now(),
            ]);

            $horario->update([
                'disponivel' => 1,
                'nome' => null,
                'matricula' => null,
                'confirmado' => 0,
                'justificativa_cancelamento' => 'Cancelado pelo aluno via e-mail',
                'token_cancelamento' => null,
            ]);

            return true;
        });

        if (!$cancelou) {
            return $this->respostaLinkIndisponivel();
        }

        return response()
            ->view('agendamentos.status_cancelamento', [
                'titulo' => 'Agendamento cancelado com sucesso!',
                'mensagem' => 'Seu horário foi liberado no sistema.',
            ])
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function tokenCancelamentoValido(
        ?Horario $horario,
        $token
    ): bool {
        return $horario !== null
            && (int) $horario->disponivel === 0
            && (int) $horario->confirmado !== 1
            && is_string($token)
            && strlen($token) === 64
            && is_string($horario->token_cancelamento)
            && hash_equals($horario->token_cancelamento, $token);
    }

    private function respostaLinkIndisponivel()
    {
        return response()
            ->view('agendamentos.status_cancelamento', [
                'titulo' => 'Link de cancelamento indisponível',
                'mensagem' => 'O agendamento associado a este link pode já '
                    . 'ter sido cancelado ou encerrado. '
                    . 'Nenhum atendimento foi alterado.',
            ], 410)
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}