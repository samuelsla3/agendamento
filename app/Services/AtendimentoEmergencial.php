<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AtendimentoEmergencial
{
    public function registrar(array $dados, ?int $horarioId = null): string
    {
        return DB::transaction(function () use ($dados, $horarioId) {
            $trava = DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first();
            abort_unless($trava, 503, 'Execute as migrations antes de usar o sistema.');

            if ($horarioId !== null) {
                $vaga = Horario::whereKey($horarioId)->lockForUpdate()->firstOrFail();
                abort_unless((int) $vaga->disponivel === 1 && (int) $vaga->confirmado === 0
                    && !$vaga->temIndisponibilidade() && trim((string) $vaga->nome) === '' && trim((string) $vaga->matricula) === '',
                    409, 'Este horário não está mais disponível. Atualize a agenda.');
                if (Carbon::parse($vaga->data.' '.$vaga->hora, config('app.timezone'))->lte(now())) {
                    throw ValidationException::withMessages(['data' => 'Para agendar um aluno, selecione um horário futuro. Para registrar atendimento passado, utilize Atendimento Emergencial.']);
                }
                // A reserva usa exclusivamente a vaga selecionada, nunca outra data enviada pelo navegador.
                $dados['data'] = $vaga->data;
                $dados['hora'] = substr($vaga->hora, 0, 5);
            }

            $matricula = trim($dados['matricula']);
            $hora = $dados['hora'].':00';
            $aluno = Usuario::where('matricula', $matricula)->lockForUpdate()->first();
            if (!empty($dados['aluno_id']) && (!$aluno || (string) $aluno->id !== (string) $dados['aluno_id'])) {
                throw ValidationException::withMessages(['matricula' => 'O aluno selecionado não corresponde à matrícula. Selecione novamente.']);
            }
            if ($aluno && !in_array($aluno->tipo, ['estudante', 'aluno'], true)) {
                throw ValidationException::withMessages(['matricula' => 'Esta matrícula não pertence a um cadastro de aluno.']);
            }

            if (Horario::where('matricula', $matricula)->where('data', $dados['data'])->where('disponivel', 0)->exists()) {
                throw ValidationException::withMessages(['data' => 'Este aluno já possui atendimento nessa data. Abra o atendimento existente na agenda.']);
            }
            $vagas = Horario::where('data', $dados['data'])->where('hora', $hora)->lockForUpdate()->get();
            $horario = $vagas->first();
            abort_if($horarioId !== null && (!$horario || (int) $horario->id !== $horarioId), 409,
                'A vaga selecionada mudou. Atualize a agenda antes de continuar.');
            if ($vagas->count() > 1 || ($horario && ((int) $horario->disponivel !== 1
                || $horario->temIndisponibilidade() || (int) $horario->confirmado === 1 || trim((string) $horario->nome) !== ''
                || trim((string) $horario->matricula) !== ''))) {
                throw ValidationException::withMessages(['hora' => 'Já existe um atendimento ou conflito nesse horário. Escolha outra data/hora ou confira a agenda.']);
            }

            $novo = !$aluno;
            if ($novo) {
                $nome = trim($dados['nome'] ?? '');
                $turma = trim($dados['turma_codigo'] ?? '');
                $turma = $turma === '' ? null : Usuario::formatarTurma(mb_strtoupper($turma, 'UTF-8'));
                $email = trim($dados['email'] ?? '') ?: null;
                Validator::make(compact('nome', 'turma', 'email'), [
                    'nome' => 'required|string|max:100',
                    'turma' => ['nullable', 'string', 'max:50', 'regex:/^\d+\.\d+\.[A-Z0-9]+$/'],
                    'email' => 'nullable|email|max:100|unique:usuarios,email',
                ], [
                    'nome.required' => 'Informe o nome completo do aluno novo.',
                    'turma.regex' => 'Informe a turma completa, como 3.18.1I, ou deixe em branco.',
                    'email.unique' => 'Este e-mail já está vinculado a outro cadastro. Confira o contato ou deixe o campo vazio.',
                ])->validate();
                $aluno = new Usuario([
                    'nome' => $nome, 'matricula' => $matricula, 'turma_codigo' => $turma,
                    'email' => $email, 'tipo' => 'estudante', 'cadastro_provisorio' => true,
                    // Senha aleatória nunca exibida; o primeiro acesso deve ser validado pelo SUAP.
                    'senha' => Hash::make(Str::random(64)),
                ]);
                // Compatibilidade com a coluna legada, exigida pelas migrations antigas.
                $aluno->password = $aluno->senha;
                $aluno->save();
            }

            // Para aluno existente, usa sempre o cadastro do banco, sem sobrescrevê-lo pelo formulário.
            $token = Str::random(64);
            $reserva = [
                'data' => $dados['data'], 'hora' => $hora, 'disponivel' => 0, 'confirmado' => 0,
                'nome' => $aluno->nome, 'matricula' => $aluno->matricula,
                'justificativa_cancelamento' => null, 'token_cancelamento' => $token,
            ];
            if ($horario) {
                $horario->update($reserva);
            } else {
                $horario = Horario::create($reserva);
            }

            $email = trim((string) $aluno->email);
            AvisosEmail::registrar('agendado:'.$token, $email,
                    'Setor de Psicologia IFBA: Confirmação de Agendamento',
                    'Olá, <strong>'.e($aluno->nome).'</strong>!<br><br>Um atendimento foi registrado pela psicóloga.<br>'
                    .'<strong>Data:</strong> '.Carbon::parse($dados['data'])->format('d/m/Y')
                    .'<br><strong>Hora:</strong> '.Carbon::parse($hora)->format('H:i')
                    .'<br><br>Serviço de Psicologia - IFBA Seabra',
                    ['horario_id' => $horario->id, 'reserva_hash' => hash('sha256', $token), 'data' => $horario->data],
                    ['nome' => $aluno->nome, 'matricula' => $aluno->matricula, 'data' => $horario->data, 'hora' => $horario->hora]);

            $mensagem = 'Atendimento registrado como Agendado. O prontuário já está disponível pelo botão Operar ou pelo calendário.';
            if ($novo) {
                $mensagem .= ' Cadastro provisório criado; o primeiro login pelo SUAP usará a mesma matrícula.';
            }
            $mensagem .= ' Aviso registrado para acompanhamento em E-mails; o contato precisa estar confirmado pelo aluno para o envio.';
            return $mensagem;
        });
    }
}
