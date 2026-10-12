<?php

namespace App\Services;

use App\Jobs\EnviarAvisoEmail;
use App\Models\Usuario;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AvisosEmail
{
    public const PROBLEMAS = ['falhou', 'revisar', 'sem_contato'];

    public static function registrar(string $evento, string $email, string $assunto, string $html,
        ?array $condicao = null, array $contexto = []): ?int
    {
        $email = trim($email);
        return DB::transaction(function () use ($evento, $email, $assunto, $html, $condicao, $contexto) {
            $chave = hash('sha256', $evento);
            $tipo = str_starts_with($evento, 'verificar_email:') ? 'verificacao_email'
                : (str_starts_with($evento, 'lembrete:') ? 'lembrete'
                    : (str_starts_with($evento, 'cancelado:') ? 'cancelamento' : 'confirmacao'));
            $aluno = !empty($contexto['matricula']) ? Usuario::where('matricula', $contexto['matricula'])->first() : null;
            $confirmado = $tipo === 'verificacao_email' || ($aluno && $aluno->emailConfirmado());
            if ($tipo !== 'verificacao_email' && $confirmado) {
                $email = (string) $aluno->email;
            }
            $valido = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $motivo = !$valido ? 'sem_contato' : (!$confirmado
                ? ($aluno ? 'email_nao_confirmado' : 'vinculo_contato_ausente') : null);
            $identificacao = array_intersect_key($contexto, array_flip(['nome', 'matricula', 'data', 'hora']));
            $identificacao['email'] = $email;
            $inseriu = DB::table('avisos_email')->insertOrIgnore([
                'evento' => $chave,
                'tipo' => $tipo,
                'conteudo' => Crypt::encryptString(json_encode(compact('email', 'assunto', 'html', 'condicao'), JSON_THROW_ON_ERROR)),
                'contexto' => Crypt::encryptString(json_encode($identificacao, JSON_THROW_ON_ERROR)),
                'estado' => $motivo === null ? 'pendente' : 'sem_contato',
                'motivo_codigo' => $motivo,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $id = (int) DB::table('avisos_email')->where('evento', $chave)->value('id');
            if ($inseriu === 1 && $motivo === null) {
                self::enviarAposCommit($id);
            }
            return $id ?: null;
        });
    }

    // Usado dentro da transação da rota de reenvio, após autenticação real da psicóloga.
    public static function tentarNovamente(int $id, string $versao, bool $confirmouIncerto, int $psicologaId): void
    {
        $aviso = DB::table('avisos_email')->where('id', $id)->lockForUpdate()->first();
        abort_unless($aviso, 404);
        abort_unless(hash_equals(self::versao($aviso), $versao), 409,
            'Este aviso mudou. Atualize a lista antes de tentar novamente.');
        abort_unless(in_array($aviso->estado, ['falhou', 'revisar', 'sem_contato', 'pendente'], true), 409,
            'Este aviso já foi enviado, descartado ou está em envio. Não será reenviado.');
        abort_if($aviso->estado === 'revisar' && !$confirmouIncerto, 422,
            'O envio ficou incerto. Confira se o aluno recebeu o e-mail e confirme a orientação antes de tentar novamente.');
        abort_if(trim((string) $aviso->conteudo) === '', 409, 'O conteúdo deste aviso não está mais disponível.');
        try {
            $dados = self::dados($aviso);
            $contexto = self::contexto($aviso);
        } catch (Throwable $e) {
            abort(409, 'Não foi possível recuperar este aviso. Procure o responsável pelo sistema.');
        }
        // Lembretes e confirmações antigos sem vínculo verificável não são reenviados.
        $tipo = self::tipo($aviso, $dados);
        abort_if(in_array($tipo, ['confirmacao', 'lembrete', 'legado'], true) && empty($dados['condicao']), 409,
            'Este aviso antigo não possui vínculo suficiente para conferir a reserva. Não será reenviado.');
        if ($tipo !== 'verificacao_email' && !empty($contexto['matricula'])) {
            // Não aceita destinatário arbitrário informado pelo navegador.
            $aluno = Usuario::where('matricula', $contexto['matricula'])->first();
            abort_unless($aluno && $aluno->emailConfirmado(), 422,
                'O aluno precisa confirmar um e-mail de contato no próprio sistema antes do envio.');
            $dados['email'] = trim((string) $aluno->email);
        }
        abort_unless(filter_var($dados['email'] ?? '', FILTER_VALIDATE_EMAIL) !== false, 422,
            'O aluno continua sem e-mail válido no cadastro. Atualize o cadastro ou avise por outro meio.');
        abort_if($tipo !== 'verificacao_email' && empty($contexto['matricula']) && empty($dados['condicao']), 409,
            'Este aviso antigo não permite conferir o contato do aluno com segurança.');
        $contexto['email'] = $dados['email'];
        DB::table('avisos_email')->where('id', $id)->update([
            'estado' => 'pendente', 'erro_tipo' => null, 'motivo_codigo' => null,
            'tentativa_id' => null, 'iniciado_em' => null, 'solicitado_por' => $psicologaId,
            'conteudo' => Crypt::encryptString(json_encode($dados, JSON_THROW_ON_ERROR)),
            'contexto' => Crypt::encryptString(json_encode($contexto, JSON_THROW_ON_ERROR)),
            'tipo' => $tipo, 'revisao' => (int) $aviso->revisao + 1, 'updated_at' => now(),
        ]);
        self::enviarAposCommit($id);
    }

    public static function dados(object $aviso): array
    {
        $dados = json_decode(Crypt::decryptString($aviso->conteudo), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($dados) || !is_string($dados['email'] ?? null)
            || !is_string($dados['assunto'] ?? null) || !is_string($dados['html'] ?? null)) {
            throw new \UnexpectedValueException('conteudo_aviso_invalido');
        }
        return $dados;
    }

    public static function contexto(object $aviso): array
    {
        if (!empty($aviso->contexto)) {
            $contexto = json_decode(Crypt::decryptString($aviso->contexto), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($contexto)) {
                throw new \UnexpectedValueException('contexto_aviso_invalido');
            }
            return array_intersect_key($contexto, array_flip(['nome', 'matricula', 'email', 'data', 'hora']));
        }
        // O corpo do aviso não é exposto na tela, inclusive em registros antigos.
        return !empty($aviso->conteudo) ? ['email' => self::dados($aviso)['email']] : [];
    }

    public static function tipo(object $aviso, ?array $dados = null): string
    {
        if (($aviso->tipo ?? 'legado') !== 'legado') {
            return $aviso->tipo;
        }
        if ($dados === null && !empty($aviso->conteudo)) {
            $dados = self::dados($aviso);
        }
        if (!empty($dados['condicao'])) {
            return 'lembrete';
        }
        $assunto = mb_strtolower($dados['assunto'] ?? '', 'UTF-8');
        if (str_contains($assunto, 'cancelad') || str_contains($assunto, 'cancelamento')) {
            return 'cancelamento';
        }
        if (str_contains($assunto, 'confirmação de agendamento')) {
            return 'confirmacao';
        }
        return 'legado';
    }

    public static function versao(object $aviso): string
    {
        return hash('sha256', implode('|', [$aviso->id, $aviso->evento, $aviso->estado,
            (int) $aviso->revisao, $aviso->tentativa_id ?? '', $aviso->updated_at ?? '']));
    }

    public static function problemas($query): void
    {
        $query->whereIn('estado', self::PROBLEMAS)->orWhere(function ($envio) {
            $envio->where('estado', 'em_envio')->where('updated_at', '<', now()->subMinutes(15));
        })->orWhere(function ($pendente) {
            $pendente->where('estado', 'pendente')->where('updated_at', '<', now()->subMinutes(2));
        });
    }

    public static function contarProblemas(): int
    {
        return DB::table('avisos_email')->where(fn ($q) => self::problemas($q))->count();
    }

    private static function enviarAposCommit(int $id): void
    {
        // Mantém o envio direto atual. A confirmação do agendamento/cancelamento
        // não depende do resultado do SMTP, nem há repetição automática de avisos incertos.
        DB::afterCommit(function () use ($id) {
            try {
                (new EnviarAvisoEmail($id))->handle();
            } catch (Throwable $e) {
                Log::warning('Não foi possível concluir a tentativa de envio do aviso.', [
                    'aviso_id' => $id, 'erro_tipo' => get_class($e),
                ]);
            }
        });
    }
}
