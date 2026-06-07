document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        displayEventTime: false,
        events: {
            url: LaravelConfig.rotas.eventos,
            failure: function() { alert('Houve um erro ao carregar os eventos!'); }
        },
        eventClick: function(info) { openModal(info.event); },
        eventDataTransform: function(rawEventData) {
            const props = rawEventData.extendedProps;
            let className = 'evento-disponivel-psicologa';
            let statusTexto = 'Disponível';
            
            let horarioOriginal = '';
            if (rawEventData.start) {
                const partesHora = rawEventData.start.split('T')[1]; 
                if (partesHora) {
                    const [horas, minutos] = partesHora.split(':');
                    horarioOriginal = minutos === '00' ? `${horas}h` : `${horas}h${minutos}`;
                }
            }

            if (props.disponivel == 0) {
                className = 'evento-indisponivel-psicologa'; 
                statusTexto = 'Agendado: ' + (props.nome || 'N/A');
            } else if (props.justificativa_cancelamento) {
                className = 'evento-indisponivel-psicologa'; 
                statusTexto = 'Cancelado por: ' + (props.nome || 'N/A');
            }

            let tituloFinal = (horarioOriginal ? horarioOriginal + ' - ' : '') + statusTexto;

            return { 
                ...rawEventData, 
                classNames: [className], 
                title: tituloFinal 
            };
        }
    });
    calendar.render();
    window.refreshCalendar = function() { calendar.refetchEvents(); }
});

function openModal(event) {
    $('#eventId').val(event.id);
    const props = event.extendedProps;
    const isPast = new Date(event.start) < new Date();
    
    $('#justificativaTexto, #agendadoNome, #agendadoMatricula, #agendadoStatus').text('');
    $('#justificativaInfo, #agendadoInfo, #form-disponivel, #confirmBtn, #cancelByPsicologaBtn, #deleteBtn').hide();
    
    const isCanceladoReal = props.justificativa_cancelamento && props.disponivel == 1;

    if (isCanceladoReal) {
        $('#justificativaTexto').text(props.justificativa_cancelamento);
        $('#justificativaInfo').show();
    }

    if (props.disponivel === 1 && !isCanceladoReal) {
        $('#modalTitle').text('Editar Horário Disponível');
        $('#form-disponivel').show();
        const start = new Date(event.start);
        $('#data').val(start.toISOString().slice(0, 10));
        $('#hora').val(start.toTimeString().slice(0, 5));
        
        if (!isPast) {
            $('#deleteBtn').show();
        }
    } else {
        $('#agendadoInfo').show();
        
        $('#agendadoNome').text(props.nome || props.aluno_nome || 'N/A');
        $('#agendadoMatricula').text(props.matricula || props.aluno_matricula || 'N/A');
        
        if (isCanceladoReal) {
            $('#modalTitle').text('Agendamento Cancelado');
            
            const statusTexto = props.status_real || 'Cancelado';
            $('#agendadoStatus').text(statusTexto).css('color', 'var(--danger-color)');
            
            if (!isPast) {
                $('#deleteBtn').show();
            }
            
        } else {
            $('#modalTitle').text('Detalhes do Agendamento');
            $('#agendadoStatus').text('Agendado').css('color', '#d97706');
            
            if (isPast) { 
                $('#confirmBtn').show(); 
                $('#cancelByPsicologaBtn').hide();
                $('#deleteBtn').hide();
            } else {
                $('#cancelByPsicologaBtn').show();
                $('#deleteBtn').show();
            }
        }
    }
    $('#modal').addClass('is-visible');
}

function closeModal() { 
    $('#modal, #generateModal, #deleteModal, #cancelByPsicologaModal').removeClass('is-visible'); 
}

$(document).on('click', '.close-btn, .btn-secondary', function(e) {
    e.preventDefault();
    closeModal();
});

$('.modal').on('click', function(e) { 
    if ($(e.target).is(this)) { 
        closeModal(); 
    } 
});

