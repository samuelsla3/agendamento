<?php

namespace App\Http\Controllers;

use App\Models\ProntuarioSessao;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProntuarioController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('psicologa.index');
    }

    public function show(Request $request, $alunoId)
    {
        if (!session()->get('prontuario_autorizado')) {
            session(['aluno_id_pendente' => $alunoId]);
            
            return redirect()->route('psicologa.index')->with('pedir_senha', true);
        }

        $aluno = Usuario::where('id', $alunoId)
                        ->orWhere('matricula', $alunoId)
                        ->firstOrFail();

        $sessoes = ProntuarioSessao::where('aluno_id', $aluno->id)
                    ->orderBy('data_sessao', 'asc')
                    ->get();

        return view('prontuarios.show', compact('aluno', 'sessoes'));
    }

    public function validarSenha(Request $request)
    {
        $request->validate([
            'senha' => 'required|string',
        ]);

        $user = Auth::user();

        if (Hash::check($request->senha, $user->password ?? $user->senha)) {
            session(['prontuario_autorizado' => true]);

            $alunoId = session('aluno_id_pendente');
            session()->forget('aluno_id_pendente');

            if ($alunoId) {
                return redirect()->route('prontuarios.aluno', $alunoId);
            }

            return redirect()->route('psicologa.index');
        }

        return redirect()->route('psicologa.index')
                         ->with('pedir_senha', true)
                         ->withErrors(['senha' => 'Senha de acesso incorreta. Acesso negado.']);
    }

    public function store(Request $request, $alunoId)
    {
        $request->validate([
            'anotacoes' => 'required|string',
            'data_sessao' => 'required|date',
            'horario_id' => 'nullable|exists:horarios,id',
        ]);

        ProntuarioSessao::create([
            'aluno_id' => $alunoId,
            'horario_id' => $request->horario_id,
            'anotacoes' => $request->anotacoes,
            'data_sessao' => $request->data_sessao,
        ]);

        return redirect()->back()->with('sucesso', 'Anotação registrada com sucesso!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'anotacoes' => 'required|string',
            'data_sessao' => 'required|date',
        ]);

        $sessao = ProntuarioSessao::findOrFail($id);
        $sessao->update([
            'anotacoes' => $request->anotacoes,
            'data_sessao' => $request->data_sessao,
        ]);

        return redirect()->back()->with('sucesso', 'Sessão atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $sessao = ProntuarioSessao::findOrFail($id);
        $sessao->delete();

        return redirect()->back()->with('sucesso', 'Sessão excluída com sucesso!');
    }
}