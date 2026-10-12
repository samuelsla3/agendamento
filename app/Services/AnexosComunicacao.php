<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class AnexosComunicacao
{
    public function disco()
    {
        return Storage::build(['driver' => 'local', 'root' => storage_path('app/contato-psicologia'),
            'visibility' => 'private', 'throw' => true]);
    }

    public function preparar(array $arquivos): array
    {
        if (count($arquivos) > (int) config('comunicacao.arquivos_por_mensagem', 3)) $this->erro('Quantidade de anexos acima do limite permitido.');
        $tipos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'webp' => 'image/webp'];
        $preparados = [];
        foreach ($arquivos as $arquivo) {
            if (!$arquivo instanceof UploadedFile || !$arquivo->isValid()) $this->erro('O arquivo não pôde ser recebido. Tente selecioná-lo novamente.');
            $extensao = strtolower($arquivo->getClientOriginalExtension());
            $mime = $arquivo->getMimeType();
            $tamanho = $arquivo->getSize();
            if (!isset($tipos[$extensao]) || $tipos[$extensao] !== $mime) $this->erro('Envie somente PDF, JPG, PNG ou WEBP com conteúdo válido.');
            if (!$tamanho || $tamanho > config('comunicacao.arquivo_maximo_bytes')) $this->erro('Cada arquivo pode ter até 10 MB.');
            $inicio = file_get_contents($arquivo->getRealPath(), false, null, 0, 12);
            $valido = match ($mime) {
                'application/pdf' => str_starts_with((string) $inicio, '%PDF-'),
                'image/jpeg' => str_starts_with((string) $inicio, "\xFF\xD8\xFF"),
                'image/png' => str_starts_with((string) $inicio, "\x89PNG\r\n\x1a\n"),
                'image/webp' => substr((string) $inicio, 0, 4) === 'RIFF' && substr((string) $inicio, 8, 4) === 'WEBP',
                default => false,
            };
            if (!$valido) $this->erro('O conteúdo do arquivo não corresponde ao formato informado.');
            $scanner = trim((string) config('comunicacao.antivirus_binario'));
            if ($scanner !== '') {
                $processo = new Process([$scanner, '--no-summary', $arquivo->getRealPath()]);
                $processo->setTimeout(30);
                try { $processo->run(); }
                catch (\Throwable $e) { $this->erro('Não foi possível verificar o anexo. Procure o responsável pelo sistema.'); }
                if (!$processo->isSuccessful()) $this->erro('O anexo não passou pela verificação de segurança. Selecione outro arquivo ou procure o setor.');
            }
            $nome = basename(str_replace('\\', '/', $arquivo->getClientOriginalName()));
            $nome = preg_replace('/[^\p{L}\p{N}._ ()-]/u', '_', $nome) ?? ('anexo.' . $extensao);
            $base = mb_substr(pathinfo($nome, PATHINFO_FILENAME), 0, 110, 'UTF-8');
            if (trim($base, '. ') === '') $base = 'anexo';
            $nome = $base . '.' . $extensao;
            $uuid = (string) Str::uuid();
            $preparados[] = ['upload' => $arquivo, 'nome' => $nome, 'mime' => $mime, 'tamanho' => $tamanho,
                'hash' => hash_file('sha256', $arquivo->getRealPath()), 'uuid' => $uuid, 'arquivo' => $uuid . '.bin'];
        }
        return $preparados;
    }

    public function gravar(int $mensagemId, array $arquivos): void
    {
        foreach ($arquivos as $arquivo) {
            $conteudo = file_get_contents($arquivo['upload']->getRealPath());
            if ($conteudo === false) throw new \RuntimeException('anexo_indisponivel');
            $this->disco()->put('anexos/' . $arquivo['arquivo'], Crypt::encryptString($conteudo));
            unset($conteudo);
            DB::table('mensagens_anexos')->insert(['mensagem_id' => $mensagemId, 'uuid' => $arquivo['uuid'],
                'nome' => Crypt::encryptString($arquivo['nome']), 'mime' => $arquivo['mime'],
                'tamanho' => $arquivo['tamanho'], 'arquivo' => $arquivo['arquivo'], 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function limparNaoRegistrados(array $arquivos): void
    {
        if (!$arquivos) return;
        // Se o estado do commit ficou incerto, conserva o arquivo até poder conferir.
        try {
            $registrados = DB::table('mensagens_anexos')->whereIn('arquivo', array_column($arquivos, 'arquivo'))->pluck('arquivo')->all();
            foreach ($arquivos as $arquivo) {
                if (!in_array($arquivo['arquivo'], $registrados, true)) $this->disco()->delete('anexos/' . $arquivo['arquivo']);
            }
        } catch (\Throwable $e) { /* Nenhum conteúdo, caminho ou nome de aluno é registrado em log. */ }
    }

    public function ler(object $anexo): string
    {
        abort_unless(preg_match('/^[a-f0-9-]{36}\.bin$/D', $anexo->arquivo), 404);
        $conteudo = Crypt::decryptString($this->disco()->get('anexos/' . $anexo->arquivo));
        abort_unless(strlen($conteudo) === (int) $anexo->tamanho, 500, 'Não foi possível recuperar o arquivo.');
        return $conteudo;
    }

    private function erro(string $mensagem): never
    {
        throw ValidationException::withMessages(['arquivos' => $mensagem]);
    }
}
