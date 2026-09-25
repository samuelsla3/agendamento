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
                    ->orderBy('data_sessao', 'desc')
                    ->get();

        return response()
    ->view('prontuarios.show', compact('aluno', 'sessoes'))
    ->header('Cache-Control', 'no-store, private')
    ->header('Pragma', 'no-cache')
    ->header('Expires', '0');
    }

    public function validarSenha(Request $request)
{
    $request->validate([
        'senha' => 'required|string',
    ]);

    $matricula = session('matricula') 
              ?? session('usuario_matricula') 
              ?? (Auth::check() ? Auth::user()->matricula : null);

    $user = Usuario::where('matricula', $matricula)->first();

    if ($user) {
        // Pega a senha que estiver preenchida no banco (senha ou password)
        $senhaBanco = !empty($user->senha) ? $user->senha : $user->password;

        // Valida tanto por Hash quanto por texto puro
        // Aceita somente uma senha que corresponda ao hash armazenado.
$senhaValida = false;

if (is_string($senhaBanco) && $senhaBanco !== '') {
    try {
        $senhaValida = Hash::check(
            $request->input('senha'),
            $senhaBanco
        );
    } catch (\RuntimeException $e) {
        // Hash inválido ou incompatível: recusa o acesso.
        $senhaValida = false;
    }
}

        if ($senhaValida && !empty($senhaBanco)) {
            session(['prontuario_autorizado' => true]);

            $alunoId = session('aluno_id_pendente');
            session()->forget('aluno_id_pendente');

            if ($alunoId) {
                return redirect()->route('prontuarios.aluno', $alunoId);
            }

            return redirect()->route('psicologa.index');
        }
    }

    return redirect()->route('psicologa.index')
                     ->with('pedir_senha', true)
                     ->withErrors(['senha' => 'Senha de acesso incorreta ou não cadastrada.']);
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