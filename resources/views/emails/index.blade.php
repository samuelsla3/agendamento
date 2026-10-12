<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Acompanhamento de e-mails — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/acompanhar-emails.css') }}?v=20261008-1">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
    <script src="{{ asset('js/acompanhar-emails.js') }}?v=20261008-1" defer></script>
</head>
<body>
<header class="header">
    <h1>Acompanhamento de e-mails</h1>
    <div class="user-info">
        <span>{{ auth()->user()->nome }}</span>
        <a href="{{ route('configuracoes.index') }}#acompanhamento-emails" class="btn btn-secondary">Voltar às configurações</a>
    </div>
</header>
<main class="container emails-container">
    <section class="content-section">
        <h2>Resultado dos avisos aos alunos</h2>
        <p>Confirmações, lembretes e cancelamentos. Uma falha no e-mail não desfaz o agendamento ou o cancelamento.</p>
        <div class="emails-resumo">
            <p><strong>{{ $problemas }}</strong><span>Precisam de atenção</span></p>
            <p><strong>{{ ($resumo['pendente'] ?? 0) + ($resumo['em_envio'] ?? 0) }}</strong><span>Aguardando ou em envio</span></p>
            <p><strong>{{ $resumo['enviado'] ?? 0 }}</strong><span>Enviados</span></p>
        </div>
        <p class="emails-legenda">Enviado significa aceito pelo transporte de e-mail; não confirma leitura ou chegada à caixa de entrada.</p>
    </section>
    <section class="content-section">
        <h2>Consultar avisos</h2>
        @if($errors->any())
            <div class="emails-mensagem emails-mensagem--erro" role="alert">
                @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
            </div>
        @endif
        <form method="GET" action="{{ route('emails.index') }}" class="emails-filtros">
            <div class="form-group">
                <label for="emails-estado">Situação</label>
                <select id="emails-estado" name="estado">
                    @foreach(['problemas' => 'Precisam de atenção', 'todos' => 'Todos', 'pendente' => 'Aguardando envio',
                        'em_envio' => 'Em envio', 'falhou' => 'Falha no envio', 'revisar' => 'Envio incerto',
                        'sem_contato' => 'Sem e-mail válido', 'enviado' => 'Enviados', 'descartado' => 'Descartados'] as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($estado === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="emails-tipo">Tipo</label>
                <select id="emails-tipo" name="tipo">
                    @foreach(['todos' => 'Todos', 'confirmacao' => 'Confirmação', 'lembrete' => 'Lembrete',
                        'cancelamento' => 'Cancelamento', 'verificacao_email' => 'Confirmação de e-mail', 'legado' => 'Registros anteriores'] as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="emails-inicio">Registrados de</label>
                <input type="date" id="emails-inicio" name="inicio" value="{{ old('inicio', $filtros['inicio'] ?? '') }}">
            </div>
            <div class="form-group">
                <label for="emails-fim">Até</label>
                <input type="date" id="emails-fim" name="fim" value="{{ old('fim', $filtros['fim'] ?? '') }}">
            </div>
            <div class="emails-filtro-acoes">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ request()->fullUrl() }}" class="btn btn-secondary">Atualizar lista</a>
                <a href="{{ route('emails.index') }}" class="btn btn-secondary">Limpar filtros</a>
            </div>
        </form>
        <div id="emails-retorno" class="emails-mensagem" role="status" aria-live="polite" hidden></div>
        @if($avisos->isEmpty())
            <p class="emails-vazio">Nenhum aviso encontrado com estes filtros.</p>
        @else
            <p class="emails-total">{{ $avisos->total() }} {{ $avisos->total() === 1 ? 'aviso encontrado' : 'avisos encontrados' }}.</p>
            <div class="emails-lista">
                @foreach($avisos as $aviso)
                    <article class="emails-item">
                        <div class="emails-item-cabecalho">
                            <h3>{{ $aviso->tipo }} <small>#{{ $aviso->id }}</small></h3>
                            <span class="emails-estado emails-estado--{{ $aviso->estado }}">{{ $aviso->rotulo }}</span>
                        </div>
                        <dl class="emails-dados">
                            <div><dt>Aluno</dt><dd>{{ $aviso->nome }}@if($aviso->matricula)<br><span>{{ $aviso->matricula }}</span>@endif</dd></div>
                            <div><dt>Destinatário do aviso</dt><dd>{{ $aviso->email ?: 'Sem e-mail preservado' }}</dd></div>
                            @if($aviso->data && $aviso->hora)
                                <div><dt>Horário do atendimento</dt><dd>{{ \Carbon\Carbon::parse($aviso->data)->format('d/m/Y') }} às {{ \Carbon\Carbon::parse($aviso->hora)->format('H:i') }}</dd></div>
                            @endif
                            <div><dt>Registrado em</dt><dd>{{ $aviso->criado_em }}</dd></div>
                            <div><dt>Tentativas</dt><dd>{{ $aviso->antigo && !$aviso->tentativas ? 'Contagem não preservada' : $aviso->tentativas }}@if($aviso->tentado_em)<br>Última: {{ $aviso->tentado_em }}@endif</dd></div>
                            @if($aviso->enviado_em)<div><dt>Enviado em</dt><dd>{{ $aviso->enviado_em }}</dd></div>@endif
                        </dl>
                        <p class="emails-orientacao">{{ $aviso->orientacao }}</p>
                        @if($aviso->email_reenvio)<p>Para nova tentativa: {{ $aviso->email_reenvio }}</p>@endif
                        @if($aviso->pode_tentar)
                            <form method="POST" action="{{ route('emails.tentar', ['aviso' => $aviso->id]) }}"
                                class="emails-reenvio" data-estado="{{ $aviso->estado }}" data-aviso-id="{{ $aviso->id }}">
                                @csrf
                                @include('partials.operacao')
                                <input type="hidden" name="versao" value="{{ $aviso->versao }}">
                                @if($aviso->estado === 'revisar')
                                    <label class="emails-confirmar-incerto">
                                        <input type="checkbox" name="confirmou_incerto" value="1" required>
                                        <span>Conferi o envio anterior e preciso tentar novamente. Entendo que, se já tiver sido recebido, o e-mail pode aparecer novamente.</span>
                                    </label>
                                @endif
                                <button type="submit" class="btn btn-primary">{{ $aviso->estado === 'sem_contato' ? 'Tentar com e-mail cadastrado' : 'Tentar novamente' }}</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </div>
            <nav class="emails-paginacao" aria-label="Páginas de avisos">
                @if($avisos->previousPageUrl())<a class="btn btn-secondary" href="{{ $avisos->previousPageUrl() }}">Anterior</a>@endif
                <span>Página {{ $avisos->currentPage() }} de {{ $avisos->lastPage() }}</span>
                @if($avisos->nextPageUrl())<a class="btn btn-secondary" href="{{ $avisos->nextPageUrl() }}">Próxima</a>@endif
            </nav>
        @endif
    </section>
</main>
<script>const LaravelConfig = {csrfToken: @json(csrf_token())};</script>
</body>
</html>
