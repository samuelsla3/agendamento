<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Horario;
use App\Models\RegistroAtendimento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use App\Services\AvisosEmail;
use Carbon\Carbon;

class PsicologaController extends Controller
{
public function index()
{
    if (!auth()->check() || auth()->user()->tipo !== 'psicologa') {
        return redirect()->route('login')->with('erro', 'Acesso negado.');
    }

    $hoje = \Carbon\Carbon::today()->toDateString();
    $agendamentosHoje = Horario::where('data', $hoje)
        ->where('disponivel', 0)
        ->orderBy('hora', 'asc')
        ->get();

    // Filtra para exibir apenas os cancelamentos cujos horários originais continuam disponíveis (disponivel = 1)
    $ultimosCancelamentos = DB::table('registros_atendimentos as ra')
        ->join('horarios as h', 'ra.id_horario_original', '=', 'h.id')
        ->where('ra.status', 'Cancelado pelo Aluno')
        ->where('h.disponivel', 1)
        ->select('ra.*')
        ->orderBy('ra.data_registro', 'desc')
        ->take(10)
        ->get();

    $ultimosCancelamentos = collect($ultimosCancelamentos)->map(function ($cancelamento) {
        $horario = Horario::find($cancelamento->id_horario_original);
        
        $cancelamento->nome_aluno = $cancelamento->nome ?? $cancelamento->nome_aluno ?? 'Não informado';
        $cancelamento->matricula_aluno = $cancelamento->matricula ?? $cancelamento->matricula_aluno ?? 'N/A';
        
        $cancelamento->data_atendimento = $horario ? $horario->data : $cancelamento->data_registro;
        $cancelamento->hora_atendimento = $horario ? $horario->hora : $cancelamento->data_registro;
        
        return $cancelamento;
    });

    $alunos = Usuario::whereIn(DB::raw('LOWER(tipo)'), ['aluno', 'estudante'])
        ->orderBy('nome', 'asc')
        ->get();

    return view('agenda', compact('ultimosCancelamentos', 'agendamentosHoje', 'alunos'));
}

    public function listarEventos()
    {
        $horarios = Horario::all();
        $eventos = [];

        foreach ($horarios as $row) {
            $nome = $row->nome;
            $matricula = $row->matricula;
            $statusReal = $row->confirmado ? 'Confirmado' : 'Agendado';

            if ($row->disponivel == 1 && !empty($row->justificativa_cancelamento)) {
                $historico = DB::table('registros_atendimentos')
                    ->where('id_horario_original', $row->id)
                    ->orderBy('data_registro', 'desc')
                    ->first();

                if ($historico) {
                    $nome = $historico->nome ?? $historico->nome_aluno ?? $nome;
                    $matricula = $historico->matricula ?? $historico->matricula_aluno ?? $matricula;
                    $statusReal = $historico->status; 
                } else {
                    $statusReal = 'Cancelado';
                }
            }

            $eventos[] = [
                'id' => $row->id,
                'title' => $nome ?? 'Disponível',
                'start' => $row->data . 'T' . $row->hora,
                'extendedProps' => [
                    'id' => $row->id,
                    'id_horario' => $row->id,
                    'versao' => $row->versao(),
                    'disponivel' => (int)$row->disponivel,
                    'nome' => $nome,
                    'matricula' => $matricula,
                    'confirmado' => (int)$row->confirmado,
                    'justificativa_cancelamento' => $row->justificativa_cancelamento,
                    'status_real' => $statusReal 
                ]
            ];
        }

        return response()->json($eventos);
    }


