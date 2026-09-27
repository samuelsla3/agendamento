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
        
        // AJUSTE 1: Aplica o cursor de "proibido" (not-allowed) visualmente no calendário
        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            const isPast = new Date(info.event.start) < new Date();
            const ehMeuAgendamento = (LaravelConfig.userTipo === 'estudante' || LaravelConfig.userTipo === 'aluno') && 
                                    (props.matricula_agendada === LaravelConfig.matriculaUsuario || props.matricula === LaravelConfig.matriculaUsuario);

            // Se o horário já passou OU se está ocupado por outro aluno, aplica visual de indisponível
            if (isPast || (props.disponivel != 1 && !ehMeuAgendamento && LaravelConfig.userTipo !== 'psicologa')) {
                info.el.style.cursor = 'not-allowed';
                info.el.style.opacity = '0.6';
            }
        },

        eventDataTransform: function(eventData) {
            const props = eventData.extendedProps;
            let className = '';
            let statusTexto = '';
            
            let horarioOriginal = eventData.title || ''; 

            if (props.disponivel == 1) {
                className = 'evento-disponivel-aluno';
                statusTexto = 'Disponível';
            } else if (props.confirmado == 1) {
                className = 'evento-indisponivel-aluno';
                statusTexto = 'Atendimento Realizado';
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
    const telaPequena = window.matchMedia('(max-width: 767px)');
    const opcoesCelular = {
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek,timeGridDay' },
        buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia', list: 'Lista' },
        noEventsContent: 'Nenhum horário nesta semana. Use as setas para consultar outro período.',
        height: 'auto',
        dayMaxEvents: 3,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false }
    };
    // Guarda os valores originais, incluindo os padrões do FullCalendar.
    const opcoesComputador = Object.fromEntries(
        Object.keys(opcoesCelular).map(chave => [chave, calendar.getOption(chave)])
    );
    function adaptarCalendario() {
        calendar.batchRendering(() => {
            const opcoes = telaPequena.matches ? opcoesCelular : opcoesComputador;
            Object.entries(opcoes).forEach(([chave, valor]) => calendar.setOption(chave, valor));
            calendar.changeView(telaPequena.matches ? 'listWeek' : 'dayGridMonth');
        });
    }
    if (telaPequena.matches) adaptarCalendario();
    calendar.render();
    telaPequena.addEventListener('change', adaptarCalendario);
    window.refreshCalendar = function() { location.reload(); }
});

function openModal(event) {
    const props = event.extendedProps;
    const dataHora = new Date(event.start).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short'});
    
    // Calcula se o horário do agendamento é anterior ao momento atual
    const isPast = new Date(event.start) < new Date();

    if (!LaravelConfig.isLoggedIn) {
        showMessage('error', "É necessário estar logado.");
        return;
    }

    if (LaravelConfig.userTipo === 'psicologa') {
        showMessage('success', "Horário selecionado: " + dataHora);
        return;
    }

    // AJUSTE 2: TRAVA IMEDIATA NO PRIMEIRO CLIQUE PARA HORÁRIO DISPONÍVEL QUE JÁ PASSOU
    if (isPast && props.disponivel == 1) {
        showMessage('error', "Não é possível realizar agendamentos para um horário que já passou.");
        return;
    }

    // 1. TRAVA DE CONFIRMADO/REALIZADO
    if (props.confirmado == 1 || props.status === 'Realizado') {
        showMessage('error', "Este atendimento já foi realizado e não pode ser cancelado.");
        return;
    }

    // 2. TRAVA DE HORÁRIO PASSADO (Impede cancelar retroativamente agendamentos passados)
    if (isPast && props.disponivel != 1) {
        showMessage('error', "Este horário já passou e não pode mais ser cancelado.");
        return;
    }

    if (props.disponivel == 1) {
        $('#agendarModal').find('.data-hora').text(dataHora);
        $('#id_horario_agendar').val(event.id).data('versao', props.versao);
        $('#agendarModal').addClass('is-visible');
    } else if (props.matricula_agendada === LaravelConfig.matriculaUsuario || props.matricula === LaravelConfig.matriculaUsuario) {
        $('#cancelarModal').find('.data-hora').text(dataHora);
        $('#id_horario_cancelar').val(event.id).data('versao', props.versao);
        $('#cancelarModal').addClass('is-visible');
    } else {
        showMessage('error', "Este horário já está ocupado.");
    }
}

function closeModal() { $('.modal').removeClass('is-visible'); }
$('.modal').on('click', function(e) { if ($(e.target).is(this)) { closeModal(); } });
function showMessage(type, message) { $('#message-box').removeClass('success error').addClass(type).text(message).fadeIn().delay(4000).fadeOut(); }

$('#agendar-form').on('submit', function(e) { 
    e.preventDefault(); 
    sendAjaxRequest(LaravelConfig.rotas.agendar, { 
        action: 'agendar', 
        id_horario: $('#id_horario_agendar').val(),
        versao: $('#id_horario_agendar').data('versao') 
    }); 
});

$('#cancelar-form').on('submit', function(e) { 
    e.preventDefault(); 
    sendAjaxRequest(LaravelConfig.rotas.cancelar, { 
        action: 'cancelar', 
        id_horario: $('#id_horario_cancelar').val(),
        versao: $('#id_horario_cancelar').data('versao'), 
        justificativa: $('#justificativa').val() 
    }); 
});


function sendAjaxRequest(urlAlvo, data) {
    return Operacoes.ajax({
        grupo: 'agenda', url: urlAlvo, data, json: true, navegar: true,
        botoes: '#agendar-form button[type="submit"], #cancelar-form button[type="submit"]',
        texto: data.action === 'agendar' ? 'Agendando…' : 'Cancelando…',
        sucesso(response) {
            showMessage(response.status, response.message);
            setTimeout(() => window.refreshCalendar(), 1000);
        },
        erro(message) { showMessage('error', message); }
    });
}
