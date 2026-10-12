<?php

namespace App\Http\Controllers;

use App\Models\ProntuarioSessao;
use App\Models\Usuario;
use Illuminate\Http\Request;

class ProntuarioController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('psicologa.index');
    }

    public function show(Request $request, $alunoId)
    {
        if (!\App\Services\AcessoProntuario::valido($request, $request->user())) {
            session(['aluno_id_pendente' => $alunoId]);
            
            return redirect()->route('psicologa.index')->with('pedir_senha', true);
        }

        \App\Services\AcessoProntuario::exigir($request);

        $aluno = Usuario::where('id', $alunoId)
                        ->orWhere('matricula', $alunoId)
                        ->firstOrFail();

        $sessoes = ProntuarioSessao::where('aluno_id', $aluno->id)
                    ->orderBy('data_sessao', 'desc')
                    ->orderByRaw('hora_sessao IS NULL ASC')
                    ->orderBy('hora_sessao', 'desc')
                    ->orderBy('id', 'desc')
                    ->get();

        return response()
    ->view('prontuarios.show', compact('aluno', 'sessoes'))
    ->header('Cache-Control', 'no-store, private')
    ->header('Pragma', 'no-cache')
    ->header('Expires', '0');
    }

    public function validarSenha(Request $request)
    {
        $request->validate(['senha' => 'required|string|max:512']);
        $usuario = Usuario::whereKey($request->user()->id)->firstOrFail();
        abort_unless(\App\Services\SegurancaPsicologa::sessaoAtual($request, $usuario)
            && \App\Services\SegurancaPsicologa::configurada($usuario), 403);
        if (\App\Services\SegurancaPsicologa::conferir($request->input('senha'), $usuario->senha_prontuario)) {
            $request->session()->regenerate(true);
            \App\Services\AcessoProntuario::liberar($request, $usuario);
            $alunoId = $request->session()->pull('aluno_id_pendente');
            return redirect()->route($alunoId ? 'prontuarios.aluno' : 'psicologa.index', $alunoId ? [$alunoId] : []);
        }
        \App\Services\AcessoProntuario::bloquear($request);
        return redirect()->route('psicologa.index')->with('pedir_senha', true)
            ->withErrors(['senha' => 'Senha do prontuário incorreta. Use a senha específica do prontuário.']);
    }

    public function statusAcesso(Request $request)
    {
        $valido = \App\Services\AcessoProntuario::valido($request, $request->user());
        return response()->json(['valido' => $valido, 'agora' => now()->timestamp,
            'expira_em' => $valido ? (int) $request->session()->get('prontuario_ultimo_acesso')
                + 60 * config('psicologia.prontuario_inatividade_minutos', 30) : null])
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function renovarAcesso(Request $request)
    {
        // A atividade não revive um desbloqueio expirado ou revogado.
        \App\Services\AcessoProntuario::exigir($request);
        return $this->statusAcesso($request);
    }

    public function store(Request $request, $alunoId)
    {
        $dados = $request->validate([
            'anotacoes' => 'required|string',
            'data_sessao' => 'required|date_format:Y-m-d',
            'hora_sessao' => 'required|date_format:H:i',
            'horario_id' => 'nullable|exists:horarios,id',
        ], [
            'hora_sessao.required' => 'Informe o horário da sessão.',
            'hora_sessao.date_format' => 'Informe um horário válido no formato hora e minuto.',
        ]);

        ProntuarioSessao::create([
            'aluno_id' => $alunoId,
            'horario_id' => $request->horario_id,
            'anotacoes' => $request->anotacoes,
            'data_sessao' => $request->data_sessao,
            'hora_sessao' => $dados['hora_sessao'].':00',
        ]);

        return redirect()->back()->with('sucesso', 'Anotação registrada com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $dados = $request->validate([
            'anotacoes' => 'required|string',
            'data_sessao' => 'required|date_format:Y-m-d',
            // Permite manter registros antigos sem horário conhecido.
            'hora_sessao' => 'sometimes|nullable|date_format:H:i',
        ], [
            'hora_sessao.date_format' => 'Informe um horário válido no formato hora e minuto.',
        ]);

        $sessao = ProntuarioSessao::findOrFail($id);
        $alteracoes = [
            'anotacoes' => $request->anotacoes,
            'data_sessao' => $request->data_sessao,
        ];
        if (array_key_exists('hora_sessao', $dados)) {
            $alteracoes['hora_sessao'] = !empty($dados['hora_sessao']) ? $dados['hora_sessao'].':00' : null;
        }
        $sessao->update($alteracoes);

        return redirect()->back()->with('sucesso', 'Sessão atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $sessao = ProntuarioSessao::findOrFail($id);
        $sessao->delete();

        return redirect()->back()->with('sucesso', 'Sessão excluída com sucesso!');
    }
}
