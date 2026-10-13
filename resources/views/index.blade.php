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
    <script src="{{ asset('js/sugestoes.js') }}?v=20261012-2" defer></script>
<script src="{{ asset('js/operacoes.js') }}?v=20260924-1"></script>
    <link rel="stylesheet" href="{{ asset('css/mensagens.css') }}?v=20261013-imagens-1">
    <script src="{{ asset('js/mensagens.js') }}?v=20261013-imagens-1" defer></script>
    <script src="{{ asset('js/limites-texto.js') }}?v=20261013-1" defer></script>
</head>
<body>

    <header class="header">
        <h1>Agendamento de Atendimento Psicológico</h1>
        <div class="user-info">
            @if(auth()->check())
                <span>{{ auth()->user()->nome }} ({{ auth()->user()->matricula }})</span>
                @if(($contatoEmail ?? null) !== null)
                    <button type="button" class="contato-email-link" data-abrir-contato-email aria-controls="contato-email-modal" aria-expanded="false">Alterar e-mail</button>
                @endif
                
                @include('partials.mensagens-botao')
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
            @include('partials.operacao')
                    <button type="submit" class="btn btn-danger">Sair</button>
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
<script src="{{ asset('js/aluno.js') }}?v=20261008-faltas-1"></script>
@include('partials.mensagens-modal')
</body>
</html>
