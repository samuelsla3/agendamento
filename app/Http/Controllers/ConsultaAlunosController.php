<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultaAlunosController extends Controller
{
    private function resposta(array $dados)
    {
        return response()->json($dados)->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function alunos()
    {
        return Usuario::query()->whereIn('tipo', ['aluno', 'estudante']);
    }

    private function cadastro(Usuario $aluno): array
    {
        return ['id' => (int) $aluno->id, 'nome' => $aluno->nome,
            'matricula' => $aluno->matricula, 'turma' => $aluno->turma_formatada,
            'provisorio' => (bool) $aluno->cadastro_provisorio,
            'prontuario' => route('prontuarios.aluno', ['alunoId' => $aluno->id])];
    }

    public function index(Request $request)
    {
        $dados = $request->validate(['busca' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $busca = trim($dados['busca'] ?? '');
        $query = $this->alunos()->select('id', 'nome', 'matricula', 'turma_codigo', 'cadastro_provisorio');
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', '%' . $busca . '%')
                    ->orWhere('matricula', 'like', '%' . $busca . '%')
                    ->orWhere('turma_codigo', 'like', '%' . $busca . '%');
            });
        }
        $pagina = $query->orderBy('nome')->orderBy('id')->paginate(12);
        return $this->resposta(['alunos' => $pagina->getCollection()->map(fn ($a) => $this->cadastro($a))->values()->all(),
            'pagina' => $pagina->currentPage(), 'ultima_pagina' => $pagina->lastPage(), 'total' => $pagina->total()]);
    }

    public function show(Request $request, int $aluno)
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $pessoa = $this->alunos()->findOrFail($aluno);
        $matricula = trim((string) $pessoa->matricula);
        // Mesmo critério do relatório: histórico encerrado e reservas atuais, sem cópias de Agendado.
        // Não usa nome para associar atendimentos: homônimos nunca são misturados.
        $historico = DB::table('registros_atendimentos')->where('matricula', $matricula)
            ->where('status', '<>', 'Agendado');
        $abertos = DB::table('horarios')->where('matricula', $matricula)->where('disponivel', 0)
            ->where(fn ($q) => $q->where('confirmado', 0)->orWhereNull('confirmado'));
        if ($matricula === '') {
            $historico->whereRaw('1 = 0'); $abertos->whereRaw('1 = 0');
        }
        $status = (clone $historico)->select('status')->selectRaw('COUNT(*) as quantidade')
            ->groupBy('status')->pluck('quantidade', 'status');
        $agora = now(); $data = $agora->toDateString(); $hora = $agora->format('H:i:s');
        $futuros = (clone $abertos)->where(fn ($q) => $q->where('data', '>', $data)
            ->orWhere(fn ($q) => $q->where('data', $data)->where('hora', '>=', $hora)));
        $proximos = (clone $futuros)->select('data', 'hora')->orderBy('data')->orderBy('hora')->limit(10)->get();
        $ultimo = (clone $historico)->where('status', 'Realizado')->whereNotNull('data_atendimento')
            ->orderByDesc('data_atendimento')->orderByDesc('hora_atendimento')->first(['data_atendimento', 'hora_atendimento']);
        $registros = (clone $historico)->select('status', 'data_atendimento', 'hora_atendimento')
            ->orderByRaw('data_atendimento IS NULL ASC')->orderByDesc('data_atendimento')
            ->orderByDesc('hora_atendimento')->orderByDesc('id')->paginate(10);
        $quantidadeFuturos = (clone $futuros)->count(); $quantidadeAbertos = (clone $abertos)->count();
        return $this->resposta(['aluno' => $this->cadastro($pessoa), 'resumo' => [
            'realizados' => (int) ($status['Realizado'] ?? 0),
            'faltas' => (int) ($status['Não compareceu'] ?? 0),
            'cancelados_aluno' => (int) ($status['Cancelado pelo Aluno'] ?? 0),
            'cancelados_psicologa' => (int) ($status['Cancelado pela Psicóloga'] ?? 0),
            'proximos' => $quantidadeFuturos, 'pendentes_passados' => $quantidadeAbertos - $quantidadeFuturos,
            'ultimo_realizado' => $ultimo,
        ], 'proximos' => $proximos, 'historico' => $registros->items(),
            'pagina' => $registros->currentPage(), 'ultima_pagina' => $registros->lastPage(),
            'total_historico' => $registros->total(),
            'sem_matricula' => $matricula === '',
            'realizados_sem_data' => (clone $historico)->where('status', 'Realizado')->whereNull('data_atendimento')->count()]);
    }
}