function sendAjaxRequest(data) {
    $.ajax({
        url: LaravelConfig.rotas.acao, 
        type: 'POST', 
        headers: {
            'X-CSRF-TOKEN': LaravelConfig.csrfToken 
        },
        data: data, 
        success: function(response) {
            alert(response.message);
            if (response.status === 'success') {
                window.refreshCalendar();
                closeModal();
                if (data.action === 'confirmar' || data.action === 'cancel_by_psicologa') { 
                    location.reload(); 
                }
            }
        },
        error: function(xhr) { 
            console.error("Erro detalhado:", xhr.responseText);
            alert('Erro de comunicação com o servidor.'); 
        }
    });
}

$('#generate-form').submit(function(e) { e.preventDefault(); const diasSelecionados = []; $('input[name="dias_semana[]"]:checked').each(function() { diasSelecionados.push($(this).val()); }); sendAjaxRequest({ action: 'generate_default', data_inicio: $('#data_inicio_gerar').val(), data_fim: $('#data_fim_gerar').val(), dias_semana: diasSelecionados }); });
$('#delete-form').submit(function(e) { e.preventDefault(); const horasSelecionadas = []; $('input[name="horas_apagar[]"]:checked').each(function() { horasSelecionadas.push($(this).val()); }); sendAjaxRequest({ action: 'delete_specific_default', data_inicio: $('#data_inicio_apagar').val(), data_fim: $('#data_fim_apagar').val(), horas: horasSelecionadas }); });
$('#form-disponivel').submit(function(e) { e.preventDefault(); sendAjaxRequest({ action: 'edit', id: $('#eventId').val(), data: $('#data').val(), hora: $('#hora').val() }); });
$('#confirmBtn').click(function() { sendAjaxRequest({ action: 'confirmar', id: $('#eventId').val() }); });
$('#deleteBtn').click(function() { if (confirm('Tem certeza que deseja excluir este horário?')) { sendAjaxRequest({ action: 'delete', id: $('#eventId').val() }); } });

$('#cancelByPsicologaBtn').click(function() {
    $('#cancelAlunoNome').text($('#agendadoNome').text());
    $('#cancelByPsicologaModal').addClass('is-visible');
});

$('#cancel-by-psicologa-form').submit(function(e) {
    e.preventDefault();
    sendAjaxRequest({ action: 'cancel_by_psicologa', id: $('#eventId').val(), justificativa: $('#justificativa_psicologa').val() });
});

$('#filtro-relatorio-form').on('submit', function(e) {
    e.preventDefault();
    $('#resultado_relatorio').html('<p>Carregando relatório...</p>');
    $.ajax({
        url: LaravelConfig.rotas.relatorio,
        method: 'POST',
        data: {
            _token: LaravelConfig.csrfToken,
            aluno_matricula: $('#aluno_matricula').val(),
            data_inicio: $('#data_inicio').val(),
            data_fim: $('#data_fim').val(),
            ordenar_por: $('#ordenar_por').val()
        },
        success: function(response) {
            $('#resultado_relatorio').html(response);
        },
        error: function(xhr) {
            console.error("Erro retornado pelo servidor:", xhr.responseText);
            $('#resultado_relatorio').html('<span style="color: var(--danger-color);">Erro ao processar relatório.</span>');
        }
    });
});

