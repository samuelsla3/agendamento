<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\RegistroAtendimento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class EncerramentoAtendimento
{
    // Chamado pela rota de agenda, dentro da transação de RequisicaoUnica,
    // com o horário bloqueado e sua versão conferida pelo controller.
    public function encerrar(Horario $horario, string $situacao, ?string $observacao = null): void
    {
        abort_unless(in_array($situacao, ['Realizado', 'Não compareceu'], true), 422);
        abort_if((int) $horario->disponivel !== 0 || (int) $horario->confirmado === 1
            || trim((string) $horario->matricula) === '', 409, 'Atendimento já encerrado ou vaga sem reserva.');

        if ($situacao === 'Não compareceu') {
            $this->conferirPrazoFalta($horario);
        }

        $registro = RegistroAtendimento::create([
            'id_horario_original' => $horario->id,
            'nome' => $horario->nome, 'matricula' => $horario->matricula,
            'status' => $situacao,
            'observacao' => trim((string) $observacao) ?: null,
            'data_atendimento' => $horario->data, 'hora_atendimento' => $horario->hora,
            // Momento da ação, separado da data/hora agendadas.
            'data_registro' => now(),
        ]);
        $horario->update([
            'confirmado' => 1, 'disponivel' => 0, 'token_cancelamento' => null,
            'situacao_final' => $situacao, 'registro_encerramento_id' => $registro->id,
            'revisao_encerramento' => (int) $horario->revisao_encerramento + 1,
        ]);
    }

    public function corrigir(Horario $horario, string $novaSituacao, int $psicologaId): void
    {
        abort_unless(in_array($novaSituacao, ['Realizado', 'Não compareceu'], true), 422);
        abort_if((int) $horario->disponivel !== 0 || (int) $horario->confirmado !== 1, 409,
            'Somente atendimentos encerrados podem ter sua situação corrigida.');
        $anterior = $horario->situacao_atendimento;
        abort_if($anterior === $novaSituacao, 422, 'Escolha uma situação diferente da atual.');
        if ($novaSituacao === 'Não compareceu') {
            $this->conferirPrazoFalta($horario);
        }

        $query = RegistroAtendimento::where('id_horario_original', $horario->id)
            ->where('matricula', $horario->matricula)->where('status', $anterior);
        if ($horario->registro_encerramento_id) {
            $query->whereKey($horario->registro_encerramento_id);
        } else {
            // Legados: só corrige quando é possível identificar um registro com segurança.
            $query->whereDate('data_atendimento', $horario->data)
                ->whereTime('hora_atendimento', $horario->hora);
        }
        $registros = $query->lockForUpdate()->get();
        abort_unless($registros->count() === 1, 409,
            'Não foi possível identificar um único registro histórico deste atendimento. Procure o responsável pelo sistema.');
        $registro = $registros->first();

        DB::table('correcoes_atendimentos')->insert([
            'horario_id' => $horario->id, 'registro_atendimento_id' => $registro->id,
            'psicologa_id' => $psicologaId, 'situacao_anterior' => $anterior,
            'situacao_nova' => $novaSituacao, 'corrigido_em' => now(),
            // Preserva a observação substituída sem deixá-la legível na trilha de auditoria.
            'observacao_anterior' => $registro->observacao !== null
                ? Crypt::encryptString($registro->observacao) : null,
        ]);
        // Mantém a identidade e a data do registro: uma sessão continua sendo uma linha.
        $registro->update(['status' => $novaSituacao,
            'observacao' => $novaSituacao === 'Realizado' ? 'Atendimento concluído com sucesso.' : null]);
        $horario->update([
            'situacao_final' => $novaSituacao, 'registro_encerramento_id' => $registro->id,
            'revisao_encerramento' => (int) $horario->revisao_encerramento + 1,
            'token_cancelamento' => null,
        ]);
    }

    private function conferirPrazoFalta(Horario $horario): void
    {
        abort_unless(Carbon::parse($horario->data . ' ' . $horario->hora, config('app.timezone'))->lt(now()),
            422, 'A falta só pode ser registrada depois do horário de início do atendimento.');
    }
}
