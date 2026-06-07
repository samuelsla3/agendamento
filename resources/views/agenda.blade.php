<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda da Psicóloga</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
</head>
<body>

    <header class="header">
        <h1>Painel da Psicóloga</h1>
        <div class="user-info">
            <span>{{ session('usuario_nome') }} ({{ ucfirst(auth()->user()->tipo) }})</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger">Sair</button>
            </form>
        </div>
    </header>

    <div class="container">
        <div class="action-buttons-group">
            <button type="button" class="btn btn-primary" onclick="$('#generateModal').addClass('is-visible')">Gerar Horário(s)</button>
            <button type="button" class="btn btn-danger" onclick="$('#deleteModal').addClass('is-visible')">Apagar Horários Futuros</button>
        </div>
        
        <section class="content-section">
            <h2>Últimos Cancelamentos</h2>
            @if($ultimosCancelamentos->isEmpty())
                <p style="text-align: center;">Nenhum cancelamento recente.</p>
            @else
                <div style="display: flex; gap: 30px; justify-content: space-between; flex-wrap: wrap;">
                    
                    @foreach ($ultimosCancelamentos->chunk(5) as $bloco)
                        {{-- Cada coluna ocupa quase metade da largura (48%) e alinha à esquerda --}}
                        <div style="flex: 1; min-width: 300px; max-width: 48%; text-align: left;">
                            <ul class="cancelamentos-lista">
                                @foreach ($bloco as $cancelamento)
                                    @php
                                        $data_atendimento = \Carbon\Carbon::parse($cancelamento->data_atendimento)->format('d/m/Y');
                                        $hora_atendimento = \Carbon\Carbon::parse($cancelamento->hora_atendimento)->format('H:i');
                                        $momento_cancelamento = \Carbon\Carbon::parse($cancelamento->data_registro)->format('d/m/Y \à\s H:i');
                                    @endphp
                                    
                                    <li style="margin-bottom: 15px;">
                                        <strong>Aluno:</strong> {{ $cancelamento->nome_aluno ?? 'Não informado' }} 
                                        (Mat: {{ $cancelamento->matricula_aluno ?? 'N/A' }})<br>
                                        
                                        <strong>Horário Cancelado:</strong> Dia {{ $data_atendimento }} às {{ $hora_atendimento }}h<br>
                                        
                                        <strong>Cancelado em:</strong> {{ $momento_cancelamento }}<br>
                                        
                                        <strong>Justificativa:</strong> "{{ $cancelamento->observacao ?? 'Sem justificativa.' }}"
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                </div>
            @endif
        </section>

        <section class="content-section">
            <h2>Agenda de Horários</h2>
            <div id='calendar'></div>
        </section>
        
        <section id="relatorio-container" class="content-section">
            <h2>Relatório de Histórico de Alunos</h2>
            <form id="filtro-relatorio-form">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="aluno_matricula">Matrícula do Aluno:</label>
                        <input type="text" id="aluno_matricula" name="aluno_matricula">
                    </div>
                    <div class="form-group">
                        <label for="data_inicio">Período de:</label>
                        <input type="date" id="data_inicio" name="data_inicio">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Até:</label>
                        <input type="date" id="data_fim" name="data_fim">
                    </div>
                    <div class="form-group">
                    <label for="ordenar_por">Ordenar por:</label>
                    <select id="ordenar_por" name="ordenar_por">
                        <option value="data_asc">Data e Hora (Mais antigos primeiro)</option>
                        <option value="data_desc" selected>Data e Hora (Mais recentes primeiro)</option>
                        <option value="nome_asc">Nome do Aluno (A-Z)</option>
                        <option value="situacao_asc">Situação (Confirmados primeiro)</option>
                    </select>
                </div>
                </div>
                <button type="submit" class="btn btn-primary">Gerar Relatório</button>
                <button type="button" id="exportar-pdf-btn" class="btn btn-success">Exportar para PDF</button>
            </form>
            <div id="resultado_relatorio" style="margin-top: 20px;"></div>
        </section> 

        <div id="modal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <h3 id="modalTitle"></h3>
                
                <div id="justificativaInfo" style="display:none;" class="cancelamentos-lista">
                    <li>
                        <strong>Justificativa do cancelamento:</strong>
                        <p id="justificativaTexto" style="margin: 5px 0 0 0;"></p>
                    </li>
                </div>
                
                <div id="agendadoInfo" style="display:none; text-align: left; margin-bottom: 20px;">
                    <p><strong>Agendado por:</strong> <span id="agendadoNome"></span></p>
                    <p><strong>Matrícula:</strong> <span id="agendadoMatricula"></span></p>
                    <p><strong>Status:</strong> <span id="agendadoStatus"></span></p>
                </div>
                
                <form id="form-disponivel" style="display:none;">
                    <div class="form-group"><label for="data">Data:</label><input type="date" id="data" name="data" required></div>
                    <div class="form-group"><label for="hora">Hora:</label><input type="time" id="hora" name="hora" required></div>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
                
                <div id="action-buttons" style="margin-top: 20px;">
                    <input type="hidden" id="eventId">
                    <button type="button" id="confirmBtn" class="btn btn-success" style="display:none;">Confirmar Atendimento</button>
                    <button type="button" id="cancelByPsicologaBtn" class="btn btn-danger" style="display:none;">Cancelar Agendamento</button>
                    <button type="button" id="deleteBtn" class="btn btn-danger">Excluir Horário</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Fechar</button>
                </div>
            </div>
        </div>

        <div id="generateModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        
        <div class="modal-tabs" style="display: flex; margin-bottom: 20px; border-bottom: 2px solid #e9ecef;">
            <button type="button" class="tab-btn active" onclick="switchTab('bloco')" 
            style="flex: 1; padding: 10px; border: none; background: none; font-weight: bold; 
            border-bottom: 3px solid #00833D; color: #00833D; cursor: pointer;">Gerar em Bloco</button>
            <button type="button" class="tab-btn" onclick="switchTab('individual')" 
            style="flex: 1; padding: 10px; border: none; background: none; font-weight: bold; 
            color: #6c757d; cursor: pointer;">Horário Individual</button>
        </div>

        <form id="generate-form" class="tab-content">
            <div class="form-row">
                <div class="form-group"><label for="data_inicio_gerar">Período de:</label><input type="date" id="data_inicio_gerar" required></div>
                <div class="form-group"><label for="data_fim_gerar">Até:</label><input type="date" id="data_fim_gerar" required></div>
            </div>
            
            <div class="form-group checkbox-group">
                <p><strong>Selecionar dias da semana:</strong></p>
                <label><input type="checkbox" name="dias_semana[]" value="1"> Seg</label>
                <label><input type="checkbox" name="dias_semana[]" value="2" checked> Ter</label>
                <label><input type="checkbox" name="dias_semana[]" value="3" checked> Qua</label>
                <label><input type="checkbox" name="dias_semana[]" value="4" checked> Qui</label>
                <label><input type="checkbox" name="dias_semana[]" value="5" checked> Sex</label>
                <label><input type="checkbox" name="dias_semana[]" value="6"> Sáb</label>
                <label><input type="checkbox" name="dias_semana[]" value="7"> Dom</label>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label><strong>Selecionar Horários de Atendimento:</strong></label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 5px;">
                    <label><input type="checkbox" name="horas_selecionadas[]" value="09:00:00" checked> 09:00</label>
                    <label><input type="checkbox" name="horas_selecionadas[]" value="10:00:00" checked> 10:00</label>
                    <label><input type="checkbox" name="horas_selecionadas[]" value="11:00:00" checked> 11:00</label>
                    <label><input type="checkbox" name="horas_selecionadas[]" value="14:00:00" checked> 14:00</label>
                    <label><input type="checkbox" name="horas_selecionadas[]" value="15:00:00" checked> 15:00</label>
                    <label><input type="checkbox" name="horas_selecionadas[]" value="16:00:00" checked> 16:00</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Gerar Bloco de Horários</button>
        </form>

        <form id="individual-form" class="tab-content" style="display: none;">
            <p style="color: #6c757d; font-size: 0.9rem; margin-bottom: 15px;">Crie um único horário avulso para um dia específico na agenda.</p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_individual">Data do Atendimento:</label>
                    <input type="date" id="data_individual" required disabled>
                </div>
                <div class="form-group">
                    <label for="hora_individual">Horário:</label>
                    <input type="time" id="hora_individual" required disabled>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success" 
            style="margin-top: 20px; width: 100%; background-color: #00833D; border-color: #00833D;">Criar Horário Único</button>
        </form>
    </div>
