<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Configurações — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
    <style>
        .config-container { max-width: 720px; }
        .config-card h2 { text-align: left; margin-top: 0; }
        .config-card p { line-height: 1.6; }
        .config-card input[type="time"] { max-width: 240px; font: inherit; }
        .config-acoes { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        .config-acoes a, .header a { text-decoration: none; }
        .config-aviso { padding: 14px; border-radius: 6px; margin: 16px 0; }
        .config-sucesso { background: #e8f5ec; color: #176536; }
        .config-erro { background: #fff1f1; color: #9d2020; }
        @media (max-width: 767px) {
            .config-acoes > .btn { width: 100%; box-sizing: border-box; text-align: center; }
            .config-card input[type="time"] { max-width: none; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Configurações</h1>
        <div class="user-info">
            <span>{{ auth()->user()->nome }}</span>
            <a href="{{ route('psicologa.index') }}" class="btn btn-secondary">Voltar à agenda</a>
        </div>
    </header>

    <main class="container config-container">
        @if(session('sucesso'))
            <div class="config-aviso config-sucesso" role="status">{{ session('sucesso') }}</div>
        @endif
        @if($errors->any())
            <div class="config-aviso config-erro" role="alert">
                @foreach($errors->all() as $erro)
                    <p>{{ $erro }}</p>
                @endforeach
            </div>
        @endif

        <section class="content-section config-card">
            <h2>Lembretes por e-mail</h2>
            <p>Os lembretes são enviados <strong>no dia anterior ao atendimento</strong>, no horário escolhido abaixo.</p>
            <form action="{{ route('configuracoes.atualizar') }}" method="POST" data-texto-envio="Salvando…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <div class="form-group">
                    <label for="horario_lembretes">Horário de envio</label>
                    <input type="time" id="horario_lembretes" name="horario_lembretes" step="60" required
                        aria-describedby="orientacao-horario"
                        value="{{ old('horario_lembretes', $configuracao->horario_lembretes) }}">
                </div>
                <p id="orientacao-horario">Horário de Brasília. Exemplo: um atendimento na terça-feira recebe o lembrete na segunda-feira, no horário definido.</p>
                <p>Salvar não envia e-mails imediatamente. Se o horário escolhido já passou hoje, o próximo envio automático será amanhã.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    <a href="{{ route('psicologa.index') }}" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>