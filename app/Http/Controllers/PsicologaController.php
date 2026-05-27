<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Horario;
use App\Models\RegistroAtendimento;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PsicologaController extends Controller
{
    public function index()
    {
        if (!auth()->check() || auth()->user()->tipo !== 'psicologa') {
            return redirect()->route('login')->with('erro', 'Acesso negado.');
        }

        $ultimosCancelamentos = RegistroAtendimento::where('status', 'Cancelado pelo Aluno')
            ->orderBy('data_registro', 'desc')
            ->limit(5)
            ->get();

        return view('agenda', compact('ultimosCancelamentos'));
    }

    public function listarEventos()
    {
        $horarios = Horario::all();
        $eventos = [];

        foreach ($horarios as $row) {
            $eventos[] = [
                'id' => $row->id,
                'title' => $row->nome ?? 'Disponível',
                'start' => $row->data . 'T' . $row->hora,
                'extendedProps' => [
                    'disponivel' => (int)$row->disponivel,
                    'nome' => $row->nome,
                    'matricula' => $row->matricula,
                    'confirmado' => (int)$row->confirmado,
                    'justificativa_cancelamento' => $row->justificativa_cancelamento
                ]
            ];
        }

        return response()->json($eventos);
    }

    public function processarAcao(Request $request)
    {
        $action = $request->input('action');
        $id = $request->input('id');

        switch ($action) {
            case 'confirmar':
                $horario = Horario::find($id);
                if (!$horario) return response()->json(['status' => 'error', 'message' => 'Horário não encontrado.']);

                DB::transaction(function () use ($horario) {
                    RegistroAtendimento::create([
                        'nome_aluno'       => $horario->nome ?? 'N/A',
                        'matricula_aluno'  => $horario->matricula ?? 'N/A',
                        'data_atendimento' => $horario->data,
                        'status'           => 'Realizado',
                        'observacao'       => 'Atendimento concluído com sucesso.'
                    ]);
                    $horario->delete();
                });

                return response()->json(['status' => 'success', 'message' => 'Atendimento confirmado e movido para o histórico.']);

            case 'cancel_by_psicologa':
                $horario = Horario::find($id);
                $justificativa = $request->input('justificativa', 'Motivos operacionais.');

                if (!$horario) return response()->json(['status' => 'error', 'message' => 'Horário não encontrado.']);

                DB::transaction(function () use ($horario, $justificativa) {
                    $aluno = Usuario::where('matricula', $horario->matricula)->first();

                    RegistroAtendimento::create([
                        'nome_aluno'       => $horario->nome ?? 'N/A',
                        'matricula_aluno'  => $horario->matricula ?? 'N/A',
                        'data_atendimento' => $horario->data,
                        'status'           => 'Cancelado pela Psicóloga',
                        'observacao'       => 'Motivo: ' . $justificativa
                    ]);

                    $horario->update([
                        'disponivel' => 1,
                        'nome' => null,
                        'matricula' => null,
                        'confirmado' => 0,
                        'justificativa_cancelamento' => null
                    ]);

                    if ($aluno && !empty($aluno->email)) {
                        $dataFormato = date('d/m/Y', strtotime($horario->data));
                        $horaFormato = date('H:i', strtotime($horario->hora));
                        
                        $corpoHtml = "Olá, <strong>{$horario->nome}</strong>!<br><br>Sua consulta em <strong>{$dataFormato}</strong> às <strong>{$horaFormato}</strong> foi cancelada.<br><strong>Motivo:</strong> {$justificativa}";

                        Mail::html($corpoHtml, function ($message) use ($aluno) {
                            $message->to($aluno->email)->subject('Aviso Urgente: Sua consulta foi cancelada');
                        });
                    }
                });

                return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado e aluno notificado.']);

            case 'delete':
                Horario::where('id', $id)->delete();
                return response()->json(['status' => 'success', 'message' => 'Horário excluído com sucesso.']);

            case 'delete_specific_default':
                $dataInicio = $request->input('data_inicio');
                $dataFim = $request->input('data_fim');
                $horas = $request->input('horas', []);

                if (empty($dataInicio) || empty($dataFim) || empty($horas)) {
                    return response()->json(['status' => 'error', 'message' => 'Preencha o período e selecione as horas.']);
                }

                $removidos = Horario::where('disponivel', 1)
                    ->whereNull('nome')
                    ->whereBetween('data', [$dataInicio, $dataFim])
                    ->whereIn('hora', $horas)
                    ->delete();

                return response()->json(['status' => 'success', 'message' => "{$removidos} horários foram apagados com sucesso."]);

            case 'generate_default':
                $diasSemana = $request->input('dias_semana', []);
                $dataInicio = new \DateTime($request->input('data_inicio'));
                $dataFim = new \DateTime($request->input('data_fim'));
                $dataFim->modify('+1 day');

                $horariosPadrao = $request->input('horas_selecionadas', ['09:00:00', '10:00:00', '11:00:00', '14:00:00', '15:00:00', '16:00:00']);
                
                if (empty($diasSemana)) {
                    return response()->json(['status' => 'error', 'message' => 'Selecione pelo menos um dia da semana.']);
                }

                $periodo = new \DatePeriod($dataInicio, new \DateInterval('P1D'), $dataFim);
                $criados = 0;

                foreach ($periodo as $dia) {
                    if (in_array((int)$dia->format('N'), $diasSemana)) {
                        foreach ($horariosPadrao as $hora) {
                            $dataStr = $dia->format('Y-m-d');
                            
                            $existe = Horario::where('data', $dataStr)->where('hora', $hora)->exists();
                            if (!$existe) {
                                Horario::create([
                                    'data' => $dataStr,
                                    'hora' => $hora,
                                    'disponivel' => 1
                                ]);
                                $criados++;
                            }
                        }
                    }
                }

                return response()->json(['status' => 'success', 'message' => "{$criados} horários customizados criados com sucesso."]);

            case 'edit':
                Horario::where('id', $id)->update([
                    'data' => $request->input('data'),
                    'hora' => $request->input('hora')
                ]);
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
        $matricula = $request->input('aluno_matricula');
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $ordenarPor = $request->input('ordenar_por', 'data_desc');

        $query = \App\Models\Horario::query()
            ->whereNotNull('matricula') 
            ->when($matricula, function ($query, $matricula) {
                return $query->where('matricula', 'like', "%{$matricula}%");
            })
            ->when($dataInicio, function ($query, $dataInicio) {
                return $query->where('data', '>=', $dataInicio);
            })
            ->when($dataFim, function ($query, $dataFim) {
                return $query->where('data', '<=', $dataFim);
            });

        switch ($ordenarPor) {
            case 'data_asc':
                $query->orderBy('data', 'asc')->orderBy('hora', 'asc');
                break;
            case 'nome_asc':
                $query->orderBy('nome', 'asc');
                break;
            case 'situacao_asc':
                $query->orderBy('confirmado', 'desc')->orderBy('data', 'desc');
                break;
            case 'data_desc':
            default:
                $query->orderBy('data', 'desc')->orderBy('hora', 'desc');
                break;
        }

        $registros = $query->get();

        $dataInicioFormatada = $dataInicio ? \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') : 'N/A';
        $dataFimFormatada = $dataFim ? \Carbon\Carbon::parse($dataFim)->format('d/m/Y') : 'N/A';
        $periodoStr = "Período da consulta: {$dataInicioFormatada} a {$dataFimFormatada}";

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
        $situacao = $reg->confirmado ? 'Confirmado' : 'Agendado pelo Aluno';
        
        $statusClass = $reg->confirmado ? 'color: #00833D;' : 'color: #d97706;';
        
        $dataReg = \Carbon\Carbon::parse($reg->data)->format('d/m/Y');
        $horaReg = \Carbon\Carbon::parse($reg->hora)->format('H:i');
        
        $html .= "<tr>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$reg->nome}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$reg->matricula}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>{$dataReg} às {$horaReg}</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'><strong style='{$statusClass}'>{$situacao}</strong></td>
                </tr>";
    }

    $html .= "</tbody></table>";
    return $html;
}
}