    // A rota usa RequisicaoUnica: todas as escritas de agenda são serializadas.
    public function processarAcao(Request $request)
    {
        $action = $request->input('action');
        if (in_array($action, ['confirmar', 'cancel_by_psicologa', 'delete', 'edit'])) {
            $request->validate(['id' => 'required|integer', 'versao' => 'required|string|size:64']);
            $horario = Horario::whereKey($request->input('id'))->lockForUpdate()->firstOrFail();
            $horario->conferirVersao($request->input('versao'));
        }
        switch ($action) {
            case 'confirmar':
                abort_if((int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1, 409, 'Atendimento já encerrado ou vaga sem reserva.');
                RegistroAtendimento::create(['id_horario_original' => $horario->id, 'nome' => $horario->nome,
                    'matricula' => $horario->matricula, 'status' => 'Realizado',
                    'observacao' => 'Atendimento concluído com sucesso.',
                    'data_registro' => Carbon::parse($horario->data.' '.$horario->hora)]);
                $horario->update(['confirmado' => 1, 'token_cancelamento' => null]);
                return response()->json(['status' => 'success', 'message' => 'Atendimento concluído e mantido no histórico!']);

            case 'cancel_by_psicologa':
                abort_if((int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1, 409, 'Atendimento já encerrado ou vaga sem reserva.');
                $request->validate(['justificativa' => 'nullable|string']);
                $justificativa = $request->input('justificativa') ?: 'Motivos operacionais.';
                $aluno = Usuario::where('matricula', $horario->matricula)->first();
                $nome = $horario->nome ?? $aluno?->nome ?? 'Discente';
                $token = $horario->token_cancelamento;
                RegistroAtendimento::create(['id_horario_original' => $horario->id, 'nome' => $nome,
                    'matricula' => $horario->matricula, 'status' => 'Cancelado pela Psicóloga',
                    'observacao' => 'Motivo: '.$justificativa, 'data_registro' => now()]);
                $horario->update(['disponivel' => 1, 'nome' => null, 'matricula' => null, 'confirmado' => 0,
                    'justificativa_cancelamento' => $justificativa, 'token_cancelamento' => null]);
                if ($aluno && $aluno->email) {
                    AvisosEmail::registrar('cancelado:'.$token, $aluno->email,
                        'Setor de Psicologia IFBA: Sua consulta foi cancelada',
                        'Olá, '.e($nome).'!<br><br>Sua consulta em '.Carbon::parse($horario->data)->format('d/m/Y')
                        .' às '.Carbon::parse($horario->hora)->format('H:i').' foi cancelada.<br><strong>Motivo:</strong> '.e($justificativa));
                }
                return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado. A notificação será enviada por e-mail quando houver contato cadastrado.']);

            case 'delete':
                if (Carbon::parse($horario->data.' '.$horario->hora)->isPast()) {
                    return response()->json(['status' => 'error', 'message' => 'Não é possível excluir um horário do passado.']);
                }
                $horario->delete();
                return response()->json(['status' => 'success', 'message' => 'Horário excluído com sucesso.']);

            case 'delete_specific_default':
                $request->validate(['data_inicio' => 'required|date', 'data_fim' => 'required|date|after_or_equal:data_inicio',
                    'horas' => 'required|array|min:1', 'horas.*' => 'date_format:H:i:s']);
                $removidos = Horario::where('disponivel', 1)->whereNull('nome')
                    ->whereBetween('data', [$request->data_inicio, $request->data_fim])
                    ->whereIn('hora', $request->horas)->delete();
                return response()->json(['status' => 'success', 'message' => "{$removidos} horários foram apagados com sucesso."]);

            case 'generate_default':
                $request->validate(['data_inicio' => 'required|date', 'data_fim' => 'required|date|after_or_equal:data_inicio',
                    'dias_semana' => 'required|array|min:1', 'dias_semana.*' => 'integer|between:1,7',
                    'horas_selecionadas' => 'required|array|min:1', 'horas_selecionadas.*' => 'date_format:H:i:s']);
                $inicio = Carbon::parse($request->data_inicio)->startOfDay();
                $fim = Carbon::parse($request->data_fim)->startOfDay();
                $criados = 0;
                for ($dia = $inicio->copy(); $dia->lte($fim); $dia->addDay()) {
                    if (!in_array($dia->dayOfWeekIso, $request->dias_semana)) { continue; }
                    foreach (array_unique($request->horas_selecionadas) as $hora) {
                        if (!Horario::where('data', $dia->toDateString())->where('hora', $hora)->exists()) {
                            Horario::create(['data' => $dia->toDateString(), 'hora' => $hora, 'disponivel' => 1]);
                            $criados++;
                        }
                    }
                }
                return response()->json(['status' => 'success', 'message' => "{$criados} horários customizados criados com sucesso."]);

            case 'generate_individual':
                $request->validate(['data_individual' => 'required|date', 'hora_individual' => 'required|date_format:H:i']);
                $hora = $request->hora_individual.':00';
                if (Horario::where('data', $request->data_individual)->where('hora', $hora)->exists()) {
                    return response()->json(['status' => 'error', 'message' => 'Este horário já está cadastrado para este dia!']);
                }
                Horario::create(['data' => $request->data_individual, 'hora' => $hora, 'disponivel' => 1]);
                return response()->json(['status' => 'success', 'message' => 'Horário avulso criado com sucesso.']);

            case 'edit':
                $request->validate(['data' => 'required|date', 'hora' => 'required|date_format:H:i']);
                $hora = $request->hora.':00';
                if (Horario::where('id', '<>', $horario->id)->where('data', $request->data)->where('hora', $hora)->exists()) {
                    return response()->json(['status' => 'error', 'message' => 'Já existe um horário nesta data e hora.']);
                }
                $horario->update(['data' => $request->data, 'hora' => $hora]);
                return response()->json(['status' => 'success', 'message' => 'Horário atualizado com sucesso.']);
        }
        return response()->json(['status' => 'error', 'message' => 'Ação inválida.']);
    }

    public function gerarRelatorio(Request $request)
    {
        if (!auth()->check() || auth()->user()->tipo !== 'psicologa') {
            return response('Acesso negado.', 403);
        }

        try {
            $nome = $request->filled('aluno_nome') ? $request->input('aluno_nome') : null;
            $matricula = $request->filled('aluno_matricula') ? $request->input('aluno_matricula') : null;
            $dataInicio = $request->input('data_inicio');
            $dataFim = $request->input('data_fim');
            $ordenarPor = $request->input('ordenar_por', 'data_desc');

            $query = DB::table('registros_atendimentos')
                ->when($nome, function ($query, $nome) {
                    $nomeTermo = '%' . mb_strtolower($nome, 'UTF-8') . '%';
                    return $query->whereRaw('LOWER(nome) LIKE ?', [$nomeTermo]);
                })
                ->when($matricula, function ($query, $matricula) {
                    return $query->where('matricula', 'like', "%{$matricula}%");
                })
                ->when($dataInicio, function ($query, $dataInicio) {
                    return $query->where('data_registro', '>=', $dataInicio . ' 00:00:00');
                })
                ->when($dataFim, function ($query, $dataFim) {
                    return $query->where('data_registro', '<=', $dataFim . ' 23:59:59');
                });

            switch ($ordenarPor) {
                case 'data_asc':
                    $query->orderBy('data_registro', 'asc');
                    break;
                case 'nome_asc':
                    $query->orderBy('nome', 'asc');
                    break;
                case 'situacao_asc':
                    $query->orderBy('status', 'asc');
                    break;
                case 'data_desc':
                default:
                    $query->orderBy('data_registro', 'desc');
                    break;
            }

            $registros = $query->get();

            $dataInicioFormatada = $dataInicio ? \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') : 'N/A';
            $dataFimFormatada = $dataFim ? \Carbon\Carbon::parse($dataFim)->format('d/m/Y') : 'N/A';
            $periodoStr = "Período do relatório: {$dataInicioFormatada} a {$dataFimFormatada}";

            return $this->renderTabelaFallback($registros, $periodoStr);

        } catch (\Exception $e) {
            return response()->json([
                'erro' => $e->getMessage(),
                'linha' => $e->getLine()
            ], 500);
        }
    }

    private function renderTabelaFallback($registros, $periodoStr)
    {
        if ($registros->isEmpty()) {
            return "<p style='color: #555;'>Nenhum agendamento encontrado para os filtros aplicados.</p>";
        }

        $html = "<h3>Total de registros encontrados: " . $registros->count() . "</h3>";
        $html .= "<p id='periodo-relatorio' style='font-style: italic; color: #555;'>{$periodoStr}</p>";
        
        $html .= "<table id='tabela-relatorio' style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <thead style='background-color: #f2f2f2;'>
                        <tr>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Aluno</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Matrícula</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Data e Hora</th>
                            <th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Situação</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($registros as $reg) {
            $situacao = $reg->status === 'Realizado' ? 'Atendimento Realizado' : $reg->status;
            $statusClass = $reg->status === 'Realizado' ? 'color: #00833D;' : 'color: #dc3545;';
            
            $dataReg = \Carbon\Carbon::parse($reg->data_registro)->format('d/m/Y');
            $horaReg = \Carbon\Carbon::parse($reg->data_registro)->format('H:i');
            
            $nomeExibir = $reg->nome ?? $reg->nome_aluno ?? 'Não informado';
            $matriculaExibir = $reg->matricula ?? $reg->matricula_aluno ?? 'N/A';

            $html .= "<tr>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$nomeExibir}</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$matriculaExibir}</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'>{$dataReg} às {$horaReg}</td>
                        <td style='padding: 8px; border: 1px solid #ddd;'><strong style='{$statusClass}'>{$situacao}</strong></td>
                    </tr>";
        }

        $html .= "</tbody></table>";
        return $html;
    }
}
