<?php

namespace App\Services;

use App\Mail\RecuperacaoSenhaPsicologaMail;
use App\Models\{ConfiguracaoSistema, Usuario};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Hash, Log, Mail};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecuperacaoSenhaPsicologa
{
    public const VALIDADE_MINUTOS = 15;
    public const MENSAGEM = 'Se a matrícula corresponder a uma psicóloga autorizada, enviaremos as instruções ao e-mail de contato cadastrado nas informações do atendimento. Confira a caixa de entrada e o spam. Aguarde um minuto antes de solicitar novamente.';

    public static function emailContato(): string
    {
        return mb_strtolower(trim((string) ConfiguracaoSistema::whereKey(1)->value('atendimento_email')), 'UTF-8');
    }
    public function solicitar(string $matricula, string $tipo): void
    {
        $emitido = DB::transaction(function () use ($matricula, $tipo) {
            $this->travar();
            $usuario = Usuario::where('matricula', $matricula)->lockForUpdate()->first();
            $email = self::emailContato();
            if (!$usuario || !SegurancaPsicologa::autorizada($usuario) || !SegurancaPsicologa::configurada($usuario)
                || filter_var($email, FILTER_VALIDATE_EMAIL) === false) return null;
            $recentes = DB::table('recuperacoes_senha_psicologa')->where('usuario_id', $usuario->id)->where('tipo', $tipo);
            if ((clone $recentes)->where('created_at', '>', now()->subMinute())->exists()) return null;
            // Somente o pedido mais recente da mesma senha pode ser usado.
            (clone $recentes)->whereNull('usado_em')->update(['usado_em' => now()]);
            $id = (string) Str::uuid(); $token = bin2hex(random_bytes(32));
            DB::table('recuperacoes_senha_psicologa')->insert([
                'id' => $id, 'usuario_id' => $usuario->id, 'tipo' => $tipo,
                'token_hash' => hash('sha256', $token), 'email_hash' => hash('sha256', $email),
                'versao_acesso' => $usuario->psicologa_autenticacao_versao,
                'versao_prontuario' => $usuario->psicologa_prontuario_versao,
                'expira_em' => now()->addMinutes(self::VALIDADE_MINUTOS), 'created_at' => now(),
            ]);
            return compact('id', 'token', 'email', 'tipo');
        });
        if (!$emitido) return;
        try {
            // APP_URL é a origem confiável, incluindo a subpasta, sem utilizar o Host recebido.
            $base = rtrim((string) config('app.url'), '/');
            $partes = parse_url($base);
            if (!is_array($partes) || empty($partes['host']) || !in_array($partes['scheme'] ?? '', ['http', 'https'], true)
                || (app()->environment('production') && $partes['scheme'] !== 'https')) {
                throw new \RuntimeException('app_url_recuperacao_invalida');
            }
            $url = $base.route('seguranca.recuperacao.redefinir', ['id' => $emitido['id'], 'token' => $emitido['token']], false);
            Mail::to($emitido['email'])->send(new RecuperacaoSenhaPsicologaMail($url, $tipo));
        } catch (Throwable $erro) {
            DB::table('recuperacoes_senha_psicologa')->where('id', $emitido['id'])->update(['usado_em' => now()]);
            // Não registra link, token, endereço ou mensagens da exceção SMTP.
            Log::warning('Falha no envio da recuperação de senha da psicóloga.', [
                'recuperacao_id' => $emitido['id'], 'erro_tipo' => get_class($erro),
            ]);
        }
    }
    public function conferir(string $id, ?string $prova): ?object
    {
        $registro = DB::table('recuperacoes_senha_psicologa')->where('id', $id)->first();
        return $this->valido($registro, $prova) ? $registro : null;
    }
    private function valido(?object $registro, ?string $prova, ?Usuario $usuario = null): bool
    {
        if (!$registro || !is_string($prova) || strlen($prova) !== 64 || $registro->usado_em !== null
            || Carbon::parse($registro->expira_em)->lte(now()) || !hash_equals($registro->token_hash, $prova)) return false;
        $usuario ??= Usuario::find($registro->usuario_id);
        return $usuario && SegurancaPsicologa::autorizada($usuario) && SegurancaPsicologa::configurada($usuario)
            && (int) $registro->versao_acesso === (int) $usuario->psicologa_autenticacao_versao
            && (int) $registro->versao_prontuario === (int) $usuario->psicologa_prontuario_versao
            && hash_equals($registro->email_hash, hash('sha256', self::emailContato()));
    }
    public function redefinir(string $id, ?string $prova, string $senha): Usuario
    {
        return DB::transaction(function () use ($id, $prova, $senha) {
            $this->travar();
            $registro = DB::table('recuperacoes_senha_psicologa')->where('id', $id)->lockForUpdate()->first();
            $usuario = $registro ? Usuario::whereKey($registro->usuario_id)->lockForUpdate()->first() : null;
            abort_unless($usuario && $this->valido($registro, $prova, $usuario), 410,
                'Este link expirou ou já foi utilizado. Solicite uma nova recuperação.');
            $prontuario = $registro->tipo === 'prontuario';
            $campo = $prontuario ? 'senha_prontuario' : 'senha';
            if (SegurancaPsicologa::conferir($senha, $usuario->{$campo})
                || SegurancaPsicologa::conferir($senha, $usuario->{$prontuario ? 'senha' : 'senha_prontuario'})) {
                throw ValidationException::withMessages(['nova_senha' => 'Escolha uma senha diferente da atual e da outra senha do sistema.']);
            }
            $usuario->{$campo} = Hash::make($senha);
            $usuario->psicologa_prontuario_versao = (int) $usuario->psicologa_prontuario_versao + 1;
            if (!$prontuario) {
                // A coluna legada pode ser NOT NULL; nunca conserva o hash antigo.
                $usuario->password = Hash::make(Str::random(64));
                $usuario->psicologa_autenticacao_versao = (int) $usuario->psicologa_autenticacao_versao + 1;
            }
            $usuario->save();
            DB::table('recuperacoes_senha_psicologa')->where('usuario_id', $usuario->id)->whereNull('usado_em')
                ->update(['usado_em' => now()]);
            return $usuario;
        });
    }
    private function travar(): void
    {
        abort_unless(DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first(), 503,
            'Execute as migrations antes de usar o sistema.');
    }
}
