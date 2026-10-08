<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConfiguracaoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        return response()->view('configuracoes.index', [
            'configuracao' => ConfiguracaoSistema::findOrFail(1),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function atualizarAviso(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        // Só normaliza strings: valores de outros tipos seguem para a validação.
        if (is_string($request->input('aviso_texto'))) {
            $request->merge(['aviso_texto' => trim($request->input('aviso_texto'))]);
        }

        $dados = $request->validate([
            'aviso_ativo' => ['required', 'boolean'],
            'aviso_texto' => ['nullable', 'string', 'max:2000',
                Rule::requiredIf(fn () => $request->boolean('aviso_ativo'))],
            'versao' => ['required', 'integer', 'min:1'],
        ], [
            'aviso_ativo.required' => 'Informe se o aviso deve ser exibido.',
            'aviso_ativo.boolean' => 'Escolha uma opção válida para exibir o aviso.',
            'aviso_texto.required' => 'Escreva o recado antes de ativar o aviso.',
            'aviso_texto.string' => 'Informe o recado em texto.',
            'aviso_texto.max' => 'O recado pode ter até 2.000 caracteres.',
        ]);

        $alterados = ConfiguracaoSistema::query()->whereKey(1)
            ->where('versao', $dados['versao'])
            ->update([
                'aviso_ativo' => $request->boolean('aviso_ativo'),
                'aviso_texto' => ($dados['aviso_texto'] ?? '') !== '' ? $dados['aviso_texto'] : null,
                'versao' => DB::raw('versao + 1'),
            ]);

        if ($alterados !== 1) {
            return redirect()->route('configuracoes.index')->withErrors([
                'aviso_texto' => 'A configuração mudou em outra aba. Confira os dados atuais e salve novamente.',
            ]);
        }

        return redirect()->route('configuracoes.index')->with('sucesso',
            $request->boolean('aviso_ativo')
                ? 'Aviso salvo e ativado para os alunos.'
                : 'Recado salvo. A exibição do aviso está desativada.');
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