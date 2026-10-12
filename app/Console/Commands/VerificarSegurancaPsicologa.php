<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Services\AcessoProntuario;
use App\Services\SegurancaPsicologa;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VerificarSegurancaPsicologa extends Command
{
    protected $signature = 'psicologia:verificar-seguranca';
    protected $description = 'Confere a instalação e executa verificações isoladas de autorização/senhas, sem alterar usuários ou prontuários.';

    public function handle(): int
    {
        $campos = ['psicologa_conta_teste', 'psicologa_senha_local_definida', 'senha_prontuario',
            'psicologa_configurada_em', 'psicologa_autenticacao_versao', 'psicologa_prontuario_versao'];
        if (!Schema::hasTable('usuarios') || !Schema::hasColumns('usuarios', $campos)) {
            $this->error('Migration de segurança ausente. Execute a migration 2026_10_08_070000 antes de continuar.');
            return self::FAILURE;
        }
        $ambiente = app()->environment();
        $configuracao = config('psicologia');
        $checks = 0;
        $conferir = function (bool $condicao) use (&$checks): void {
            if (!$condicao) throw new \RuntimeException('verificacao_nao_passou');
            $checks++;
        };
        try {
            config(['psicologia.matriculas_suap' => ['AUTORIZACAO_FICTICIA'],
                'psicologia.permitir_contas_teste' => true, 'psicologia.prontuario_inatividade_minutos' => 30]);
            app()->instance('env', 'local');
            $usuario = new Usuario();
            $usuario->forceFill(['id' => 999999, 'tipo' => 'psicologa', 'matricula' => 'AUTORIZACAO_FICTICIA',
                'psicologa_conta_teste' => false, 'psicologa_senha_local_definida' => true,
                'psicologa_configurada_em' => now(), 'psicologa_autenticacao_versao' => 3,
                'psicologa_prontuario_versao' => 4, 'senha_prontuario' => Hash::make(Str::random(24))]);
            $conferir(SegurancaPsicologa::autorizada($usuario));
            $conferir(SegurancaPsicologa::configurada($usuario));
            $usuario->tipo = 'estudante'; $conferir(!SegurancaPsicologa::autorizada($usuario)); $usuario->tipo = 'psicologa';
            $usuario->matricula = 'NAO_AUTORIZADA'; $conferir(!SegurancaPsicologa::autorizada($usuario));
            $usuario->psicologa_conta_teste = true; $conferir(SegurancaPsicologa::autorizada($usuario));
            app()->instance('env', 'production'); $conferir(!SegurancaPsicologa::autorizada($usuario));
            app()->instance('env', 'local'); config(['psicologia.permitir_contas_teste' => false]);
            $conferir(!SegurancaPsicologa::autorizada($usuario));
            $usuario->psicologa_conta_teste = false; $usuario->matricula = 'AUTORIZACAO_FICTICIA';
            $senha = Str::random(20) . 'a1'; $hash = Hash::make($senha);
            $conferir(SegurancaPsicologa::conferir($senha, $hash));
            $conferir(!SegurancaPsicologa::conferir(Str::random(20), $hash));
            $conferir(!SegurancaPsicologa::conferir($senha, null));
            $conferir(!SegurancaPsicologa::conferir($senha, 'valor_sem_hash'));
            foreach ([[$senha, $senha, true], ['curta1', 'curta1', false],
                [str_repeat('a', 12), str_repeat('a', 12), false], [$senha, 'outra', false],
                [str_repeat('á', 40) . '1', str_repeat('á', 40) . '1', false]] as [$nova, $confirmacao, $esperado]) {
                $validacao = Validator::make(['nova_senha' => $nova, 'nova_senha_confirmation' => $confirmacao],
                    ['nova_senha' => SegurancaPsicologa::regrasNovaSenha()]);
                $conferir($validacao->passes() === $esperado);
            }
            $request = Request::create('/verificacao-interna');
            $sessao = new Store('verificacao_psicologa', new ArraySessionHandler(30)); $sessao->start();
            $request->setLaravelSession($sessao); $request->setUserResolver(fn () => $usuario);
            $conferir(!SegurancaPsicologa::sessaoAtual($request, $usuario));
            $sessao->put('psicologa_autenticacao_versao', 3);
            $conferir(SegurancaPsicologa::sessaoAtual($request, $usuario));
            $conferir(!AcessoProntuario::valido($request, $usuario));
            AcessoProntuario::liberar($request, $usuario); $conferir(AcessoProntuario::valido($request, $usuario));
            $usuario->psicologa_prontuario_versao++; $conferir(!AcessoProntuario::valido($request, $usuario));
            $usuario->psicologa_prontuario_versao--; $usuario->psicologa_autenticacao_versao++;
            $conferir(!AcessoProntuario::valido($request, $usuario)); $usuario->psicologa_autenticacao_versao--;
            $sessao->put('prontuario_usuario_id', 888888); $conferir(!AcessoProntuario::valido($request, $usuario));
            AcessoProntuario::liberar($request, $usuario);
            $sessao->put('prontuario_ultimo_acesso', now()->subMinutes(31)->timestamp);
            $conferir(!AcessoProntuario::valido($request, $usuario));
            AcessoProntuario::liberar($request, $usuario); AcessoProntuario::bloquear($request);
            $conferir(!AcessoProntuario::valido($request, $usuario));
            $usuario->psicologa_configurada_em = null;
            $sessao->put(['psicologa_login_metodo' => 'suap', 'psicologa_suap_confirmado_em' => now()->timestamp]);
            SegurancaPsicologa::registrarSenhaSuap($request, $usuario, $senha);
            $conferir(SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, $senha));
            $conferir(!SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, 'OutraSenhaFicticia123'));
            $conferir($sessao->get('psicologa_suap_senha_hash') !== $senha);
            $sessao->put('psicologa_suap_senha_usuario_id', 888888);
            $conferir(!SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, $senha));
            $sessao->put('psicologa_suap_senha_usuario_id', $usuario->id);
            $sessao->put('psicologa_suap_confirmado_em', now()->subMinutes(31)->timestamp);
            $conferir(!SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, $senha));
            $sessao->put('psicologa_suap_confirmado_em', now()->timestamp);
            $usuario->psicologa_configurada_em = now();
            $conferir(!SegurancaPsicologa::senhaSuapCorresponde($request, $usuario, $senha));
            SegurancaPsicologa::registrarSenhaSuap($request, $usuario, $senha);
            $conferir(!$sessao->has('psicologa_suap_senha_hash'));
            $usuario->psicologa_configurada_em = null; $usuario->psicologa_conta_teste = true;
            SegurancaPsicologa::registrarSenhaSuap($request, $usuario, $senha);
            $conferir(!$sessao->has('psicologa_suap_senha_hash'));
            $usuario->psicologa_conta_teste = false;
            SegurancaPsicologa::registrarSenhaSuap($request, $usuario, str_repeat('a', 73));
            $conferir(!$sessao->has('psicologa_suap_senha_hash'));
            $usuario->senha_prontuario = null; $conferir(!SegurancaPsicologa::configurada($usuario));
            $conferir(!array_key_exists('senha_prontuario', $usuario->toArray()));
            $this->info("{$checks} verificações internas passaram. Nenhum cadastro ou prontuário foi alterado.");
        } catch (\Throwable $e) {
            // Não exibe conteúdo de modelos, credenciais ou mensagens de exceção.
            $this->error('Uma verificação interna não passou. Informe ao responsável técnico; não utilize com dados reais antes de resolver.');
            return self::FAILURE;
        } finally {
            config(['psicologia' => $configuracao]); app()->instance('env', $ambiente);
        }
        $this->line('Autorização SUAP: ' . (config('psicologia.matriculas_suap') ? 'configurada' : 'ainda não configurada'));
        $this->line('Contas de teste: ' . (SegurancaPsicologa::testesPermitidos() ? 'liberadas neste ambiente local' : 'bloqueadas'));
        $this->line('Confira também o login SUAP da servidora e o fluxo completo no navegador.');
        return self::SUCCESS;
    }
}
