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
use App\Services\AvisosEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

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
                'versao' => ($horario->disponivel == 1 || $tipo === 'psicologa' || $ehMeuAgendamento) ? $horario->versao() : null,
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

        // O histórico usa a data/hora gravada no próprio registro.
        // Valores ausentes em registros antigos são tratados pela view.
        $historicoCancelados = $registrosBrutos;

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


    // As rotas autenticadas usam RequisicaoUnica: transação + trava da agenda.
    public function processarAcao(Request $request)
    {
        $usuario = $request->user();
        abort_unless($usuario && in_array($usuario->tipo, ['estudante', 'aluno']), 403);
        $request->validate(['action' => 'required|in:agendar,cancelar', 'id_horario' => 'required|integer', 'versao' => 'required|string|size:64']);
        $horario = Horario::whereKey($request->input('id_horario'))->lockForUpdate()->firstOrFail();
        $horario->conferirVersao($request->input('versao'));
        $nome = $usuario->nome;
        $matricula = $usuario->matricula;
        $email = $usuario->email;
        $data = Carbon::parse($horario->data)->format('d/m/Y');
        $hora = Carbon::parse($horario->hora)->format('H:i');

        if ($request->input('action') === 'agendar') {
            if (Carbon::parse($horario->data.' '.$horario->hora)->isPast()) {
                return response()->json(['status' => 'error', 'message' => 'Não é possível agendar um horário que já passou.']);
            }
            if ((int) $horario->disponivel !== 1) {
                return response()->json(['status' => 'error', 'message' => 'Este horário não está mais disponível.']);
            }
            if (Horario::where('matricula', $matricula)->where('data', $horario->data)->where('disponivel', 0)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Você já possui um atendimento agendado para esta data.']);
            }
            $token = Str::random(64);
            $horario->update(['disponivel' => 0, 'nome' => $nome, 'matricula' => $matricula,
                'confirmado' => 0, 'justificativa_cancelamento' => null, 'token_cancelamento' => $token]);
            AvisosEmail::registrar('agendado:'.$token, (string) $email,
                'Setor de Psicologia IFBA: Confirmação de Agendamento',
                'Olá, <strong>'.e($nome).'</strong>!<br><br>Seu agendamento foi confirmado.<br>'
                .'<strong>Data:</strong> '.$data.'<br><strong>Hora:</strong> '.$hora
                .'<br><br>Serviço de Psicologia - IFBA Seabra');
            return response()->json(['status' => 'success', 'message' => 'Agendamento realizado! A confirmação será enviada por e-mail.']);
        }

        abort_unless((string) $horario->matricula === (string) $matricula, 403, 'Você só pode cancelar seu próprio agendamento.');
        abort_if((int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1, 409, 'Este atendimento já foi encerrado.');
        $request->validate(['justificativa' => 'nullable|string']);
        $token = $horario->token_cancelamento;
        $justificativa = $request->input('justificativa', '') ?? '';
        RegistroAtendimento::create(['id_horario_original' => $horario->id,
                    'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora, 'nome' => $nome, 'matricula' => $matricula,
            'status' => 'Cancelado pelo Aluno', 'observacao' => $justificativa, 'data_registro' => now()]);
        $horario->update(['disponivel' => 1, 'nome' => null, 'matricula' => null, 'confirmado' => 0,
            'justificativa_cancelamento' => $justificativa, 'token_cancelamento' => null]);
        AvisosEmail::registrar('cancelado:'.$token, (string) $email,
            'Setor de Psicologia IFBA: Cancelamento confirmado',
            'Olá, '.e($nome).'!<br><br>Seu atendimento de '.$data.' às '.$hora.' foi cancelado.'
            .'<br><br>Serviço de Psicologia - IFBA Seabra');
        return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado. A confirmação será enviada por e-mail.']);
    }

    public function exibirTelaCancelamento(Request $request, $id)
    {
        $horario = Horario::find($id);
        $token = $request->query('token');
        if (!$this->tokenCancelamentoValido($horario, $token)) { return $this->respostaLinkIndisponivel(); }
        $urlExecutar = URL::temporarySignedRoute('agendamento.cancelar.executar',
            Carbon::createFromTimestamp((int) $request->query('expires')), ['id' => $horario->id, 'token' => $token]);
        return response()->view('agendamentos.confirmar_cancelamento', compact('horario', 'urlExecutar'))
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
    public function executarCancelamentoDireto(Request $request, $id)
    {
        $cancelou = DB::transaction(function () use ($request, $id) {
            DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first();
            $horario = Horario::whereKey($id)->lockForUpdate()->first();
            if (!$this->tokenCancelamentoValido($horario, $request->query('token'))) { return false; }
            RegistroAtendimento::create(['id_horario_original' => $horario->id,
                    'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora, 'nome' => $horario->nome,
                'matricula' => $horario->matricula, 'status' => 'Cancelado pelo Aluno',
                'observacao' => 'Cancelamento confirmado via link do e-mail', 'data_registro' => now()]);
            $horario->update(['disponivel' => 1, 'nome' => null, 'matricula' => null, 'confirmado' => 0,
                'justificativa_cancelamento' => 'Cancelado pelo aluno via e-mail', 'token_cancelamento' => null]);
            return true;
        });
        if (!$cancelou) { return $this->respostaLinkIndisponivel(); }
        return response()->view('agendamentos.status_cancelamento', [
            'titulo' => 'Agendamento cancelado com sucesso!', 'mensagem' => 'Seu horário foi liberado no sistema.',
        ])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
    private function tokenCancelamentoValido(?Horario $horario, $token): bool
    {
        return $horario !== null && (int) $horario->disponivel === 0 && (int) $horario->confirmado !== 1
            && is_string($token) && strlen($token) === 64 && is_string($horario->token_cancelamento)
            && hash_equals($horario->token_cancelamento, $token);
    }
    private function respostaLinkIndisponivel()
    {
        return response()->view('agendamentos.status_cancelamento', [
            'titulo' => 'Link de cancelamento indisponível',
            'mensagem' => 'O agendamento associado a este link pode já ter sido cancelado ou encerrado. Nenhum atendimento foi alterado.',
        ], 410)->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
