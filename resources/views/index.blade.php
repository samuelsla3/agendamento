<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicon')
    <title>Calendário de Agendamentos</title>
    
    <link rel="stylesheet" href="{{ asset('css/style_main.css') }}?v={{ time() }}">
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/aviso-alunos.css') }}?v=20261008-1">
    <link rel="stylesheet" href="{{ asset('css/informacoes-atendimento.css') }}?v=20261008-1">
    <script src="{{ asset('js/informacoes-atendimento.js') }}?v=20261008-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/contato-email.css') }}?v=20261008-1">
    <script src="{{ asset('js/contato-email.js') }}?v=20261008-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/sugestoes.css') }}?v=20261012-1">
    <script src="{{ asset('js/sugestoes.js') }}?v=20261021-rodape-1" defer></script>
<script src="{{ asset('js/operacoes.js') }}?v=20260924-1"></script>
    <link rel="stylesheet" href="{{ asset('css/mensagens.css') }}?v=20261013-imagens-1">
    <script src="{{ asset('js/mensagens.js') }}?v=20261021-email-direto-1" defer></script>
    <script src="{{ asset('js/limites-texto.js') }}?v=20261013-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/identidade-visual.css') }}?v=20261013-1">
    <style>
        .aluno-sair { display:inline-flex; align-items:center; justify-content:center; gap:7px; }
        .aluno-sair span { margin:0; }
        .aluno-sair svg { flex-shrink:0; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/desenvolvedores.css') }}?v=20261013-1">
    <link rel="stylesheet" href="{{ asset('css/apresentacao-publica.css') }}?v=20261021-cabecalho-mobile-1">
    <link rel="stylesheet" href="{{ asset('css/rodape-aluno.css') }}?v=20261021-1">
    <script src="{{ asset('js/apresentacao-publica.js') }}?v=20261013-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/indisponibilidade-agenda.css') }}?v=20261021-1">
</head>
<body class="ui-sistema ui-aluno">

    <header class="header{{ auth()->check() ? ' aluno-cabecalho-logado' : '' }}">
        <h1>Agendamento Psicológico</h1>
        <div class="user-info">
            @if(auth()->check())
                @php
                    $partesNomeAluno = preg_split('/\s+/u', trim((string) auth()->user()->nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $nomeCurtoAluno = count($partesNomeAluno) > 1
                        ? $partesNomeAluno[0] . ' ' . $partesNomeAluno[count($partesNomeAluno) - 1]
                        : ($partesNomeAluno[0] ?? 'Aluno');
                @endphp
                <span>{{ $nomeCurtoAluno }}</span>
                @if(($contatoEmail ?? null) !== null)
                    <button type="button" class="contato-email-link" data-abrir-contato-email aria-controls="contato-email-modal" aria-expanded="false">Alterar e-mail</button>
                @endif
                
                @include('partials.mensagens-botao')
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
            @include('partials.operacao')
                    <button type="submit" class="btn btn-danger aluno-sair" title="Sair do sistema">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M9 5H5v14h4M10 12h10M16 8l4 4-4 4"></path>
                        </svg>
                        <span>Sair</span>
                    </button>
                </form>
            @else
                <button class="btn btn-primary" onclick="window.location.href='{{ route('login') }}';">Fazer Login</button>
            @endif
        </div>
    </header>

    <div class="container">
        @include('partials.aviso-alunos')
        @include('partials.informacoes-atendimento')
        <div id="message-box" class="message-box"></div>
        @if(($contatoEmail ?? null) !== null && !$contatoEmail['confirmado'])
            <aside class="contato-email-pendente">
                Confirme o e-mail que você utiliza para receber os avisos dos agendamentos.
                <button type="button" class="contato-email-link" data-abrir-contato-email aria-controls="contato-email-modal" aria-expanded="false">Confirmar e-mail</button>
            </aside>
        @endif
        
        <section class="content-section">
            <h2>Calendário de Horários Disponíveis</h2>
            <p class="instrucao-texto">
    Clique em um horário <strong>disponível</strong> para fazer seu agendamento. 
    @guest É necessário estar logado. @endguest
</p>
            @if($antecedenciaAgendamento['ativa'])
                <p class="instrucao-subtexto">Agende com pelo menos {{ $antecedenciaAgendamento['descricao'] }} de antecedência.</p>
            @endif
            <div id="calendar"></div>
        </section>

        @guest
            @include('partials.apresentacao-publica')
        @endguest

        @if(auth()->check() && (auth()->user()->tipo === 'estudante' || auth()->user()->tipo === 'aluno'))
        <section class="content-section">
            <h2>Seu Histórico de Agendamentos</h2>
            <p class="instrucao-subtexto">(Agendamentos, atendimentos realizados, faltas e cancelamentos)</p>

            @if(count($registros) > 0)
                <h3 style="margin-top: 20px; font-size: 1.2em; color: #333;">
                    Total de Agendamentos Localizados: {{ count($registros) }}
                </h3>
                
                <table id='tabela-historico-aluno' style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                    <thead style='background-color: #f2f2f2;'>
                        <tr>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Data / Hora do Atendimento</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Status do Horário</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($registros as $reg)
                            @php
                                $data_hora = !empty($reg->data_atendimento) && !empty($reg->hora_atendimento)
                                    ? \Carbon\Carbon::parse($reg->data_atendimento)->format('d/m/Y')
                                        . ' às ' . \Carbon\Carbon::parse($reg->hora_atendimento)->format('H:i')
                                    : 'Data/hora não preservadas neste registro antigo';
                                
                                if ($reg->status === 'Agendado') {
                                    $status_texto = 'Agendado';
                                    $status_class = 'text-success';
                                } elseif ($reg->status === 'Realizado') {
                                    $status_texto = 'Atendimento Realizado';
                                    $status_class = 'text-success';
                                } elseif ($reg->status === 'Não compareceu') {
                                    $status_texto = 'Não compareceu';
                                    $status_class = 'text-warning';
                                } elseif ($reg->status === 'Cancelado pelo Aluno') {
                                    $status_texto = 'Cancelado por Você';
                                    $status_class = 'text-warning';
                                } else {
                                    $status_texto = 'Cancelado pela Psicóloga';
                                    $status_class = 'text-danger';
                                }
                            @endphp
                            <tr @if($reg->status === 'Agendado') style="background-color: #f0fdf4;" @endif>
                                <td style="padding: 10px; border: 1px solid #ddd;">{{ $data_hora }}</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <strong class="{{ $status_class }}" title="{{ $reg->observacao ?? '' }}">
                                        {{ $status_texto }}
                                    </strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p id='sem-resultados' style='text-align: center; color: #555; margin-top: 15px;'>
                    Nenhum registro de agendamento encontrado para a sua matrícula.
                </p>
            @endif
        </section>
        @endif
        @include('partials.sugestoes-aluno')
    </div>


    @if(!auth()->check() || in_array(auth()->user()->tipo, ['aluno', 'estudante'], true))
        @include('partials.desenvolvedores')
        @include('partials.rodape-aluno')
    @endif

    @include('partials.contato-email')

    <div id="agendarModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>Confirmar Agendamento</h3>
            <p>Data e Hora: <span class="data-hora"></span></p>
            <form id="agendar-form">
                <input type="hidden" name="id_horario" id="id_horario_agendar">
                <button type="submit" class="btn btn-success">Confirmar</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
            </form>
        </div>
    </div>

    <div id="cancelarModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>Cancelar Agendamento</h3>
            <p>Data e Hora: <span class="data-hora"></span></p>
            <form id="cancelar-form">
                <input type="hidden" name="id_horario" id="id_horario_cancelar">
                <div class="form-group">
                    <label for="justificativa">Motivo do Cancelamento (Obrigatório):</label>
                    <textarea id="justificativa" name="justificativa" rows="3" required data-limite-caracteres="2000" data-contador-caracteres="justificativa-contador" aria-describedby="justificativa-contador"></textarea>
                <p id="justificativa-contador" style="font-size:.85rem;margin-top:6px;color:#56675a;">0 / 2.000 caracteres</p>
                </div>
                <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Voltar</button>
            </form>
        </div>
    </div>

<div id="indisponibilidade-aviso-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="indisponibilidade-aviso-titulo" aria-describedby="indisponibilidade-aviso-texto">
    <div class="modal-content">
        <button type="button" class="close-btn" id="indisponibilidade-aviso-fechar" aria-label="Fechar aviso">&times;</button>
        <h3 id="indisponibilidade-aviso-titulo">Aviso da psicóloga</h3>
        <p id="indisponibilidade-aviso-data"></p>
        <p id="indisponibilidade-aviso-texto"></p>
    </div>
</div>
<script>
    const LaravelConfig = {
        isLoggedIn: @json(auth()->check()),
        userTipo: "{{ auth()->check() ? auth()->user()->tipo : '' }}",
        matriculaUsuario: "{{ auth()->check() ? auth()->user()->matricula : '' }}",
        csrfToken: "{{ csrf_token() }}",
        horarios: @json($horarios),
        antecedenciaAgendamento: @json($antecedenciaAgendamento),
        rotas: {
            agendar: "{{ route('agenda.agendar') }}",
            cancelar: "{{ route('agenda.cancelar') }}"
        }
    };
</script>

<script src="{{ asset('js/antecedencia-agendamento.js') }}?v=20261008-1"></script>
<script src="{{ asset('js/aluno.js') }}?v=20261021-indisponibilidade-1"></script>
@include('partials.mensagens-modal')
</body>
</html>
