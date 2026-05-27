$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': LaravelConfig.csrfToken
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
        displayEventTime: false,
        events: LaravelConfig.horarios,
        eventClick: function(info) { openModal(info.event); },
        eventDataTransform: function(eventData) {
            const props = eventData.extendedProps;
            let className = '';
            let statusTexto = '';
            
            let horarioOriginal = eventData.title || ''; 

            if (props.disponivel == 1) {
                className = 'evento-disponivel-aluno';
                statusTexto = 'Disponível';
            } else if ((LaravelConfig.userTipo === 'estudante' || LaravelConfig.userTipo === 'aluno') && props.matricula_agendada === LaravelConfig.matriculaUsuario) {
                className = 'evento-meu-agendamento';
                statusTexto = 'Meu Agendamento';
            } else {
                className = 'evento-indisponivel-aluno';
                statusTexto = LaravelConfig.userTipo === 'psicologa' ? 'Agendado' : 'Indisponível';
            }

            let tituloFinal = horarioOriginal + ' - ' + statusTexto;

            return { 
                id: eventData.id, 
                title: tituloFinal, 
                start: eventData.start, 
                classNames: [className], 
                extendedProps: props 
            };
        }
    });
    calendar.render();
    window.refreshCalendar = function() { location.reload(); }
});

function openModal(event) {
    const props = event.extendedProps;
    const dataHora = new Date(event.start).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short'});

    if (!LaravelConfig.isLoggedIn) {
        showMessage('error', "Você precisa fazer login para gerenciar os horários.");
        return;
    }

    if (LaravelConfig.userTipo === 'psicologa') {
        showMessage('success', "Horário selecionado: " + dataHora);
        return;
    }

    if (props.disponivel === 1) {
        $('#agendarModal').find('.data-hora').text(dataHora);
        $('#id_horario_agendar').val(event.id);
        $('#agendarModal').addClass('is-visible');
    } else if (props.matricula_agendada === LaravelConfig.matriculaUsuario) {
        $('#cancelarModal').find('.data-hora').text(dataHora);
        $('#id_horario_cancelar').val(event.id);
        $('#cancelarModal').addClass('is-visible');
    } else {
        showMessage('error', "Este horário não está disponível para você.");
    }
}

function closeModal() { $('.modal').removeClass('is-visible'); }
$('.modal').on('click', function(e) { if ($(e.target).is(this)) { closeModal(); } });
function showMessage(type, message) { $('#message-box').removeClass('success error').addClass(type).text(message).fadeIn().delay(4000).fadeOut(); }

$('#agendar-form').on('submit', function(e) { 
    e.preventDefault(); 
    sendAjaxRequest(LaravelConfig.rotas.agendar, { 
        action: 'agendar', 
        id_horario: $('#id_horario_agendar').val() 
    }); 
});

$('#cancelar-form').on('submit', function(e) { 
    e.preventDefault(); 
    sendAjaxRequest(LaravelConfig.rotas.cancelar, { 
        action: 'cancelar', 
        id_horario: $('#id_horario_cancelar').val(), 
        justificativa: $('#justificativa').val() 
    }); 
});

function sendAjaxRequest(urlAlvo, data) {
    $.ajax({
        url: urlAlvo, 
        type: 'POST', 
        contentType: 'application/json', 
        data: JSON.stringify(data),
        success: function(response) {
            showMessage(response.status, response.message);
            if (response.status === 'success') { setTimeout(() => window.refreshCalendar(), 1500); }
        },
        error: function() { showMessage('error', 'Erro de comunicação. Tente novamente.'); }
    });
}