<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Segurança — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/seguranca-psicologa.css') }}?v=20261008-1">
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
    <script src="{{ asset('js/seguranca-psicologa.js') }}?v=20261008-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/identidade-visual.css') }}?v=20261013-1">
</head>
<body class="ui-sistema ui-seguranca">
    <header class="header"><h1>Segurança</h1><div class="user-info"><span>{{ $usuario->nome }}</span><a href="{{ route('configuracoes.index') }}" class="btn btn-secondary">Voltar às configurações</a></div></header>
    <main class="container seguranca-container">
        @include('seguranca._mensagens')
        <section class="content-section">
            <h2>Alterar senha local</h2>
            <p>Após o primeiro cadastro, use esta senha no formulário de login com sua matrícula. A senha do SUAP é administrada no SUAP.</p>
            <form action="{{ route('seguranca.local') }}" method="POST" data-texto-envio="Alterando senha local…">
                @csrf @include('partials.operacao')
                <input type="hidden" name="versao_acesso" value="{{ $usuario->psicologa_autenticacao_versao }}">
                <input type="hidden" name="versao_prontuario" value="{{ $usuario->psicologa_prontuario_versao }}">
                @include('seguranca._senha', ['campo' => 'senha_atual', 'identificador' => 'local-atual', 'rotulo' => 'Senha local atual'])
                @include('seguranca._senha', ['campo' => 'nova_senha', 'identificador' => 'local-nova', 'rotulo' => 'Nova senha local', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'nova_senha_confirmation', 'identificador' => 'local-confirmacao', 'rotulo' => 'Confirme a nova senha local', 'nova' => true])
                <p class="seguranca-regras">Use 12 a 64 caracteres, com letras e números, e uma senha diferente da do prontuário. Alterar encerra as outras sessões e bloqueia os prontuários já abertos.</p>
                <div class="seguranca-acoes"><button type="submit" class="btn btn-primary">Alterar senha local</button></div>
            </form>
        </section>
        <section class="content-section">
            <h2>Alterar senha do prontuário</h2>
            <form action="{{ route('seguranca.prontuario') }}" method="POST" data-texto-envio="Alterando senha do prontuário…">
                @csrf @include('partials.operacao')
                <input type="hidden" name="versao_acesso" value="{{ $usuario->psicologa_autenticacao_versao }}">
                <input type="hidden" name="versao_prontuario" value="{{ $usuario->psicologa_prontuario_versao }}">
                @include('seguranca._senha', ['campo' => 'senha_atual', 'identificador' => 'prontuario-atual', 'rotulo' => 'Senha atual do prontuário'])
                @include('seguranca._senha', ['campo' => 'nova_senha', 'identificador' => 'prontuario-nova', 'rotulo' => 'Nova senha do prontuário', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'nova_senha_confirmation', 'identificador' => 'prontuario-confirmacao', 'rotulo' => 'Confirme a nova senha do prontuário', 'nova' => true])
                <p class="seguranca-regras">Use 12 a 64 caracteres, com letras e números, e uma senha diferente da local. Será necessário desbloquear os prontuários novamente, inclusive em outros dispositivos.</p>
                <div class="seguranca-acoes"><button type="submit" class="btn btn-primary">Alterar senha do prontuário</button></div>
            </form>
        </section>
        <section class="content-section">
            <h2>Esqueceu uma das senhas?</h2>
            <p>Procure o responsável técnico pelo sistema para confirmar sua identidade e redefinir a senha. A recuperação não apaga as anotações e não revela a senha antiga.</p>
            <p>O prontuário também é bloqueado após 30 minutos sem uso. Para acessá-lo novamente, informe a senha do prontuário.</p>
        </section>
    </main>
</body></html>
