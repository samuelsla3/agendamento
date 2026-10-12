<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConfiguracaoSistema extends Model
{
    public const CAMPOS_INFORMACOES_ATENDIMENTO = [
        'resumo' => 'atendimento_resumo',
        'local' => 'atendimento_local',
        'orientacoes' => 'atendimento_orientacoes',
        'email' => 'atendimento_email',
        'telefone' => 'atendimento_telefone',
        'contatos' => 'atendimento_outros_contatos',
    ];

    public static function informacoesAtendimento(): array
    {
        $campos = array_values(self::CAMPOS_INFORMACOES_ATENDIMENTO);
        if (!Schema::hasTable('configuracoes_sistema')
            || !Schema::hasColumns('configuracoes_sistema', $campos)) {
            return [];
        }

        $configuracao = static::query()->whereKey(1)->first(array_merge(['id'], $campos));
        $informacoes = [];
        foreach (self::CAMPOS_INFORMACOES_ATENDIMENTO as $chave => $campo) {
            $texto = trim((string) ($configuracao?->{$campo} ?? ''));
            if ($texto !== '') {
                $informacoes[$chave] = $texto;
            }
        }

        return $informacoes;
    }

    protected $table = 'configuracoes_sistema';
    protected $guarded = ['*'];
    protected $casts = [
        'versao' => 'integer',
        'aviso_ativo' => 'boolean',
        'antecedencia_agendamento_ativa' => 'boolean',
        'antecedencia_agendamento_minutos' => 'integer',
        'calendario_faixa_ativa' => 'boolean',
    ];

    public static function preferenciasCalendario(): array
    {
        $padrao = ['visualizacao' => 'padrao', 'faixa_ativa' => false,
            'inicio' => '08:00', 'fim' => '18:00'];
        $campos = ['calendario_visualizacao', 'calendario_faixa_ativa',
            'calendario_hora_inicio', 'calendario_hora_fim'];
        if (!Schema::hasTable('configuracoes_sistema')
            || !Schema::hasColumns('configuracoes_sistema', $campos)) {
            return $padrao;
        }
        $configuracao = static::query()->whereKey(1)->first(array_merge(['id'], $campos));
        if (!$configuracao) {
            return $padrao;
        }
        $visualizacao = $configuracao->calendario_visualizacao;
        $inicio = (string) $configuracao->calendario_hora_inicio;
        $fim = (string) $configuracao->calendario_hora_fim;
        $faixaValida = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $inicio)
            && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $fim) && $fim > $inicio;
        return [
            'visualizacao' => in_array($visualizacao, ['padrao', 'mes', 'semana', 'lista'], true)
                ? $visualizacao : 'padrao',
            'faixa_ativa' => (bool) $configuracao->calendario_faixa_ativa && $faixaValida,
            'inicio' => $faixaValida ? $inicio : $padrao['inicio'],
            'fim' => $faixaValida ? $fim : $padrao['fim'],
        ];
    }

    public static function antecedenciaAgendamento(bool $bloquear = false): array
    {
        $padrao = ['ativa' => false, 'minutos' => 120];
        if (!Schema::hasTable('configuracoes_sistema')
            || !Schema::hasColumns('configuracoes_sistema', [
                'antecedencia_agendamento_ativa', 'antecedencia_agendamento_minutos',
            ])) {
            return $padrao;
        }

        $consulta = static::query()->whereKey(1);
        if ($bloquear) {
            // No agendamento, a transação já foi aberta por RequisicaoUnica.
            $consulta->lockForUpdate();
        }

        $configuracao = $consulta->first([
            'id', 'antecedencia_agendamento_ativa', 'antecedencia_agendamento_minutos',
        ]);

        if (!$configuracao) {
            return $padrao;
        }

        return [
            'ativa' => (bool) $configuracao->antecedencia_agendamento_ativa,
            'minutos' => max(1, (int) $configuracao->antecedencia_agendamento_minutos),
        ];
    }

    public static function descreverAntecedencia(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;
        $partes = [];
        if ($horas > 0) {
            $partes[] = $horas . ($horas === 1 ? ' hora' : ' horas');
        }
        if ($resto > 0) {
            $partes[] = $resto . ($resto === 1 ? ' minuto' : ' minutos');
        }

        return implode(' e ', $partes);
    }

    public static function avisoPublico(): ?string
    {
        // As páginas continuam abrindo durante a instalação da migration.
        if (!Schema::hasTable('configuracoes_sistema')
            || !Schema::hasColumns('configuracoes_sistema', ['aviso_ativo', 'aviso_texto'])) {
            return null;
        }

        $configuracao = static::query()->whereKey(1)
            ->first(['id', 'aviso_ativo', 'aviso_texto']);

        if (!$configuracao?->aviso_ativo) {
            return null;
        }

        $texto = trim((string) $configuracao->aviso_texto);

        return $texto !== '' ? $texto : null;
    }

    public static function horarioLembretes(): string
    {
        // Conserva o horário anterior durante a instalação da migration.
        if (!Schema::hasTable('configuracoes_sistema')) {
            return '09:00';
        }

        return static::query()->whereKey(1)->value('horario_lembretes') ?? '09:00';
    }
}
