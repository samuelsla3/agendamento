<div style="max-width:600px;margin:20px auto 0;padding:16px;box-sizing:border-box;border-top:1px solid #ddd;font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6;">
    <strong>Informações do setor de Psicologia</strong>
    @foreach(['local' => 'Local', 'orientacoes' => 'Orientações de chegada', 'email' => 'E-mail de contato', 'telefone' => 'Telefone ou ramal', 'contatos' => 'Outros contatos'] as $chave => $rotulo)
        @if(isset($informacoes[$chave]))
            <p style="margin:8px 0;overflow-wrap:anywhere;"><strong>{{ $rotulo }}:</strong><br>{!! nl2br(e($informacoes[$chave])) !!}</p>
        @endif
    @endforeach
</div>
