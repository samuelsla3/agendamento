<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Services\SegurancaPsicologa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RedefinirSenhaPsicologa extends Command
{
    protected $signature = 'psicologia:redefinir-senha {--tipo= : local ou prontuario}';
    protected $description = 'Recuperação administrativa de uma senha, sem expor credenciais e sem apagar prontuários.';

    public function handle(): int
    {
        if (!$this->input->isInteractive()) {
            $this->error('Execute em terminal interativo. Senhas não são aceitas como argumentos.');
            return self::FAILURE;
        }
        $tipo = $this->option('tipo') ?: $this->choice('Qual senha será redefinida?', ['local', 'prontuario']);
        if (!in_array($tipo, ['local', 'prontuario'], true)) {
            $this->error('Use --tipo=local ou --tipo=prontuario.'); return self::FAILURE;
        }
        $matricula = trim((string) $this->secret('Matrícula da psicóloga (entrada oculta)'));
        $usuario = Usuario::where('matricula', $matricula)->first();
        if (!$usuario || !SegurancaPsicologa::autorizada($usuario)) {
            $this->error('Não foi encontrada uma conta autorizada para esta operação.'); return self::FAILURE;
        }
        if (!$this->confirm('Você confirmou a identidade da profissional e autoriza a redefinição? Todas as sessões dela serão revogadas.', false)) return self::FAILURE;
        $senha = $this->secret('Nova senha (12 a 64 caracteres, letras e números)');
        $confirmacao = $this->secret('Confirme a nova senha');
        $validacao = Validator::make(['nova_senha' => $senha, 'nova_senha_confirmation' => $confirmacao],
            ['nova_senha' => SegurancaPsicologa::regrasNovaSenha()], SegurancaPsicologa::mensagensSenha());
        if ($validacao->fails()) {
            $this->error($validacao->errors()->first()); return self::FAILURE;
        }
        $gravou = DB::transaction(function () use ($usuario, $tipo, $senha) {
            abort_unless(DB::table('travas_operacoes')->where('nome', 'agenda')->lockForUpdate()->first(), 503,
                'Execute as migrations antes de continuar.');
            $atual = Usuario::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            if (!SegurancaPsicologa::autorizada($atual)) return false;
            $campo = $tipo === 'prontuario' ? 'senha_prontuario' : 'senha';
            $outro = $tipo === 'prontuario' ? 'senha' : 'senha_prontuario';
            if (SegurancaPsicologa::conferir($senha, $atual->{$outro})) return false;
            $atual->{$campo} = Hash::make($senha);
            if ($tipo === 'local') {
                $atual->password = null; $atual->psicologa_senha_local_definida = true;
            }
            $atual->psicologa_autenticacao_versao = (int) $atual->psicologa_autenticacao_versao + 1;
            $atual->psicologa_prontuario_versao = (int) $atual->psicologa_prontuario_versao + 1;
            $atual->save(); return true;
        });
        $senha = $confirmacao = null;
        if (!$gravou) {
            $this->error('Não foi possível redefinir. Confira a autorização e escolha uma senha diferente da outra senha da conta.');
            return self::FAILURE;
        }
        $this->info('Senha redefinida. Sessões e desbloqueios anteriores foram revogados. Os registros clínicos foram preservados.');
        return self::SUCCESS;
    }
}
