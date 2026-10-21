<?php

namespace App\Http\Controllers;

use App\Services\ComunicacaoPrivada;
use App\Services\EmailsContato;
use Illuminate\Http\Request;

class EmailMensagemController extends Controller
{
    public function __construct(private EmailsContato $emails, private ComunicacaoPrivada $comunicacao) {}

    public function enviar(Request $request, int $conversa)
    {
        $this->comunicacao->autorizarConversaEmail($request, $conversa);
        $dados = $request->validate([
            'chave_envio' => ['required', 'uuid'],
            'assunto' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'texto' => ['nullable', 'string', 'max:10000'],
            'arquivos' => ['nullable', 'array', 'max:'.(int) config('comunicacao.arquivos_por_mensagem', 3)],
            'arquivos.*' => ['required', 'file', 'max:'.(int) ceil(config('comunicacao.arquivo_maximo_bytes', 10485760) / 1024)],
        ], [
            'assunto.required' => 'Informe o assunto do e-mail.', 'assunto.max' => 'O assunto pode ter até 150 caracteres.',
            'assunto.not_regex' => 'O assunto deve ocupar uma única linha.', 'texto.max' => 'O texto pode ter até 10.000 caracteres.',
            'arquivos.max' => 'Selecione no máximo 3 arquivos.', 'arquivos.*.max' => 'Cada arquivo pode ter até 10 MB.',
            'arquivos.*.uploaded' => 'O servidor não recebeu um arquivo. Confira seu tamanho e tente novamente.',
        ]);
        return response()->json($this->emails->enviar($request, $conversa, $dados))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function status(Request $request, int $conversa, string $chave)
    {
        return response()->json($this->emails->status($request, $conversa, $chave))
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }
}
