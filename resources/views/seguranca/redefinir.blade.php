<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    @include('partials.favicon')
    <title>Redefinir senha — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20261022-recuperacao-1">
    <link rel="stylesheet" href="{{ asset('css/seguranca-psicologa.css') }}?v=20261008-1">
    <link rel="stylesheet" href="{{ asset('css/identidade-visual.css') }}?v=20261013-1">
    <script src="{{ asset('js/seguranca-psicologa.js') }}?v=20261008-1" defer></script>
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
</head>
<body class="ui-sistema ui-seguranca">
    <header class="header"><h1>Redefinição de senha</h1><a href="{{ route('login') }}" class="btn btn-secondary">Voltar ao login</a></header>
    <main class="container seguranca-container">
        <section class="content-section">
        @if(!$recuperacao)
            <h2>Link indisponível</h2>
            <p>Este link expirou, já foi utilizado ou deixou de ser válido após uma alteração no cadastro. Solicite uma nova recuperação.</p>
            <a href="{{ route('seguranca.recuperacao.form') }}" class="btn btn-info">Solicitar outro link</a>
        @else
            <h2>Redefinir senha {{ $recuperacao->tipo === 'prontuario' ? 'do prontuário' : 'local' }}</h2>
            @if($errors->any())<div class="seguranca-aviso seguranca-erro" role="alert">@foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach</div>@endif
            <form action="{{ route('seguranca.recuperacao.salvar', ['id' => $recuperacao->id]) }}" method="POST" data-texto-envio="Redefinindo senha…">
                @csrf
                @include('seguranca._senha', ['campo' => 'nova_senha', 'identificador' => 'recuperacao-nova', 'rotulo' => 'Nova senha', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'nova_senha_confirmation', 'identificador' => 'recuperacao-confirmacao', 'rotulo' => 'Confirme a nova senha', 'nova' => true])
                <p>Use 12 a 64 caracteres, com letras e números. Escolha uma senha diferente da anterior e da outra senha do sistema. A recuperação não apaga as anotações.</p>
                <div class="seguranca-acoes"><button type="submit" class="btn btn-primary">Salvar nova senha</button></div>
            </form>
        @endif
        </section>
    </main>
</body>
</html>
