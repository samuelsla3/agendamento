<?php

namespace App\Services;

use App\Mail\ContatoSistemaMail;
use App\Models\ConfiguracaoSistema;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EmailsContato
{
    public function __construct(private ComunicacaoPrivada $comunicacao, private AnexosComunicacao $anexos) {}

    private function chave(Usuario $usuario, string $chave): string
    {
        return hash_hmac('sha256', 'email-contato|'.$usuario->id.'|'.strtolower($chave), (string) config('app.key'));
    }

    private function instalado(): void
    {
        abort_unless(Schema::hasTable('emails_contato'), 503, 'O envio de e-mail ainda não está disponível. Execute as migrations.');
    }

    private function contatos(Usuario $usuario, object $conversa): array
    {
        $aluno = Usuario::findOrFail($conversa->aluno_id);
        abort_unless(in_array($aluno->tipo, ['aluno', 'estudante'], true) && !$aluno->cadastro_provisorio, 403);
        $setor = trim((string) ConfiguracaoSistema::whereKey(1)->value('atendimento_email'));
        $psicologa = $usuario->tipo === 'psicologa';
        if (!$aluno->emailConfirmado()) {
            throw ValidationException::withMessages(['email' => $psicologa
                ? 'O aluno precisa confirmar o e-mail de contato no sistema antes do envio.'
                : 'Confirme seu e-mail de contato no sistema antes de enviar.']);
        }
        $destinatario = $psicologa ? trim((string) $aluno->email) : $setor;
        $responder = $psicologa ? ($setor ?: trim((string) config('mail.from.address'))) : trim((string) $aluno->email);
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL) || !filter_var($responder, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'O contato de e-mail do setor de psicologia precisa ser configurado.']);
        }
        return ['destinatario' => $destinatario, 'responder' => $responder,
            'nome' => $psicologa ? $usuario->nome.' — Setor de Psicologia' : $aluno->nome];
    }

    public function enviar(Request $request, int $conversaId, array $dados): array
    {
        $acesso = $this->comunicacao->autorizarConversaEmail($request, $conversaId);
        $this->instalado();
        $usuario = $acesso['usuario'];
        $chave = $this->chave($usuario, $dados['chave_envio']);
        $arquivos = $this->anexos->preparar($request->file('arquivos', []));
        $total = array_sum(array_column($arquivos, 'tamanho'));
        if ($total > (int) config('comunicacao.email_anexos_maximo_bytes', 15728640)) {
            throw ValidationException::withMessages(['arquivos' => 'Os anexos juntos podem ter até 15 MB por e-mail.']);
        }
        $assunto = trim($dados['assunto']);
        $texto = trim((string) ($dados['texto'] ?? ''));
        if ($assunto === '' || ($texto === '' && !$arquivos)) {
            throw ValidationException::withMessages(['texto' => 'Informe o assunto e escreva uma mensagem ou selecione um anexo.']);
        }
        $resumo = hash_hmac('sha256', json_encode([$conversaId, $assunto, $texto,
            array_map(fn ($a) => [$a['nome'], $a['mime'], $a['tamanho'], $a['hash']], $arquivos)], JSON_THROW_ON_ERROR), (string) config('app.key'));

        // O recibo é confirmado antes do SMTP. Repetir a mesma chave nunca dispara outro e-mail.
        $reserva = DB::transaction(function () use ($request, $usuario, $conversaId, $chave, $resumo, $total) {
            $atual = $this->comunicacao->usuario($request, true, true);
            $anterior = DB::table('emails_contato')->where('chave_envio', $chave)->first();
            if ($anterior) {
                abort_unless((int) $anterior->conversa_id === $conversaId && (int) $anterior->remetente_id === (int) $atual->id
                    && hash_equals($anterior->resumo_hash, $resumo), 409, 'Esta tentativa já foi usada com outro conteúdo. Confira o envio anterior.');
                return ['id' => $anterior->id, 'novo' => false];
            }
            $contatos = $this->contatos($atual, $this->comunicacao->autorizarConversaEmail($request, $conversaId)['conversa']);
            $mailer = (string) config('mail.default');
            $transporte = config('mail.mailers.'.$mailer.'.transport', $mailer);
            if (in_array($transporte, ['log', 'array'], true) && !app()->environment('testing')) {
                throw ValidationException::withMessages(['email' => 'O envio real de e-mails precisa estar configurado no servidor.']);
            }
            $limite = (int) config($atual->tipo === 'psicologa' ? 'comunicacao.emails_por_hora_psicologa' : 'comunicacao.emails_por_hora_aluno');
            abort_if(DB::table('emails_contato')->where('remetente_id', $atual->id)->where('created_at', '>=', now()->subHour())->count() >= $limite,
                429, 'Limite de e-mails neste intervalo. Aguarde antes de enviar novamente.');
            $diario = DB::table('emails_contato')->where('remetente_id', $atual->id)
                ->where('created_at', '>=', now('America/Sao_Paulo')->startOfDay()->timezone(config('app.timezone')))->sum('anexos_bytes');
            if ($diario + $total > (int) config('comunicacao.anexos_diarios_bytes')) {
                throw ValidationException::withMessages(['arquivos' => 'Limite diário de anexos por e-mail atingido.']);
            }
            if ($atual->tipo === 'psicologa') AcessoProntuario::exigir($request, $atual);
            $id = DB::table('emails_contato')->insertGetId(['conversa_id' => $conversaId, 'remetente_id' => $usuario->id,
                'chave_envio' => $chave, 'resumo_hash' => $resumo, 'estado' => 'em_envio', 'anexos_bytes' => $total,
                'created_at' => now(), 'updated_at' => now()]);
            return ['id' => $id, 'novo' => true, 'contatos' => $contatos];
        }, 3);

        if ($reserva['novo']) {
            try {
                $contatos = $reserva['contatos'];
                $enviado = Mail::to($contatos['destinatario'])->send(new ContatoSistemaMail(
                    $assunto, $texto, $contatos['nome'], $contatos['responder'], $arquivos));
                $estado = $enviado ? 'enviado' : 'falhou';
                DB::table('emails_contato')->where('id', $reserva['id'])->update(['estado' => $estado,
                    'enviado_em' => $enviado ? now() : null, 'updated_at' => now()]);
            } catch (\Throwable $e) {
                // Um erro de conexão pode acontecer após a aceitação pelo SMTP. Nunca repete automaticamente.
                DB::table('emails_contato')->where('id', $reserva['id'])->update(['estado' => 'revisar', 'updated_at' => now()]);
                Log::warning('Falha na tentativa de e-mail de contato.', ['envio_id' => $reserva['id'], 'erro_tipo' => get_class($e)]);
            }
        }

        return $this->apresentar(DB::table('emails_contato')->where('id', $reserva['id'])->first());
    }

    public function status(Request $request, int $conversaId, string $chave): array
    {
        $acesso = $this->comunicacao->autorizarConversaEmail($request, $conversaId);
        $this->instalado();
        $envio = DB::table('emails_contato')->where('chave_envio', $this->chave($acesso['usuario'], $chave))
            ->where('conversa_id', $conversaId)->where('remetente_id', $acesso['usuario']->id)->first();
        return $envio ? $this->apresentar($envio) : ['estado' => 'nao_localizado', 'nova_chave' => (string) \Illuminate\Support\Str::uuid(),
            'message' => 'A tentativa ainda não foi localizada. Aguarde e consulte novamente; não é possível confirmar o envio.'];
    }

    private function apresentar(object $envio): array
    {
        $estado = $envio->estado;
        if ($estado === 'em_envio' && \Carbon\Carbon::parse($envio->updated_at)->lt(now()->subMinutes(5))) $estado = 'revisar';
        return ['estado' => $estado, 'nova_chave' => (string) \Illuminate\Support\Str::uuid(), 'message' => match ($estado) {
            'enviado' => 'E-mail enviado com os anexos selecionados. Confira também a pasta de spam do destinatário.',
            'falhou' => 'O envio foi interrompido antes da confirmação. Você pode iniciar uma nova tentativa.',
            'revisar' => 'Não foi possível confirmar o envio. Confira se o destinatário recebeu antes de iniciar outra tentativa.',
            default => 'O e-mail ainda está em processamento. Consulte o resultado novamente em instantes.',
        }];
    }
}
