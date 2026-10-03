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

            // Substitua o bloco de verificação de status no eventDataTransform por este:
if (props.confirmado == 1) {
    className = 'evento-indisponivel-psicologa';
    statusTexto = 'Realizado: ' + (props.nome || 'N/A');
} else if (props.disponivel == 0) {
    className = 'evento-indisponivel-psicologa'; 
    statusTexto = 'Agendado: ' + (props.nome || 'N/A');
} else if (props.justificativa_cancelamento || String(props.status_real || '').startsWith('Cancelado')) {
    className = 'evento-disponivel-psicologa';
    statusTexto = 'Disponível (cancelamento anterior)';
}

            let tituloFinal = (horarioOriginal ? horarioOriginal + ' - ' : '') + statusTexto;

            return { 
                ...rawEventData, 
                classNames: [className], 
                title: tituloFinal 
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
    window.refreshCalendar = function() { calendar.refetchEvents(); }
});

function openModal(event) {
    const props = event.extendedProps;
    const start = new Date(event.start);
    const isPast = start < new Date();
    const disponivel = Number(props.disponivel) === 1;
    const confirmado = Number(props.confirmado) === 1;
    const statusAnterior = String(props.status_real || '');
    const teveCancelamento = disponivel && !confirmado && (
        Boolean(props.justificativa_cancelamento)
        || statusAnterior.startsWith('Cancelado')
    );

    $('#eventId').val(event.id).data('versao', props.versao);
    $('#justificativaTexto, #agendadoNome, #agendadoTurma, #agendadoMatricula, #agendadoStatus').text('');
    $('#justificativaInfo, #agendadoInfo, #form-disponivel, #confirmBtn, #cancelByPsicologaBtn, #deleteBtn, #prontuarioBtn').hide();

    if (disponivel && !confirmado) {
        $('#modalTitle').text('Editar Horário Disponível');
        $('#form-disponivel').show();

        // Usa a data local exibida no calendário, sem convertê-la para UTC.
        const ano = start.getFullYear();
        const mes = String(start.getMonth() + 1).padStart(2, '0');
        const dia = String(start.getDate()).padStart(2, '0');
        $('#data').val(`${ano}-${mes}-${dia}`);
        $('#hora').val(start.toTimeString().slice(0, 5));

        if (teveCancelamento) {
            const nome = props.nome || props.aluno_nome;
            const turma = props.turma_formatada || 'Não informada';
            let aviso = nome ? `A reserva anterior de ${nome} (${turma})` : 'A reserva anterior';

            if (statusAnterior === 'Cancelado pelo Aluno') {
                aviso += ' foi cancelada pelo próprio aluno.';
            } else if (statusAnterior === 'Cancelado pela Psicóloga') {
                aviso += ' foi cancelada pela psicóloga.';
            } else {
                aviso += ' foi cancelada.';
            }

            if (props.data_hora_reserva_anterior) {
    aviso += ` A reserva cancelada estava marcada para ${props.data_hora_reserva_anterior}.`;
} else {
    aviso += ' A data e a hora originais não foram preservadas neste registro antigo.';
}

if (props.justificativa_cancelamento) {
    aviso += ` Motivo: ${props.justificativa_cancelamento}`;
}

            aviso += ' Esta vaga está disponível para edição e novo agendamento.';
            // Nome e justificativa são texto, nunca HTML executável.
            $('#justificativaTexto').text(aviso);
            $('#justificativaInfo').show();
        }

        if (!isPast) {
            $('#deleteBtn').show();
        }
    } else {
        $('#modalTitle').text('Detalhes do Agendamento');
        $('#agendadoInfo').show();
        $('#agendadoNome').text(props.nome || props.aluno_nome || 'N/A');
        $('#agendadoTurma').text(props.turma_formatada || 'Não informada');
        $('#agendadoMatricula').text(props.matricula || props.aluno_matricula || 'N/A');

        const alunoId = props.aluno_id || props.user_id;
        if (alunoId && alunoId !== 'null') {
            $('#prontuarioBtn').attr('href', '/prontuarios/aluno/' + alunoId).css('display', 'inline-block');
        }

        if (confirmado) {
            $('#agendadoStatus').text('Realizado / Concluído').css('color', '#00833D');
        } else {
            $('#agendadoStatus').text('Agendado').css('color', '#d97706');
            $('#cancelByPsicologaBtn').show();
            if (isPast) {
                $('#confirmBtn').show();
            } else {
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
    if (['confirmar', 'cancel_by_psicologa', 'delete', 'edit'].includes(data.action)) {
        data.versao = $('#eventId').data('versao');
    }
    const textos = {confirmar: 'Concluindo…', cancel_by_psicologa: 'Cancelando…',
        generate_default: 'Gerando horários…', generate_individual: 'Criando horário…',
        edit: 'Salvando…', delete: 'Excluindo…', delete_specific_default: 'Excluindo horários…'};
    return Operacoes.ajax({
        grupo: 'agenda', url: LaravelConfig.rotas.acao, data, navegar: true,
        botoes: '#generate-form button[type="submit"], #individual-form button[type="submit"], #delete-form button[type="submit"], #form-disponivel button[type="submit"], #cancel-by-psicologa-form button[type="submit"], #confirmBtn, #deleteBtn',
        texto: textos[data.action] || 'Processando…',
        sucesso(response) { alert(response.message); location.reload(); },
        erro(message) { alert(message); }
    });
}

$('#generate-form').submit(function(e) { e.preventDefault(); const diasSelecionados = []; $('input[name="dias_semana[]"]:checked').each(function() { diasSelecionados.push($(this).val()); }); sendAjaxRequest({ action: 'generate_default', data_inicio: $('#data_inicio_gerar').val(), data_fim: $('#data_fim_gerar').val(), dias_semana: diasSelecionados, horas_selecionadas: $('input[name="horas_selecionadas[]"]:checked').map(function() { return this.value; }).get() }); });
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


(() => {
    const form = $('#filtro-relatorio-form');
    const pesquisaAluno = document.getElementById('aluno_nome');
    const selecaoAluno = document.getElementById('aluno_id');
    const sugestoesAlunos = document.getElementById('alunos-relatorio');
    const alunosPorOpcao = new Map(Array.from(sugestoesAlunos?.options || []).map(opcao => [
        opcao.value, opcao.dataset.alunoId
    ]));
    const sincronizarAluno = () => {
        if (!pesquisaAluno || !selecaoAluno) return;
        // Só uma opção completa identifica um aluno. Texto livre continua sendo busca por nome.
        selecaoAluno.value = alunosPorOpcao.get(pesquisaAluno.value) || '';
    };
    pesquisaAluno?.addEventListener('input', sincronizarAluno);
    pesquisaAluno?.addEventListener('change', sincronizarAluno);
    sincronizarAluno();
    const resultado = $('#resultado_relatorio');
    const exportar = $('#exportar-pdf-btn');
    const gerar = $('#filtro-relatorio-form button[type="submit"]');
    let requisicao = null;
    let versao = 0;
    let filtrosGerados = null;
    exportar.prop('disabled', true);

    form.on('input change', 'input, select', function () {
        versao++;
        if (requisicao) requisicao.abort();
        requisicao = null;
        gerar.prop('disabled', false).text('Gerar Relatório');
        filtrosGerados = null;
        exportar.prop('disabled', true);
        resultado.empty().append($('<p>').text('Filtros alterados. Clique em Gerar Relatório para atualizar.'));
    });

    form.on('submit', function (e) {
        e.preventDefault();
        sincronizarAluno();
        const atual = ++versao;
        if (requisicao) requisicao.abort();
        const filtros = form.serialize();
        gerar.prop('disabled', true).text('Gerando relatório…');
        filtrosGerados = null;
        exportar.prop('disabled', true);
        resultado.html('<p>Carregando relatório...</p>');
        requisicao = $.ajax({
            url: LaravelConfig.rotas.relatorio,
            method: 'POST',
            data: filtros,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LaravelConfig.csrfToken },
            dataType: 'html',
            success: function (response) {
                if (atual !== versao || form.serialize() !== filtros) return;
                resultado.html(response);
                adaptarTabelaRelatorio();
                filtrosGerados = filtros;
                exportar.prop('disabled', !document.getElementById('resumo-relatorio'));
            },
            error: function (xhr, status) {
                if (status === 'abort' || atual !== versao) return;
                let dados = xhr.responseJSON;
                if (!dados) {
                    try { dados = JSON.parse(xhr.responseText); } catch (_) { dados = {}; }
                }
                let mensagem = 'Não foi possível gerar o relatório. Tente novamente.';
                if (xhr.status === 422 && dados.errors) {
                    mensagem = Object.values(dados.errors).flat().join(' ');
                } else if (xhr.status === 419) {
                    mensagem = 'Sua sessão expirou. Recarregue a página e entre novamente.';
                } else if (xhr.status === 401 || xhr.status === 403) {
                    mensagem = 'Acesso não autorizado. Entre novamente com a conta da psicóloga.';
                }
                resultado.empty().append($('<p>').css('color', '#b42318').text(mensagem));
            },
            complete: function () {
                if (atual === versao) {
                    requisicao = null;
                    gerar.prop('disabled', false).text('Gerar Relatório');
                }
            }
        });
    });

    exportar.on('click', function () {
        if (!filtrosGerados || form.serialize() !== filtrosGerados || !document.getElementById('resumo-relatorio')) {
            alert('Gere o relatório com os filtros atuais antes de exportar.');
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('A biblioteca de PDF não carregou. Recarregue a página.');
            return;
        }
        const doc = new window.jspdf.jsPDF();
        const tabela = document.getElementById('tabela-relatorio');
        if (tabela && typeof doc.autoTable !== 'function') {
            alert('A biblioteca de tabelas do PDF não carregou. Recarregue a página.');
            return;
        }
        const texto = id => (document.getElementById(id)?.textContent || '').replace(/\s+/g, ' ').trim();
        const largura = doc.internal.pageSize.getWidth();
        const altura = doc.internal.pageSize.getHeight();
        const margem = 14;
        const larguraTexto = largura - margem * 2;
        const totalPagesExp = '{total_pages_count_string}';
        // Captura o resultado renderizado, jamais os valores de outro pedido.
        const metadados = [
            texto('indicadores-relatorio'), texto('total-registros-relatorio'),
            texto('periodo-relatorio'), texto('situacao-relatorio'), texto('aluno-relatorio'),
            texto('turma-relatorio'),
            'Indicadores: somente realizados; alunos distintos por matrícula, dentro dos filtros.',
            texto('avisos-relatorio')
        ].filter(Boolean);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        const linhas = metadados.flatMap(linha => doc.splitTextToSize(linha, larguraTexto));
        const inicioTabela = 34 + linhas.length * 4.2 + 5;
        const emitido = new Date().toLocaleString('pt-BR');
        const desenharCabecalho = pagina => {
            doc.setTextColor(40);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text('Relatório de Atendimentos', margem, 18);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.text('Emitido em: ' + emitido, margem, 25);
            linhas.forEach((linha, i) => doc.text(linha, margem, 34 + i * 4.2));
            doc.setFontSize(9);
            const total = typeof doc.putTotalPages === 'function' ? ' de ' + totalPagesExp : '';
            doc.text('Página ' + pagina + total, margem, altura - 10);
        };
        if (tabela) {
            doc.autoTable({
                html: '#tabela-relatorio', startY: inicioTabela,
                margin: { top: inicioTabela, right: margem, bottom: 20, left: margem },
                theme: 'grid', styles: { fontSize: 9, overflow: 'linebreak' },
                headStyles: { fillColor: [0, 131, 61], textColor: [255, 255, 255] },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                didDrawPage: data => desenharCabecalho(data.pageNumber)
            });
        } else {
            desenharCabecalho(1);
            doc.text(doc.splitTextToSize(texto('sem-resultados'), larguraTexto), margem, inicioTabela + 5);
        }
        if (typeof doc.putTotalPages === 'function') doc.putTotalPages(totalPagesExp);
        doc.save('relatorio_atendimentos.pdf');
    });
})();

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
    const alunoId = botao.getAttribute('data-alunoid');
    const disponivel = parseInt(botao.getAttribute('data-disponivel'));
    const confirmado = parseInt(botao.getAttribute('data-confirmado'));
    const nome = botao.getAttribute('data-nome');
    const matricula = botao.getAttribute('data-matricula');
    const turma = botao.getAttribute('data-turma') || 'Não informada';
    const isPast = botao.getAttribute('data-ispast') === '1';

    $('#eventId').val(id).data('versao', botao.getAttribute('data-versao'));
    $('#justificativaTexto, #agendadoNome, #agendadoTurma, #agendadoMatricula, #agendadoStatus').text('');

    $('#justificativaInfo, #agendadoInfo, #form-disponivel, #confirmBtn, #cancelByPsicologaBtn, #deleteBtn, #prontuarioBtn').hide();

    $('#modalTitle').text('Detalhes do Agendamento');
    $('#agendadoInfo').show();

    $('#agendadoNome').text(nome);
    $('#agendadoTurma').text(turma);
    $('#agendadoMatricula').text(matricula);
    $('#agendadoStatus').text('Agendado').css('color', '#d97706');

    if (alunoId && alunoId !== '' && alunoId !== 'null' && alunoId !== 'undefined') {
        $('#prontuarioBtn').attr('href', '/prontuarios/aluno/' + alunoId).css('display', 'inline-block');
    } else if (matricula && matricula !== 'N/A' && matricula !== '') {
        $('#prontuarioBtn').attr('href', '/prontuarios/aluno/' + matricula).css('display', 'inline-block');
    }

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
        $('#agendadoStatus').text('Confirmado').css('color', '#00833D');
    }

    $('#modal').addClass('is-visible');
}

// A tabela recebe rolagem própria apenas no celular. Ao ampliar, a marcação
// original é restaurada, inclusive se o relatório já estiver na tela.
function adaptarTabelaRelatorio() {
    const tabela = $('#resultado_relatorio #tabela-relatorio');
    if (!tabela.length) return;
    const envolvida = tabela.parent().hasClass('relatorio-table-scroll');
    if (window.matchMedia('(max-width: 767px)').matches && !envolvida) {
        tabela.wrap('<div class="relatorio-table-scroll table-responsive-wrapper" tabindex="0" role="region" aria-label="Tabela do relatório, deslize para ver todas as colunas"></div>');
    } else if (!window.matchMedia('(max-width: 767px)').matches && envolvida) {
        tabela.unwrap();
    }
}
window.matchMedia('(max-width: 767px)').addEventListener('change', adaptarTabelaRelatorio);
