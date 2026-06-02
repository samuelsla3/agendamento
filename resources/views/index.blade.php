<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendário de Agendamentos</title>
    
    <link rel="stylesheet" href="{{ asset('css/style_main.css') }}?v={{ time() }}">
    
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
</head>
<body>

    <header class="header">
        <h1>Agendamento de Consulta</h1>
        <div class="user-info">
            @if(auth()->check())
                <span>{{ auth()->user()->nome }} ({{ ucfirst(auth()->user()->tipo) }})</span>
                
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger">Sair</button>
                </form>
            @else
                <button class="btn btn-primary" onclick="window.location.href='{{ route('login') }}';">Fazer Login</button>
                <button class="btn btn-secondary" onclick="window.location.href='{{ route('register') }}';">Criar Conta</button>
            @endif
        </div>
    </header>

    <div class="container">
        <div id="message-box" class="message-box"></div>
        
        <section class="content-section">
            <h2>Calendário de Horários Disponíveis</h2>
            <p class="instrucao-texto">Clique em um horário **Disponível** para tentar agendar. É necessário estar logado.</p>
            <div id="calendar"></div>
        </section>

        @if(auth()->check() && (auth()->user()->tipo === 'estudante' || auth()->user()->tipo === 'aluno'))
        <section class="content-section">
            <h2>Seu Histórico de Agendamentos</h2>
            <p class="instrucao-subtexto">(Atendimentos Realizados e Cancelados)</p>

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
                                $data_hora = \Carbon\Carbon::parse($reg->data_atendimento)->format('d/m/Y') . ' às ' . \Carbon\Carbon::parse($reg->hora_atendimento)->format('H:i');
                                
                                if ($reg->status === 'Agendado') {
                                    $status_texto = 'Agendado / Reservado';
                                    $status_class = 'text-success';
                                } elseif ($reg->status === 'Realizado') {
                                    $status_texto = 'Atendimento Realizado';
                                    $status_class = 'text-success';
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
    </div>

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
                    <textarea id="justificativa" name="justificativa" rows="3" required></textarea>
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
        rotas: {
            agendar: "{{ route('agenda.agendar') }}",
            cancelar: "{{ route('agenda.cancelar') }}"
        }
    };
</script>

<script src="{{ asset('js/aluno.js') }}?v={{ time() }}"></script>
</body>
</html>