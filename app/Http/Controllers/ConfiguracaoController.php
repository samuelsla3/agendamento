<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracaoSistema;
use App\Services\AvisosEmail;
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
            'problemasEmail' => AvisosEmail::contarProblemas(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function atualizarCalendario(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);
        $dados = $request->validate([
            'calendario_visualizacao' => ['required', 'in:padrao,mes,semana,lista'],
            'calendario_faixa_ativa' => ['required', 'boolean'],
            'calendario_hora_inicio' => ['required', 'date_format:H:i'],
            'calendario_hora_fim' => ['required', 'date_format:H:i', 'after:calendario_hora_inicio'],
            'versao' => ['required', 'integer', 'min:1'],
        ], [
            'calendario_visualizacao.required' => 'Escolha a visualização inicial do calendário.',
            'calendario_visualizacao.in' => 'Escolha mês, semana, lista ou o padrão atual.',
            'calendario_faixa_ativa.required' => 'Informe se deseja utilizar uma faixa de horas.',
            'calendario_faixa_ativa.boolean' => 'Escolha uma opção válida para a faixa de horas.',
            'calendario_hora_inicio.required' => 'Informe o início da faixa.',
            'calendario_hora_inicio.date_format' => 'Informe um início válido, como 08:00.',
            'calendario_hora_fim.required' => 'Informe o fim da faixa.',
            'calendario_hora_fim.date_format' => 'Informe um fim válido, como 18:00.',
            'calendario_hora_fim.after' => 'O fim da faixa deve ser depois do início. Para mostrar o dia inteiro, desmarque a opção de faixa.',
        ]);
        $alterados = ConfiguracaoSistema::query()->whereKey(1)
            ->where('versao', $dados['versao'])->update([
                'calendario_visualizacao' => $dados['calendario_visualizacao'],
                'calendario_faixa_ativa' => $request->boolean('calendario_faixa_ativa'),
                'calendario_hora_inicio' => $dados['calendario_hora_inicio'],
                'calendario_hora_fim' => $dados['calendario_hora_fim'],
                'versao' => DB::raw('versao + 1'),
            ]);
        if ($alterados !== 1) {
            return redirect()->route('configuracoes.index')->withErrors([
                'calendario_visualizacao' => 'A configuração mudou em outra aba. Confira os dados atuais e salve novamente.',
            ]);
        }
        return redirect()->route('configuracoes.index')
            ->with('sucesso', 'Preferências do calendário atualizadas. Abra a agenda para visualizar.');
    }

    public function atualizarInformacoes(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        foreach (ConfiguracaoSistema::CAMPOS_INFORMACOES_ATENDIMENTO as $campo) {
            if (is_string($request->input($campo))) {
                $request->merge([$campo => trim($request->input($campo))]);
            }
        }

        $dados = $request->validate([
            'atendimento_resumo' => ['nullable', 'string', 'max:2000'],
            'atendimento_local' => ['nullable', 'string', 'max:255'],
            'atendimento_orientacoes' => ['nullable', 'string', 'max:2000'],
            'atendimento_email' => ['nullable', 'string', 'email:rfc', 'max:100'],
            'atendimento_telefone' => ['nullable', 'string', 'max:50'],
            'atendimento_outros_contatos' => ['nullable', 'string', 'max:1000'],
            'versao' => ['required', 'integer', 'min:1'],
        ], [
            'atendimento_email.email' => 'Informe um e-mail de contato válido ou deixe o campo vazio.',
        ], [
            'atendimento_resumo' => 'resumo do atendimento',
            'atendimento_local' => 'local de atendimento',
            'atendimento_orientacoes' => 'orientações de chegada',
            'atendimento_email' => 'e-mail de contato',
            'atendimento_telefone' => 'telefone ou ramal',
            'atendimento_outros_contatos' => 'outros contatos',
        ]);

        $atualizacao = [];
        foreach (ConfiguracaoSistema::CAMPOS_INFORMACOES_ATENDIMENTO as $campo) {
            $valor = $dados[$campo] ?? null;
            $atualizacao[$campo] = $valor !== '' ? $valor : null;
        }
        $atualizacao['versao'] = DB::raw('versao + 1');

        $alterados = DB::transaction(function () use ($request, $dados, $atualizacao) {
            abort_unless(DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first(), 503);
            $usuario = \App\Models\Usuario::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless(\App\Services\SegurancaPsicologa::sessaoAtual($request, $usuario), 403, 'Faça login novamente.');
            $configuracao = ConfiguracaoSistema::whereKey(1)->lockForUpdate()->firstOrFail();
            if ((int) $configuracao->versao !== (int) $dados['versao']) return 0;
            $anterior = mb_strtolower(trim((string) $configuracao->atendimento_email), 'UTF-8');
            $novo = mb_strtolower(trim((string) ($atualizacao['atendimento_email'] ?? '')), 'UTF-8');
            if ($anterior !== $novo) {
                $confirmacao = $request->validate(['senha_prontuario_contato' => 'required|string|max:512'], [
                    'senha_prontuario_contato.required' => 'Confirme a senha do prontuário para alterar o e-mail de recuperação.',
                ]);
                if (!\App\Services\SegurancaPsicologa::conferir($confirmacao['senha_prontuario_contato'], $usuario->senha_prontuario)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'senha_prontuario_contato' => 'A senha do prontuário está incorreta. O e-mail não foi alterado.',
                    ]);
                }
                DB::table('recuperacoes_senha_psicologa')->whereNull('usado_em')->update(['usado_em' => now()]);
            }
            return ConfiguracaoSistema::whereKey(1)->where('versao', $dados['versao'])->update($atualizacao);
        });

        if ($alterados !== 1) {
            return redirect()->route('configuracoes.index')->withErrors([
                'atendimento_resumo' => 'A configuração mudou em outra aba. Confira os dados atuais e salve novamente.',
            ]);
        }

        return redirect()->route('configuracoes.index')
            ->with('sucesso', 'Informações do atendimento atualizadas.');
    }

    public function atualizarAntecedencia(Request $request)
    {
        abort_unless($request->user()?->tipo === 'psicologa', 403);

        $dados = $request->validate([
            'antecedencia_agendamento_ativa' => ['required', 'boolean'],
            'antecedencia_agendamento_minutos' => ['required', 'integer', 'between:1,10080'],
            'versao' => ['required', 'integer', 'min:1'],
        ], [
            'antecedencia_agendamento_ativa.required' => 'Informe se a antecedência deve ser exigida.',
            'antecedencia_agendamento_ativa.boolean' => 'Escolha uma opção válida para ativar a antecedência.',
            'antecedencia_agendamento_minutos.required' => 'Informe o tempo de antecedência.',
            'antecedencia_agendamento_minutos.integer' => 'Informe a antecedência em minutos inteiros.',
            'antecedencia_agendamento_minutos.between' => 'Informe de 1 a 10.080 minutos (até 7 dias).',
        ]);

        $alterados = ConfiguracaoSistema::query()->whereKey(1)
            ->where('versao', $dados['versao'])
            ->update([
                'antecedencia_agendamento_ativa' => $request->boolean('antecedencia_agendamento_ativa'),
                'antecedencia_agendamento_minutos' => $dados['antecedencia_agendamento_minutos'],
                'versao' => DB::raw('versao + 1'),
            ]);

        if ($alterados !== 1) {
            return redirect()->route('configuracoes.index')->withErrors([
                'antecedencia_agendamento_minutos' => 'A configuração mudou em outra aba. Confira os dados atuais e salve novamente.',
            ]);
        }

        return redirect()->route('configuracoes.index')->with('sucesso',
            $request->boolean('antecedencia_agendamento_ativa')
                ? 'Antecedência mínima de ' . ConfiguracaoSistema::descreverAntecedencia((int) $dados['antecedencia_agendamento_minutos']) . ' ativada.'
                : 'Antecedência mínima desativada. O tempo escolhido ficou salvo.');
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
