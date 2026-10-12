<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Services\AnexosComunicacao;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerificarComunicacaoPrivada extends Command
{
    protected $signature = 'comunicacao:verificar';
    protected $description = 'Verifica tabelas, rotas, criptografia e armazenamento privado usando arquivos fictícios, sem enviar mensagens.';

    private function verificarViews(): void
    {
        $guard = Auth::guard();
        $original = $guard->user();
        try {
            $guard->forgetUser();
            view('partials.mensagens-botao')->render();
            view('partials.mensagens-modal')->render();
            // Usuários fictícios apenas em memória: verifica também os ramos do
            // Blade exibidos após login, sem autenticar ou salvar uma conta.
            foreach (['estudante', 'psicologa'] as $tipo) {
                $ficticio = new Usuario();
                $ficticio->forceFill(['id' => 0, 'tipo' => $tipo]);
                $guard->setUser($ficticio);
                view('partials.mensagens-botao')->render();
                $html = view('partials.mensagens-modal')->render();
                if (!preg_match('/<script[^>]*id="mensagens-dados"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
                    throw new \RuntimeException('dados da interface ausentes');
                }
                $dados = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
                if (($dados['psicologa'] ?? null) !== ($tipo === 'psicologa') || empty($dados['contexto'])) {
                    throw new \RuntimeException('dados da interface invalidos');
                }
            }
        } finally {
            if ($original) $guard->setUser($original);
            else $guard->forgetUser();
        }
    }

    public function handle(AnexosComunicacao $anexos): int
    {
        $tabelas = ['mensagens_conversas' => ['aluno_id', 'lida_aluno_ate', 'lida_psicologa_ate'],
            'mensagens_internas' => ['conversa_id', 'remetente_id', 'origem', 'chave_envio', 'resumo_hash', 'conteudo'],
            'mensagens_anexos' => ['mensagem_id', 'uuid', 'nome', 'mime', 'tamanho', 'arquivo']];
        foreach ($tabelas as $tabela => $colunas) {
            if (!Schema::hasTable($tabela) || !Schema::hasColumns($tabela, $colunas)) {
                $this->error('As tabelas de comunicação ainda não foram instaladas. Execute a migration 2026_10_12_010000.'); return self::FAILURE;
            }
        }
        if (!Schema::hasColumns('usuarios', ['senha_prontuario', 'psicologa_autenticacao_versao', 'psicologa_prontuario_versao'])) {
            $this->error('Instale primeiro o pacote de segurança da psicóloga.'); return self::FAILURE;
        }
        foreach (['resumo', 'desbloquear', 'abrir', 'historico', 'enviar', 'lidas', 'contatos', 'alunos', 'anexo'] as $rota) {
            if (!Route::has('mensagens.' . $rota)) { $this->error('Rotas ausentes. Limpe o cache de rotas e confira o ZIP instalado.'); return self::FAILURE; }
        }
        if (!extension_loaded('fileinfo') || !extension_loaded('openssl')) {
            $this->error('Ative fileinfo e openssl no PHP para validar e proteger os anexos.'); return self::FAILURE;
        }
        $nome = 'verificacoes/' . Str::uuid() . '.bin';
        $arquivo = null;
        try {
            $this->verificarViews();
            $binario = "conteudo ficticio\x00\xff" . Str::random(32);
            $criptografado = Crypt::encryptString($binario);
            if (!hash_equals($binario, Crypt::decryptString($criptografado))) throw new \RuntimeException('criptografia');
            $anexos->disco()->put($nome, $criptografado);
            if (!hash_equals($binario, Crypt::decryptString($anexos->disco()->get($nome)))) throw new \RuntimeException('disco');
            $arquivo = tempnam(sys_get_temp_dir(), 'contato_verificar_');
            if (!$arquivo) throw new \RuntimeException('temporario');
            file_put_contents($arquivo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jWl0AAAAASUVORK5CYII='));
            $upload = new UploadedFile($arquivo, 'teste.png', 'image/png', null, true);
            $validos = $anexos->preparar([$upload]);
            if (count($validos) !== 1 || $validos[0]['mime'] !== 'image/png') throw new \RuntimeException('png');
            $negado = false;
            try { $anexos->preparar([new UploadedFile($arquivo, 'teste.pdf', 'application/pdf', null, true)]); }
            catch (ValidationException $e) { $negado = true; }
            if (!$negado) throw new \RuntimeException('mime');
            $negado = false;
            try { $anexos->preparar([new UploadedFile($arquivo, 'teste.php', 'image/png', null, true)]); }
            catch (ValidationException $e) { $negado = true; }
            if (!$negado) throw new \RuntimeException('extensao');
            $this->info('Tabelas, rotas, criptografia, pasta privada e rejeição de formatos incorretos conferidos.');
            $this->line('Nenhuma mensagem foi enviada e nenhum prontuário foi consultado ou alterado.');
        } catch (\Throwable $e) {
            $this->error('Uma verificação não passou. Confira os partials de mensagens, rotas, APP_KEY, permissões da pasta privada, PHP e configuração do antivírus com o responsável técnico.');
            return self::FAILURE;
        } finally {
            try { $anexos->disco()->delete($nome); } catch (\Throwable $e) {}
            if ($arquivo && is_file($arquivo)) unlink($arquivo);
        }
        $this->line('Antivírus: ' . (trim((string) config('comunicacao.antivirus_binario')) !== '' ? 'configurado' : 'não configurado; validação de tipo e assinatura permanece ativa'));
        $this->line('Confira também no navegador o acesso entre dois alunos diferentes, os anexos e o bloqueio da psicóloga.');
        return self::SUCCESS;
    }
}
