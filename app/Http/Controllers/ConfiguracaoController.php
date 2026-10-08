<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracaoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        return response()->view('configuracoes.index', [
            'configuracao' => ConfiguracaoSistema::findOrFail(1),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function atualizar(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        $dados = $request->validate([
            'horario_lembretes' => ['required', 'date_format:H:i'],
            'versao' => ['required', 'integer', 'min:1'],
        ], [
            'horario_lembretes.required' => 'Escolha o horário de envio.',
            'horario_lembretes.date_format' => 'Informe um horário válido, como 09:00.',
        ]);

        // Evita que uma aba antiga sobrescreva uma alteração mais recente.
        $alterados = ConfiguracaoSistema::query()->whereKey(1)
            ->where('versao', $dados['versao'])
            ->update([
                'horario_lembretes' => $dados['horario_lembretes'],
                'versao' => DB::raw('versao + 1'),
            ]);

        if ($alterados !== 1) {
            return redirect()->route('configuracoes.index')->withErrors([
                'horario_lembretes' => 'A configuração mudou em outra aba. Confira o horário atual e salve novamente.',
            ]);
        }

        return redirect()->route('configuracoes.index')
            ->with('sucesso', 'Horário dos lembretes atualizado.');
    }
}