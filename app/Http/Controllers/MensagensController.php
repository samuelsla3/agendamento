<?php

namespace App\Http\Controllers;

use App\Services\AcessoProntuario;
use App\Services\ComunicacaoPrivada;
use App\Services\SegurancaPsicologa;
use Illuminate\Http\Request;

class MensagensController extends Controller
{
    public function __construct(private ComunicacaoPrivada $comunicacao) {}

    private function json(array $dados)
    {
        return response()->json($dados)->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function resumo(Request $request) { return $this->json($this->comunicacao->resumo($request)); }

    public function desbloquear(Request $request)
    {
        $usuario = $this->comunicacao->usuario($request); abort_unless($usuario->tipo === 'psicologa', 403);
        $dados = $request->validate(['senha' => ['required', 'string', 'max:512']]);
        if (!SegurancaPsicologa::conferir($dados['senha'], $usuario->senha_prontuario)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['senha' => 'A senha do prontuário está incorreta.']);
        }
        $request->session()->regenerate(true); AcessoProntuario::liberar($request, $usuario);
        return $this->json(['status' => 'success', 'csrf' => csrf_token(), ...$this->comunicacao->resumo($request)]);
    }

    public function abrir(Request $request)
    {
        $this->comunicacao->usuario($request, true);
        $dados = $request->validate(['aluno_id' => ['nullable', 'integer', 'min:1']]);
        return $this->json(['conversa' => $this->comunicacao->abrir($request, isset($dados['aluno_id']) ? (int) $dados['aluno_id'] : null),
            'nova_chave' => (string) \Illuminate\Support\Str::uuid()]);
    }

    public function historico(Request $request, int $conversa)
    {
        $this->comunicacao->usuario($request, true);
        $request->validate(['depois' => ['nullable', 'integer', 'min:1'], 'antes' => ['nullable', 'integer', 'min:1']]);
        abort_if($request->filled('depois') && $request->filled('antes'), 422);
        return $this->json($this->comunicacao->historico($request, $conversa));
    }

    public function enviar(Request $request, int $conversa)
    {
        $this->comunicacao->usuario($request, true);
        $quantidade = (int) config('comunicacao.arquivos_por_mensagem', 3);
        $tamanhoKb = (int) ceil(config('comunicacao.arquivo_maximo_bytes', 10485760) / 1024);
        $dados = $request->validate(['texto' => ['nullable', 'string', 'max:10000'], 'chave_envio' => ['required', 'uuid'],
            'arquivos' => ['nullable', 'array', 'max:' . $quantidade], 'arquivos.*' => ['required', 'file', 'max:' . $tamanhoKb]], [
                'texto.string' => 'Escreva a mensagem em texto.', 'texto.max' => 'A mensagem pode ter até 10.000 caracteres.',
                'chave_envio.required' => 'Abra novamente a conversa antes de enviar.', 'chave_envio.uuid' => 'Abra novamente a conversa antes de enviar.',
                'arquivos.array' => 'Selecione os arquivos pelo botão de anexar.', 'arquivos.max' => 'Selecione no máximo 3 arquivos.',
                'arquivos.*.uploaded' => 'O servidor não recebeu um arquivo. Confira o tamanho permitido e tente novamente.',
                'arquivos.*.file' => 'Selecione um arquivo válido.', 'arquivos.*.max' => 'Cada arquivo pode ter até 10 MB.',
            ]);
        if (trim((string) ($dados['texto'] ?? '')) === '' && !$request->file('arquivos')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['texto' => 'Escreva uma mensagem ou selecione um arquivo.']);
        }
        return $this->json(['status' => 'success', 'mensagem' => $this->comunicacao->enviar($request, $conversa, $dados),
            'nova_chave' => (string) \Illuminate\Support\Str::uuid(),
            ...$this->comunicacao->resumo($request)]);
    }

    public function lidas(Request $request, int $conversa)
    {
        $this->comunicacao->usuario($request, true);
        $dados = $request->validate(['ate' => ['required', 'integer', 'min:1']]);
        $this->comunicacao->marcarLidas($request, $conversa, (int) $dados['ate']);
        return $this->json($this->comunicacao->resumo($request));
    }

    private function filtros(Request $request): void
    {
        $this->comunicacao->usuario($request, true);
        $request->validate(['nome' => ['nullable', 'string', 'max:100'], 'turma' => ['nullable', 'string', 'max:50'],
            'matricula' => ['nullable', 'string', 'max:20'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
    }

    public function contatos(Request $request) { $this->filtros($request); return $this->json($this->comunicacao->contatos($request)); }
    public function alunos(Request $request) { $this->filtros($request); return $this->json($this->comunicacao->alunos($request)); }

    public function anexo(Request $request, string $anexo)
    {
        $dados = $this->comunicacao->anexo($request, $anexo);
        return response()->streamDownload(function () use ($dados) { echo $dados['conteudo']; }, $dados['nome'], [
            'Content-Type' => $dados['mime'], 'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
