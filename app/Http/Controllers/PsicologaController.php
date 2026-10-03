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
    $agendamentosHoje = Horario::with('usuario')
    ->where('data', $hoje)
    ->where('disponivel', 0)
    ->orderBy('hora', 'asc')
    ->get();

    // Filtra para exibir apenas os cancelamentos cujos horários originais continuam disponíveis (disponivel = 1)
    $ultimosCancelamentos = DB::table('registros_atendimentos as ra')
    ->join('horarios as h', 'ra.id_horario_original', '=', 'h.id')
    ->leftJoin('usuarios as u', 'ra.matricula', '=', 'u.matricula')
    ->where('ra.status', 'Cancelado pelo Aluno')
    ->where('h.disponivel', 1)
    ->select('ra.*', 'u.turma_codigo as turma_codigo_aluno')
    ->orderBy('ra.data_registro', 'desc')
    ->take(10)
    ->get();

    $ultimosCancelamentos = collect($ultimosCancelamentos)->map(function ($cancelamento) {

        $cancelamento->nome_aluno = $cancelamento->nome ?? $cancelamento->nome_aluno ?? 'Não informado';
        $cancelamento->matricula_aluno = $cancelamento->matricula ?? $cancelamento->matricula_aluno ?? 'N/A';

        $cancelamento->turma_formatada_aluno = Usuario::formatarTurma(
    $cancelamento->turma_codigo_aluno ?? null
);

        // data_atendimento e hora_atendimento vêm de ra.*, sem consultar a vaga atual.

        return $cancelamento;
    });

    $alunos = Usuario::whereIn(DB::raw('LOWER(tipo)'), ['aluno', 'estudante'])
        ->orderBy('nome', 'asc')
        ->get();

    $turmasRelatorio = $this->listarTurmasRelatorio($alunos->pluck('turma_codigo'));

    return view('agenda', compact('ultimosCancelamentos', 'agendamentosHoje', 'alunos', 'turmasRelatorio'));
}

    public function listarEventos()
    {
        abort_unless(auth()->check() && auth()->user()->tipo === 'psicologa', 403);

        $horarios = Horario::with('usuario')->get();
        $eventos = [];

        foreach ($horarios as $row) {
            $dataHoraReservaAnterior = null;
            $nome = $row->nome;
            $matricula = $row->matricula;
            $turmaCodigo = $row->usuario?->turma_codigo;
            $statusReal = $row->confirmado ? 'Confirmado' : 'Agendado';

            if ($row->disponivel == 1 && !empty($row->justificativa_cancelamento)) {
                $historico = DB::table('registros_atendimentos as ra')
                    ->leftJoin('usuarios as u', 'ra.matricula', '=', 'u.matricula')
                    ->select('ra.*', 'u.turma_codigo as turma_codigo_aluno')
                    ->where('ra.id_horario_original', $row->id)
                    ->orderBy('ra.data_registro', 'desc')
                    ->first();

                if ($historico) {
                    $nome = $historico->nome ?? $historico->nome_aluno ?? $nome;
                    $matricula = $historico->matricula ?? $historico->matricula_aluno ?? $matricula;
                    // A vaga liberada pode estar sem aluno; usa a matrícula do histórico.
                    $turmaCodigo = $historico->turma_codigo_aluno ?? null;
                    $statusReal = $historico->status; 
                    // Usa a data e a hora preservadas no histórico do atendimento.
if (
    !empty($historico->data_atendimento)
    && !empty($historico->hora_atendimento)
) {
    $dataHoraReservaAnterior =
        Carbon::parse($historico->data_atendimento)->format('d/m/Y')
        . ' às '
        . Carbon::parse($historico->hora_atendimento)->format('H:i');
}
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
                    'turma_formatada' => $this->normalizarTurmaRelatorio($turmaCodigo) ?: 'Não informada',
                    'confirmado' => (int)$row->confirmado,
                    'justificativa_cancelamento' => $row->justificativa_cancelamento,
                    'status_real' => $statusReal,
                    'data_hora_reserva_anterior' => $dataHoraReservaAnterior
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
                RegistroAtendimento::create(['id_horario_original' => $horario->id,
                    'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora, 'nome' => $horario->nome,
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
                RegistroAtendimento::create(['id_horario_original' => $horario->id,
                    'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora, 'nome' => $nome,
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

    // Catálogo encontrado na tabela turmas do banco do projeto SCAAE.
    // Somado às turmas do cadastro local, sem importar o banco do outro projeto.
    private function listarTurmasRelatorio($codigos): array
    {
        $catalogo = [
            '1.18.1I', '2.18.1I', '3.18.1I', '4.18.1I',
            '1.18.2I', '2.18.2I', '3.18.2I', '4.18.2I',
            '1.28.1I', '2.28.1I', '3.28.1I', '4.28.1I',
            '1.28.2I', '2.28.2I', '3.28.2I', '4.28.2I',
        ];

        return collect($catalogo)->merge(collect($codigos)->map(
            fn ($codigo) => $this->normalizarTurmaRelatorio($codigo)
        ))->filter(fn ($codigo) => $codigo !== '')->unique()->sort(SORT_NATURAL)->values()->all();
    }

    private function normalizarTurmaRelatorio(?string $codigo): string
    {
        $codigo = mb_strtoupper(trim($codigo ?? ''), 'UTF-8');
        // SUAP: 20261.4.18.1I -> 4.18.1I, mantendo turmas de outros formatos.
        $codigo = preg_replace('/^\d{4}[12]\.(?=\d+\.\d+\.[A-Z0-9]+$)/', '', $codigo);
        // Catálogo do outro projeto: 4181I -> 4.18.1I.
        return preg_replace('/^([1-4])(18|28)([12]I)$/', '$1.$2.$3', $codigo);
    }

    public function gerarRelatorio(Request $request)
    {
        if (!auth()->check() || auth()->user()->tipo !== 'psicologa') {
            return response('Acesso negado.', 403);
        }

        // Validação fora do try: dados inválidos devem retornar 422, não 500.
        $filtros = $request->validate([
            'aluno_nome' => ['nullable', 'string', 'max:255'],
            'aluno_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'turma' => ['nullable', 'string', 'max:50'],
            'aluno_matricula' => ['nullable', 'string', 'max:100'],
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_fim' => array_merge(['nullable', 'date_format:Y-m-d'],
                $request->filled('data_inicio') ? ['after_or_equal:data_inicio'] : []),
            'situacao' => ['nullable', 'in:realizados,todos,cancelados_aluno,cancelados_psicologa,cancelados'],
            'ordenar_por' => ['nullable', 'in:data_asc,data_desc,nome_asc,situacao_asc'],
        ], [
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'data_inicio.date_format' => 'Informe uma data inicial válida.',
            'data_fim.date_format' => 'Informe uma data final válida.',
            'situacao.in' => 'Selecione uma situação válida.',
            'aluno_id.integer' => 'Selecione um aluno válido na lista.',
            'aluno_id.exists' => 'O aluno selecionado não está mais cadastrado. Recarregue a página.',
        ]);

        try {
            $nome = trim($filtros['aluno_nome'] ?? '');
            $alunoSelecionado = !empty($filtros['aluno_id'])
                ? Usuario::select('id', 'nome', 'matricula')->find($filtros['aluno_id'])
                : null;
            if (!empty($filtros['aluno_id']) && !$alunoSelecionado) {
                return response()->json(['errors' => ['aluno_id' => [
                    'O aluno selecionado não está mais cadastrado. Recarregue a página.',
                ]]], 422);
            }
            $turma = $this->normalizarTurmaRelatorio($filtros['turma'] ?? null);
            $matricula = trim($filtros['aluno_matricula'] ?? '');
            $dataInicio = $filtros['data_inicio'] ?? null;
            $dataFim = $filtros['data_fim'] ?? null;
            $situacao = $filtros['situacao'] ?? 'realizados';
            $ordenarPor = $filtros['ordenar_por'] ?? 'data_desc';
            $rotulos = [
                'realizados' => 'Atendimentos realizados',
                'todos' => 'Todos os registros',
                'cancelados_aluno' => 'Cancelados pelo aluno',
                'cancelados_psicologa' => 'Cancelados pela psicóloga',
                'cancelados' => 'Todos os cancelados',
            ];
            $statusPorFiltro = [
                'realizados' => ['Realizado'],
                'cancelados_aluno' => ['Cancelado pelo Aluno'],
                'cancelados_psicologa' => ['Cancelado pela Psicóloga'],
                'cancelados' => ['Cancelado pelo Aluno', 'Cancelado pela Psicóloga'],
            ];

            $query = DB::table('registros_atendimentos as ra')
                ->leftJoin('usuarios as u', 'ra.matricula', '=', 'u.matricula')
                ->select('ra.*', 'u.turma_codigo as turma_codigo_aluno');
            $turmaExata = false;
            if ($turma !== '') {
                $codigos = Usuario::whereNotNull('turma_codigo')->distinct()->pluck('turma_codigo');
                $turmaExata = in_array($turma, $this->listarTurmasRelatorio($codigos), true);
                // Código completo: turma específica. Texto parcial: início do código formatado.
                // Ex.: 3 encontra terceiros anos; 3.18 encontra os terceiros anos do curso 18.
                $correspondentes = $codigos->filter(function ($codigo) use ($turma, $turmaExata) {
                    $formatada = $this->normalizarTurmaRelatorio($codigo);
                    return $turmaExata
                        ? $formatada === $turma
                        : str_starts_with($formatada, $turma);
                })->values()->all();
                // Mantém parâmetros no SQL; entrada sem correspondências gera resultado vazio.
                $query->whereIn('u.turma_codigo', $correspondentes);
            }
            if ($alunoSelecionado) {
                // Seleção exata evita misturar homônimos e mantém o histórico mesmo após mudança de nome.
                $query->where('u.id', $alunoSelecionado->id);
            } elseif ($nome !== '') {
                $query->whereRaw('LOWER(ra.nome) LIKE ?', ['%' . mb_strtolower($nome, 'UTF-8') . '%']);
            }
            if ($matricula !== '') {
                // Preserva a pesquisa por trecho de matrícula já existente.
                $query->where('ra.matricula', 'like', '%' . $matricula . '%');
            }
            if ($dataInicio) {
                $query->where('ra.data_atendimento', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->where('ra.data_atendimento', '<=', $dataFim);
            }
            if ($situacao !== 'todos') {
                $query->whereIn('ra.status', $statusPorFiltro[$situacao]);
            }

            switch ($ordenarPor) {
                case 'nome_asc':
                    $query->orderBy('ra.nome');
                    break;
                case 'situacao_asc':
                    $query->orderBy('ra.status');
                    break;
                default:
                    $direcao = $ordenarPor === 'data_asc' ? 'asc' : 'desc';
                    $query->orderByRaw('ra.data_atendimento IS NULL ASC')
                        ->orderBy('ra.data_atendimento', $direcao)
                        ->orderBy('ra.hora_atendimento', $direcao);
            }
            $registros = $query->orderBy('ra.id')->get();

            if ($dataInicio && $dataFim) {
                $periodoStr = 'Período selecionado: ' . Carbon::parse($dataInicio)->format('d/m/Y')
                    . ' a ' . Carbon::parse($dataFim)->format('d/m/Y');
            } elseif ($dataInicio) {
                $periodoStr = 'Período selecionado: a partir de ' . Carbon::parse($dataInicio)->format('d/m/Y');
            } elseif ($dataFim) {
                $periodoStr = 'Período selecionado: até ' . Carbon::parse($dataFim)->format('d/m/Y');
            } else {
                $periodoStr = 'Período: todos os registros';
            }

            // As contagens partem do MESMO resultado da tabela, após todos os filtros.
            $realizados = $registros->filter(fn ($reg) => $reg->status === 'Realizado');
            $matriculas = $realizados->map(fn ($reg) => trim((string) ($reg->matricula ?? '')));
            $indicadores = [
                'atendimentos' => $realizados->count(),
                'alunos' => $matriculas->filter(fn ($valor) => $valor !== '')->uniqueStrict()->count(),
                'sem_matricula' => $matriculas->filter(fn ($valor) => $valor === '')->count(),
            ];
            $avisos = [];
            if ($dataInicio || $dataFim) {
                $avisos[] = 'O período considera a data marcada. Registros sem essa data ficam fora do resultado.';
            } elseif ($registros->contains(fn ($reg) => empty($reg->data_atendimento))) {
                $avisos[] = 'Este resultado inclui registros antigos sem data marcada preservada.';
            }
            if ($indicadores['sem_matricula'] > 0) {
                $avisos[] = $indicadores['sem_matricula'] . ' atendimento(s) realizado(s) sem matrícula: '
                    . 'incluído(s) no total de atendimentos, mas não no total de alunos distintos.';
            }

            return $this->renderTabelaFallback($registros, $periodoStr, $indicadores, [
                'situacao' => $rotulos[$situacao],
                'turma' => $turma === '' ? 'Todas' : ($turmaExata ? $turma : 'Começa com ' . $turma),
                'aluno' => ($alunoSelecionado
                    ? 'Aluno: ' . $alunoSelecionado->nome . ' | Matrícula: ' . $alunoSelecionado->matricula
                    : 'Nome contém: ' . ($nome !== '' ? $nome : 'qualquer nome'))
                    . ' | Matrícula contém: ' . ($matricula !== '' ? $matricula : 'qualquer matrícula'),
                'avisos' => implode(' ', $avisos),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['erro' => 'Não foi possível gerar o relatório. Tente novamente.'], 500);
        }
    }

    private function renderTabelaFallback($registros, $periodoStr, array $indicadores, array $contexto)
    {
        $html = '<div id="resumo-relatorio">';
        $html .= '<p id="indicadores-relatorio" style="font-size:18px;font-weight:bold;color:#00833D;">'
            . 'Atendimentos realizados: ' . $indicadores['atendimentos']
            . ' · Alunos atendidos: ' . $indicadores['alunos'] . '</p>';
        $html .= '<p id="total-registros-relatorio">Total de registros encontrados: ' . $registros->count() . '</p>';
        // Metadados do resultado para o PDF; os seletores já mostram os filtros na tela.
        $html .= '<p id="periodo-relatorio" hidden>' . e($periodoStr) . '</p>';
        $html .= '<p id="situacao-relatorio" hidden>Situação: ' . e($contexto['situacao']) . '</p>';
        $html .= '<p id="aluno-relatorio" hidden>' . e($contexto['aluno']) . '</p>';
        $html .= '<p id="turma-relatorio" hidden>Turma (cadastro atual): ' . e($contexto['turma']) . '</p>';
        $html .= '<p id="avisos-relatorio" style="color:#555;">' . e($contexto['avisos']) . '</p></div>';

        if ($registros->isEmpty()) {
            return $html . '<p id="sem-resultados">Nenhum agendamento encontrado para os filtros aplicados.</p>';
        }

        $html .= '<table id="tabela-relatorio" style="width:100%;border-collapse:collapse;margin-top:15px;">'
            . '<thead style="background-color:#f2f2f2;"><tr>';
        foreach (['Aluno', 'Turma', 'Matrícula', 'Data e hora marcadas', 'Situação'] as $coluna) {
            $html .= '<th style="padding:8px;border:1px solid #ddd;text-align:left;">' . $coluna . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($registros as $reg) {
            $situacao = $reg->status === 'Realizado' ? 'Atendimento Realizado' : $reg->status;
            $cor = $reg->status === 'Realizado' ? '#00833D' : '#555';
            if (in_array($reg->status, ['Cancelado pelo Aluno', 'Cancelado pela Psicóloga'], true)) {
                $cor = '#b42318';
            }
            // Nunca reconstrói a data usando uma vaga que pode ter sido reutilizada.
            if (!empty($reg->data_atendimento) && !empty($reg->hora_atendimento)) {
                $dataHora = Carbon::parse($reg->data_atendimento)->format('d/m/Y')
                    . ' às ' . Carbon::parse($reg->hora_atendimento)->format('H:i');
            } else {
                $dataHora = 'Data/hora não preservadas neste registro antigo';
            }
            $html .= '<tr>';
            $turmaAluno = $this->normalizarTurmaRelatorio($reg->turma_codigo_aluno ?? null);
            foreach ([$reg->nome ?? 'Não informado', $turmaAluno !== '' ? $turmaAluno : 'Não informada',
                $reg->matricula ?? 'Não informada', $dataHora] as $valor) {
                $html .= '<td style="padding:8px;border:1px solid #ddd;">' . e($valor) . '</td>';
            }
            $html .= '<td style="padding:8px;border:1px solid #ddd;"><strong style="color:' . $cor . ';">'
                . e($situacao) . '</strong></td></tr>';
        }
        return $html . '</tbody></table>';
    }
}
