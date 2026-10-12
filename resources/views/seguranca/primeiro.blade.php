<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Primeiro acesso — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/seguranca-psicologa.css') }}?v=20261012-1">
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
    <script src="{{ asset('js/seguranca-psicologa.js') }}?v=20261008-1" defer></script>
</head>
<body>
    <header class="header"><h1>Configure seu acesso</h1><div class="user-info">
        <span>{{ $usuario->nome }}</span>
        <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="btn btn-secondary">Sair</button></form>
    </div></header>
    <main class="container seguranca-container">
        @include('seguranca._mensagens')
        <section class="content-section">
            <h2>Crie suas senhas</h2>
            <p>A senha de login será usada para entrar neste sistema depois do primeiro cadastro. A senha do prontuário protege o acesso às anotações dos alunos.</p>
            <p class="seguranca-regras">Use senhas diferentes, com 12 a 64 caracteres, incluindo letras e números. Guarde-as em local seguro. Este cadastro não altera a senha do SUAP.</p>
            <form action="{{ route('seguranca.primeiro.salvar') }}" method="POST" data-texto-envio="Cadastrando senhas…">
                @csrf @include('partials.operacao')
                <input type="hidden" name="versao_acesso" value="{{ $usuario->psicologa_autenticacao_versao }}">
                <input type="hidden" name="versao_prontuario" value="{{ $usuario->psicologa_prontuario_versao }}">
                @if($usuario->psicologa_conta_teste)
                    @include('seguranca._senha', ['campo' => 'senha_atual', 'rotulo' => 'Senha atual do login de testes'])
                @endif
                @if(!$usuario->psicologa_conta_teste)
                    <label class="seguranca-opcao" for="usarSenhaSuap">
                        <input type="checkbox" id="usarSenhaSuap" name="usar_senha_suap" value="1" @checked(old('usar_senha_suap')) aria-describedby="orientacaoSenhaSuap">
                        <span>Quero usar a mesma senha do SUAP para entrar neste sistema.</span>
                    </label>
                    <p id="orientacaoSenhaSuap" class="seguranca-regras">Se marcar, digite e confirme abaixo a senha usada no seu login pelo SUAP. Ela deve atender às regras acima. Se preferir, deixe desmarcado e crie uma senha diferente. Alterações futuras de senha no SUAP e neste sistema são independentes.</p>
                @endif
                @include('seguranca._senha', ['campo' => 'senha_local', 'rotulo' => 'Senha para entrar no sistema', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'senha_local_confirmation', 'rotulo' => 'Confirme a senha de login', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'senha_prontuario', 'rotulo' => 'Senha do prontuário', 'nova' => true])
                @include('seguranca._senha', ['campo' => 'senha_prontuario_confirmation', 'rotulo' => 'Confirme a senha do prontuário', 'nova' => true])
                <div class="seguranca-acoes"><button type="submit" class="btn btn-primary">Concluir e abrir a agenda</button></div>
            </form>
        </section>
    </main>
</body></html>
