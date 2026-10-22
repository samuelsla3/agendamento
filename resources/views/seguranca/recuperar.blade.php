<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    @include('partials.favicon')
    <title>Recuperar senha — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20261022-recuperacao-1">
    <link rel="stylesheet" href="{{ asset('css/seguranca-psicologa.css') }}?v=20261008-1">
    <link rel="stylesheet" href="{{ asset('css/identidade-visual.css') }}?v=20261013-1">
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
</head>
<body class="ui-sistema ui-seguranca">
    <header class="header"><h1>Recuperação de senha</h1><a href="{{ route('login') }}" class="btn btn-secondary">Voltar ao login</a></header>
    <main class="container seguranca-container">
        <section class="content-section">
            <h2>Recuperar senha da psicóloga</h2>
            <p>Informe sua matrícula e escolha a senha que deseja redefinir. O link será enviado ao e-mail de contato cadastrado em Configurações → Informações do atendimento.</p>
            @if(session('sucesso'))<p class="seguranca-aviso seguranca-sucesso" role="status">{{ session('sucesso') }}</p>@endif
            @if($errors->any())<div class="seguranca-aviso seguranca-erro" role="alert">@foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach</div>@endif
            <form action="{{ route('seguranca.recuperacao.enviar') }}" method="POST" data-texto-envio="Solicitando recuperação…">
                @csrf
                <div class="form-group">
                    <label for="recuperar-matricula">Matrícula da psicóloga</label>
                    <input type="text" id="recuperar-matricula" name="matricula" maxlength="20" inputmode="numeric" autocomplete="username" required value="{{ old('matricula', $matricula) }}">
                </div>
                <div class="form-group">
                    <label for="recuperar-tipo">Qual senha deseja redefinir?</label>
                    <select id="recuperar-tipo" name="tipo" required>
                        <option value="local" @selected(old('tipo') === 'local')>Senha local de login</option>
                        <option value="prontuario" @selected(old('tipo') === 'prontuario')>Senha do prontuário</option>
                    </select>
                </div>
                <p>O link vale por 15 minutos e só pode ser usado uma vez. A senha do SUAP é recuperada no próprio SUAP. Se não tiver acesso ao e-mail cadastrado, procure o responsável técnico.</p>
                <div class="seguranca-acoes"><button type="submit" class="btn btn-primary">Enviar link por e-mail</button></div>
            </form>
        </section>
    </main>
</body>
</html>
