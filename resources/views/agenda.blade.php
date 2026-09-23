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
            <button type="button" 
        onclick="abrirModalBuscaProntuario(); return false;" 
        class="btn btn-info text-white font-semibold px-4 py-2 rounded-lg shadow">
    Prontuários e Acompanhamento
</button>
        </div>
        
        <section class="content-section agenda-hoje-section">
            <div class="agenda-hoje-header">
                <h2>Agenda de Hoje - {{ \Carbon\Carbon::today()->format('d/m/Y') }}</h2>
                <span class="agenda-hoje-badge">
                    {{ $agendamentosHoje->count() }} {{ $agendamentosHoje->count() === 1 ? 'atendimento' : 'atendimentos' }}
                </span>
            </div>

            @if($agendamentosHoje->isEmpty())
                <div class="agenda-hoje-vazia">
                    <p>Nenhum agendamento marcado para o dia de hoje.</p>
                </div>
            @else
                <div class="table-responsive-wrapper">
                    <table class="table-agenda-hoje">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Aluno</th>
                                <th>Matrícula</th>
                                <th>Status Interno</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agendamentosHoje as $agendamento)
                                @php
                                    $isPast = \Carbon\Carbon::parse($agendamento->data . ' ' . $agendamento->hora)->isPast();
                                @endphp
                                <tr>
                                    <td class="col-hora">
                                        {{ \Carbon\Carbon::parse($agendamento->hora)->format('H:i') }}h
                                    </td>
                                    <td class="col-aluno">
                                        {{ $agendamento->nome ?? 'Não informado' }}
                                    </td>
                                    <td class="col-matricula">
                                        {{ $agendamento->matricula ?? 'N/A' }}
                                    </td>
                                    <td>
                                        @if($agendamento->confirmado)
                                            <span class="status-confirmado">Confirmado</span>
                                        @else
                                            <span class="status-agendado">Agendado</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" 
                                                class="btn-acoes-hoje"
                                                data-id="{{ $agendamento->id }}"
                                                data-alunoid="{{ $agendamento->aluno_id ?? optional($agendamento->aluno)->id ?? $agendamento->user_id ?? '' }}"
                                                data-disponivel="{{ $agendamento->disponivel }}"
                                                data-confirmado="{{ $agendamento->confirmado }}"
                                                data-nome="{{ $agendamento->nome ?? optional($agendamento->aluno)->name ?? 'Não informado' }}"
                                                data-matricula="{{ $agendamento->matricula ?? optional($agendamento->aluno)->matricula ?? 'N/A' }}"
                                                data-ispast="0"
                                                onclick="abrirAcoesHoje(this)">
                                            Operar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="content-section">
            <h2>Últimos Cancelamentos</h2>
            @if($ultimosCancelamentos->isEmpty())
                <p style="text-align: center;">Nenhum cancelamento recente.</p>
            @else
                <div style="display: flex; gap: 30px; justify-content: space-between; flex-wrap: wrap;">
                    
                    @foreach ($ultimosCancelamentos->chunk(5) as $bloco)
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
                                        ({{ $cancelamento->matricula_aluno ?? 'N/A' }})<br>
                                        
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
                        <label for="aluno_nome">Nome do Aluno:</label>
        <input type="text" id="aluno_nome" name="aluno_nome" placeholder="Digite o nome...">
    </div>
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
                
                <div id="action-buttons" class="action-buttons-group">
    <input type="hidden" id="eventId">
    
    <a id="prontuarioBtn" href="#" target="_blank" class="btn btn-info" style="display:none; background-color: #0284c7; border-color: #0284c7; color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-weight: bold;">
        Acessar Prontuário
    </a>

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
        
        <!-- Alerta sobre agendamentos existentes -->
        <div class="alert alert-warning" style="background-color: #fff3cd; border: 1px solid #ffeba2; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px;">
            <strong>Atenção:</strong> Caso algum dos horários no período selecionado já esteja <strong>agendado por um aluno</strong>, o ideal é realizar o <strong>cancelamento</strong> (individual ou em bloco) para que o discente seja notificado por e-mail.
        </div>

        <form id="delete-form">
            <div class="form-row">
                <div class="form-group"><label for="data_inicio_apagar">Apagar de:</label><input type="date" id="data_inicio_apagar" required></div>
                <div class="form-group"><label for="data_fim_apagar">Até:</label><input type="date" id="data_fim_apagar" required></div>
            </div>
            
            <div class="form-group checkbox-group">
                <p><strong>Selecionar horários a serem apagados:</strong></p>
                
                <!-- Opção Selecionar Todos -->
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #00833D;">
                    <input type="checkbox" id="selecionarTodosHoras" onchange="toggleTodosHorarios(this)"> 
                    [ Selecionar Todos os Horários ]
                </label>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 8px 0;">

                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="09:00:00"> 09:00</label>
                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="10:00:00"> 10:00</label>
                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="11:00:00"> 11:00</label>
                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="14:00:00"> 14:00</label>
                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="15:00:00"> 15:00</label>
                <label><input type="checkbox" class="hora-checkbox" name="horas_apagar[]" value="16:00:00"> 16:00</label>
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

    <!-- MODAL DE BUSCA DE PRONTUÁRIO COM FILTRO POR DIGITAÇÃO -->
    <div id="modalBuscaProntuario" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 99999; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 100%; max-width: 420px; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); font-family: sans-serif; margin: 20px;">
            
            <div style="background-color: #f3f4f6; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; font-weight: bold; color: #111827;">Buscar Prontuário</h3>
                <button type="button" onclick="fecharModalBuscaProntuario()" style="background: transparent; border: none; color: #374151; font-size: 22px; cursor: pointer; font-weight: bold;">&times;</button>
            </div>

            <form onsubmit="redirecionarParaProntuario(event)" style="padding: 24px;">
                
                <!-- CAMPO PARA DIGITAR O NOME/MATRÍCULA DO ALUNO -->
                <div style="margin-bottom: 12px;">
                    <label for="inputFiltroAluno" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        Pesquisar Aluno por Nome:
                    </label>
                    <input type="text" id="inputFiltroAluno" onkeyup="filtrarListaAlunosProntuario()" placeholder="Digite o nome para filtrar..." style="width: 100%; padding: 9px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label for="selectAlunoProntuario" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        Selecione o Aluno
                    </label>
                    
                    <select id="selectAlunoProntuario" class="form-select" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;" required size="5">
                        <option value="" disabled selected>Escolha um aluno na lista abaixo...</option>
                        
                        @if(isset($alunos) && $alunos->count() > 0)
                            @foreach($alunos as $aluno)
                                <option value="{{ $aluno->id }}" data-search="{{ strtolower($aluno->nome . ' ' . $aluno->matricula) }}">
                                    {{ $aluno->nome }} ({{ $aluno->matricula ?? 'N/A' }})
                                </option>
                            @endforeach
                        @else
                            <option value="" disabled>Nenhum aluno encontrado no sistema</option>
                        @endif
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" onclick="fecharModalBuscaProntuario()" style="padding: 10px 18px; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; color: #374151; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 10px 20px; background-color: #00833D; border: none; border-radius: 8px; color: #ffffff; font-weight: 600; cursor: pointer;">
                        Abrir Prontuário
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL DE SENHA DE ACESSO -->
    <div id="modalSenhaProntuario" style="display: {{ session('pedir_senha') || $errors->has('senha') ? 'flex' : 'none' }}; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 100000; align-items: center; justify-content: center;">
        <div style="background: #fff; width: 100%; max-width: 400px; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); font-family: sans-serif; margin: 20px;">
            
            <div style="background-color: #f3f4f6; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; font-weight: bold; color: #111827;">Acesso Restrito ao Prontuário</h3>
                <button type="button" onclick="fecharModalSenhaProntuario()" style="background: transparent; border: none; color: #374151; font-size: 22px; cursor: pointer; font-weight: bold;">&times;</button>
            </div>

            <form action="{{ route('prontuarios.validar-senha') }}" method="POST" style="padding: 24px;">
                @csrf
                <p style="font-size: 13px; color: #4b5563; margin-top: 0; margin-bottom: 16px;">
                    Por razões de segurança, digite a sua senha de acesso para visualizar o prontuário.
                </p>

                <div style="margin-bottom: 16px;">
                    <label for="senhaProntuarioInput" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        Sua Senha
                    </label>
                    <input type="password" id="senhaProntuarioInput" name="senha" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;" placeholder="••••••••" required autofocus>
                    
                    @error('senha')
                        <span style="color: #ef4444; font-size: 12px; margin-top: 6px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" onclick="fecharModalSenhaProntuario()" style="padding: 10px 18px; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; color: #374151; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" style="padding: 10px 20px; background-color: #00833D; border: none; border-radius: 8px; color: #ffffff; font-weight: 600; cursor: pointer;">
                        Confirmar Senha
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- FUNÇÕES JAVASCRIPT CORRIGIDAS -->
    <script>
    function abrirModalBuscaProntuario() {
        var modal = document.getElementById('modalBuscaProntuario');
        if (modal) {
            modal.style.display = 'flex';
            var input = document.getElementById('inputFiltroAluno');
            if (input) {
                input.value = '';
                input.focus();
                filtrarListaAlunosProntuario();
            }
        }
    }

    function fecharModalBuscaProntuario() {
        var modal = document.getElementById('modalBuscaProntuario');
        if (modal) {
            modal.style.display = 'none';
            var select = document.getElementById('selectAlunoProntuario');
            if (select) select.value = '';
        }
    }

    function fecharModalSenhaProntuario() {
        var modal = document.getElementById('modalSenhaProntuario');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // FUNÇÃO QUE FILTRA OS ALUNOS ENQUANTO VOCÊ DIGITA
    function filtrarListaAlunosProntuario() {
        const termo = document.getElementById('inputFiltroAluno').value.toLowerCase().trim();
        const select = document.getElementById('selectAlunoProntuario');
        const options = select.getElementsByTagName('option');

        for (let i = 0; i < options.length; i++) {
            // Ignora o placeholder inicial
            if (options[i].disabled && options[i].value === "") continue;

            const textoBusca = options[i].getAttribute('data-search') || options[i].text.toLowerCase();
            
            if (textoBusca.includes(termo)) {
                options[i].style.display = "";
            } else {
                options[i].style.display = "none";
            }
        }
    }

    function redirecionarParaProntuario(e) {
        if (e) e.preventDefault();
        
        const select = document.getElementById('selectAlunoProntuario');
        const alunoId = select ? select.value : null;
        
        if (alunoId) {
            window.location.href = '/prontuarios/aluno/' + alunoId;
        } else {
            alert('Por favor, selecione um aluno na lista.');
        }
    }
    </script>

    <script>
    function toggleTodosHorarios(masterCheckbox) {
        const checkboxes = document.querySelectorAll('.hora-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = masterCheckbox.checked;
        });
    }

    // Opcional: Desmarca o "Selecionar Todos" se alguma hora individual for desmarcada
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('hora-checkbox')) {
            const master = document.getElementById('selecionarTodosHoras');
            const total = document.querySelectorAll('.hora-checkbox').length;
            const marcados = document.querySelectorAll('.hora-checkbox:checked').length;
            
            if (master) {
                master.checked = (total === marcados);
            }
        }
    });
</script>

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