</div>
        
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <h3>Apagar Horários Disponíveis</h3>
                <form id="delete-form">
                    <div class="form-row">
                        <div class="form-group"><label for="data_inicio_apagar">Apagar de:</label><input type="date" id="data_inicio_apagar" required></div>
                        <div class="form-group"><label for="data_fim_apagar">Até:</label><input type="date" id="data_fim_apagar" required></div>
                    </div>
                    <div class="form-group checkbox-group">
                        <p><strong>Selecionar horários a serem apagados:</strong></p>
                        <label><input type="checkbox" name="horas_apagar[]" value="09:00:00"> 09:00</label>
                        <label><input type="checkbox" name="horas_apagar[]" value="10:00:00"> 10:00</label>
                        <label><input type="checkbox" name="horas_apagar[]" value="11:00:00"> 11:00</label>
                        <label><input type="checkbox" name="horas_apagar[]" value="14:00:00"> 14:00</label>
                        <label><input type="checkbox" name="horas_apagar[]" value="15:00:00"> 15:00</label>
                        <label><input type="checkbox" name="horas_apagar[]" value="16:00:00"> 16:00</label>
                    </div>
                    <button type="submit" class="btn btn-danger">Apagar Horários Selecionados</button>
                </form>
            </div>
        </div>
        
        <div id="cancelByPsicologaModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <h3>Justificar Cancelamento</h3>
                <p>Você está cancelando o agendamento de <strong id="cancelAlunoNome"></strong>.</p>
                <form id="cancel-by-psicologa-form">
                    <div class="form-group">
                        <label for="justificativa_psicologa">Motivo do Cancelamento (Obrigatório):</label>
                        <textarea id="justificativa_psicologa" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelamento e Notificar Aluno</button>
                </form>
            </div>
        </div>
    </div>

<script>
    const LaravelConfig = {
        csrfToken: "{{ csrf_token() }}",
        rotas: {
            eventos: "{{ route('agenda.eventos') }}",
            acao: "{{ route('agenda.acao') }}",
            relatorio: "/agenda/relatorio"
        }
    };
</script>

<script src="{{ asset('js/psicologa.js') }}?v={{ time() }}"></script>

</body>
</html>