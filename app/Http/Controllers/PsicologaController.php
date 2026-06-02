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

        $ultimosCancelamentos = DB::table('registros_atendimentos')
            ->where('status', 'Cancelado pelo Aluno')
            ->orderBy('data_registro', 'desc')
            ->take(5)
            ->get();

        $ultimosCancelamentos = collect($ultimosCancelamentos)->map(function ($cancelamento) {
            $horario = Horario::find($cancelamento->id_horario_original);
            
            $cancelamento->nome_aluno = $cancelamento->nome ?? 'Não informado';
            $cancelamento->matricula_aluno = $cancelamento->matricula ?? 'N/A';
            
            $cancelamento->data_atendimento = $horario ? $horario->data : $cancelamento->data_registro;
            $cancelamento->hora_atendimento = $horario ? $horario->hora : $cancelamento->data_registro;
            
            return $cancelamento;
        });

        return view('agenda', compact('ultimosCancelamentos'));
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
                $nome = $historico->nome;
                $matricula = $historico->matricula;
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
                        'id_horario_original' => $horario->id, 
                        'nome'                => $horario->nome ?? 'N/A',
                        'matricula'           => $horario->matricula ?? 'N/A',
                        'status'              => 'Realizado',
                        'observacao'          => 'Atendimento concluído com sucesso.',
                        'data_registro'       => \Carbon\Carbon::parse($horario->data . ' ' . $horario->hora)
                    ]);
                    $horario->delete();
                });

                return response()->json(['status' => 'success', 'message' => 'Atendimento confirmado e movido para o histórico.']);

            case 'cancel_by_psicologa':
                $horario = Horario::find($id);
                if (!$horario) return response()->json(['status' => 'error', 'message' => 'Horário não encontrado.']);

                $dataHoraAtendimento = \Carbon\Carbon::parse($horario->data . ' ' . $horario->hora);
                if ($dataHoraAtendimento->isPast()) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'Este atendimento já passou do horário atual e agora só pode ser Confirmado.'
                    ]);
                }

                $justificativa = $request->input('justificativa', 'Motivos operacionais.');

                $nomeAlunoSalvar = $horario->nome;
                $matriculaAlunoSalvar = $horario->matricula;

                DB::transaction(function () use ($horario, $justificativa, $nomeAlunoSalvar, $matriculaAlunoSalvar) {
                    $aluno = Usuario::where('matricula', $matriculaAlunoSalvar)->first();

                    RegistroAtendimento::create([
                        'id_horario_original' => $horario->id, 
                        'nome'                => $nomeAlunoSalvar ?? 'N/A',
                        'matricula'           => $matriculaAlunoSalvar ?? 'N/A',
                        'status'              => 'Cancelado pela Psicóloga', // Status correto!
                        'observacao'          => 'Motivo: ' . $justificativa,
                        'data_registro'       => now()
                    ]);

                    $horario->update([
                        'disponivel' => 1,
                        'nome' => null,
                        'matricula' => null,
                        'confirmado' => 0,
                        'justificativa_cancelamento' => $justificativa
                    ]);

                    if ($aluno && !empty($aluno->email)) {
                        $dataFormato = date('d/m/Y', strtotime($horario->data));
                        $horaFormato = date('H:i', strtotime($horario->hora));
                        
                        $corpoHtml = "Olá, <strong>{$nomeAlunoSalvar}</strong>!<br><br>Sua consulta em <strong>{$dataFormato}</strong> às <strong>{$horaFormato}</strong> foi cancelada.<br><strong>Motivo:</strong> {$justificativa}";

                        Mail::html($corpoHtml, function ($message) use ($aluno) {
                            $message->to($aluno->email)->subject('Aviso Urgente: Sua consulta foi cancelada');
                        });
                    }
                });

                return response()->json(['status' => 'success', 'message' => 'Agendamento cancelado e aluno notificado.']);

            case 'delete':
                $horario = Horario::find($id);
                if (!$horario) return response()->json(['status' => 'error', 'message' => 'Horário não encontrado.']);

                $dataHoraAtendimento = \Carbon\Carbon::parse($horario->data . ' ' . $horario->hora);
                if ($dataHoraAtendimento->isPast()) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'Não é possível excluir um horário do passado.'
                    ]);
                }

                $horario->delete();
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

            case 'generate_individual':
                $dataIndividual = $request->input('data_individual');
                $horaIndividual = $request->input('hora_individual');

                if (empty($dataIndividual) || empty($horaIndividual)) {
                    return response()->json(['status' => 'error', 'message' => 'Selecione a data e o horário.']);
                }

                $horaFormatada = strlen($horaIndividual) === 5 ? $horaIndividual . ':00' : $horaIndividual;

                $existe = Horario::where('data', $dataIndividual)->where('hora', $horaFormatada)->exists();
                if ($existe) {
                    return response()->json(['status' => 'error', 'message' => 'Este horário já está cadastrado para este dia!']);
                }

                Horario::create([
                    'data' => $dataIndividual,
                    'hora' => $horaFormatada,
                    'disponivel' => 1
                ]);

                return response()->json(['status' => 'success', 'message' => 'Horário avulso criado com sucesso.']);

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

            $query = DB::table('registros_atendimentos')
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
            $situacao = $reg->status === 'Realizado' ? 'Confirmado' : $reg->status;
            $statusClass = $reg->status === 'Realizado' ? 'color: #00833D;' : 'color: #dc3545;';
            
            $dataReg = \Carbon\Carbon::parse($reg->data_registro)->format('d/m/Y');
            $horaReg = \Carbon\Carbon::parse($reg->data_registro)->format('H:i');
            
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