$('#exportar-pdf-btn').on('click', function() {
    const { jsPDF } = window.jspdf; 
    const doc = new jsPDF(); 
    const tabela = document.getElementById('tabela-relatorio'); 
    const semResultados = document.getElementById('sem-resultados'); 
    if (!tabela && !semResultados) { alert('Gere um relatório primeiro!'); return; } 
    const totalPagesExp = '{total_pages_count_string}'; 
    const addHeaderAndFooter = (data) => { doc.setFontSize(18); doc.setTextColor(40); doc.setFont('helvetica', 'bold'); doc.text('Relatório de Histórico', doc.internal.pageSize.getWidth() / 2, 22, { align: 'center' }); doc.setFontSize(11); doc.setTextColor(100); doc.setFont('helvetica', 'normal'); doc.text(`Emitido em: ${new Date().toLocaleDateString('pt-BR')}`, doc.internal.pageSize.getWidth() - 14, 22, { align: 'right' }); let str = `Página ${data.pageNumber}`; if (typeof doc.putTotalPages === 'function') { str += ` de ${totalPagesExp}`; } doc.setFontSize(10); doc.text(str, data.settings.margin.left, doc.internal.pageSize.getHeight() - 10); }; 
    if (tabela) { 
        doc.autoTable({ html: '#tabela-relatorio', startY: 30, theme: 'grid', headStyles: { fillColor: [0, 131, 61], textColor: [255, 255, 255], fontStyle: 'bold' }, alternateRowStyles: { fillColor: [240, 240, 240] }, didDrawPage: addHeaderAndFooter, margin: { top: 30 } }); 
    } else if (semResultados) { 
        addHeaderAndFooter({ pageNumber: 1, settings: { margin: { left: 14 } } }); 
        const texto = semResultados.innerText.replace(/\s+/g, ' ').trim(); 
        doc.text(doc.splitTextToSize(texto, 180), 14, 45); 
    } 
    if (typeof doc.putTotalPages === 'function') { doc.putTotalPages(totalPagesExp); } 
    doc.save('relatorio_historico_alunos.pdf'); 
});

function switchTab(type) {
    const blocoForm = document.getElementById('generate-form');
    const individualForm = document.getElementById('individual-form');
    const buttons = document.querySelectorAll('.tab-btn');

    buttons.forEach(btn => {
        btn.style.color = '#6c757d';
        btn.style.borderBottom = 'none';
    });

    if (type === 'bloco') {
        buttons[0].style.color = '#00833D';
        buttons[0].style.borderBottom = '3px solid #00833D';
        
        blocoForm.style.display = 'block';
        individualForm.style.display = 'none';

        toggleInputs(blocoForm, true);
        toggleInputs(individualForm, false);
    } else {
        buttons[1].style.color = '#00833D';
        buttons[1].style.borderBottom = '3px solid #00833D';
        
        blocoForm.style.display = 'none';
        individualForm.style.display = 'block';

        toggleInputs(blocoForm, false);
        toggleInputs(individualForm, true);
    }
}

function toggleInputs(form, enable) {
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.disabled = !enable;
    });
}

const originalCloseModal = window.closeModal;
window.closeModal = function() {
    if (typeof originalCloseModal === 'function') originalCloseModal();
    switchTab('bloco');
}

$('#individual-form').submit(function(e) {
    e.preventDefault();
    sendAjaxRequest({
        action: 'generate_individual',
        data_individual: $('#data_individual').val(),
        hora_individual: $('#hora_individual').val()
    });
});

function abrirAcoesHoje(botao) {
    const id = botao.getAttribute('data-id');
    const disponivel = parseInt(botao.getAttribute('data-disponivel'));
    const confirmado = parseInt(botao.getAttribute('data-confirmado'));
    const nome = botao.getAttribute('data-nome');
    const matricula = botao.getAttribute('data-matricula');
    const isPast = botao.getAttribute('data-ispast') === '1';

    $('#eventId').val(id);
    $('#justificativaTexto, #agendadoNome, #agendadoMatricula, #agendadoStatus').text('');
    $('#justificativaInfo, #agendadoInfo, #form-disponivel, #confirmBtn, #cancelByPsicologaBtn, #deleteBtn').hide();

    $('#modalTitle').text('Detalhes do Agendamento');
    $('#agendadoInfo').show();
    
    $('#agendadoNome').text(nome);
    $('#agendadoMatricula').text(matricula);
    $('#agendadoStatus').text('Agendado').css('color', '#d97706');

    if (isPast) { 
        $('#confirmBtn').show(); 
        $('#cancelByPsicologaBtn').hide();
        $('#deleteBtn').hide();
    } else {
        $('#cancelByPsicologaBtn').show();
        $('#deleteBtn').show();
    }

    if (confirmado === 1) {
        $('#confirmBtn').hide();
        $('#agendadoStatus').text('✓ Confirmado').css('color', '#00833D');
    }

    $('#modal').addClass('is-visible');
}