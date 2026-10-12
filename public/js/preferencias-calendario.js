(() => {
    'use strict';
    const segundoDoDia = data => data.getHours() * 3600 + data.getMinutes() * 60 + data.getSeconds();
    const segundos = texto => {
        if (typeof texto !== 'string' || !/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/.test(texto)) return null;
        const [hora, minuto] = texto.split(':').map(Number);
        return hora * 3600 + minuto * 60;
    };
    const horario = valor => [Math.floor(valor / 3600), Math.floor(valor % 3600 / 60), valor % 60]
        .map(parte => String(parte).padStart(2, '0')).join(':');
    function visualizacaoInicial(preferencias, celular) {
        return {mes: 'dayGridMonth', semana: 'timeGridWeek', lista: 'listWeek'}[preferencias?.visualizacao]
            || (celular ? 'listWeek' : 'dayGridMonth');
    }
    function calcularFaixa(preferencias, eventos, periodo, completo = false) {
        const preferidoInicio = segundos(preferencias?.inicio);
        const preferidoFim = segundos(preferencias?.fim);
        if (completo || !preferencias?.faixa_ativa || preferidoInicio === null
            || preferidoFim === null || preferidoFim <= preferidoInicio) {
            return {inicio: '00:00:00', fim: '24:00:00', ampliada: false};
        }
        let inicio = preferidoInicio, fim = preferidoFim;
        const limiteInicio = periodo?.start?.getTime(), limiteFim = periodo?.end?.getTime();
        if (Number.isFinite(limiteInicio) && Number.isFinite(limiteFim) && limiteFim > limiteInicio) {
            for (const evento of eventos) {
                if (evento.allDay || !evento.start || !Number.isFinite(evento.start.getTime())) continue;
                // O calendário atual usa a duração visual padrão do FullCalendar: uma hora.
                // Isso não define duração clínica nem altera horários no banco.
                const eventoFim = evento.end?.getTime() ?? evento.start.getTime() + 3600000;
                const inicioEfetivo = Math.max(evento.start.getTime(), limiteInicio);
                const fimEfetivo = Math.min(eventoFim, limiteFim);
                if (!Number.isFinite(fimEfetivo) || fimEfetivo <= inicioEfetivo) continue;
                // Divide o intervalo em dias locais; também cobre eventos que cruzem meia-noite.
                let dia = new Date(inicioEfetivo);
                dia.setHours(0, 0, 0, 0);
                while (dia.getTime() < fimEfetivo) {
                    const proximo = new Date(dia); proximo.setDate(proximo.getDate() + 1);
                    const trechoInicio = new Date(Math.max(inicioEfetivo, dia.getTime()));
                    const trechoFim = new Date(Math.min(fimEfetivo, proximo.getTime()));
                    inicio = Math.min(inicio, Math.floor(segundoDoDia(trechoInicio) / 1800) * 1800);
                    fim = Math.max(fim, trechoFim.getTime() === proximo.getTime()
                        ? 86400 : Math.ceil(segundoDoDia(trechoFim) / 1800) * 1800);
                    dia = proximo;
                }
            }
        }
        return {inicio: horario(inicio), fim: horario(fim),
            ampliada: inicio < preferidoInicio || fim > preferidoFim};
    }
    function opcoesIniciais(preferencias, celular) {
        const faixa = calcularFaixa(preferencias, [], null);
        const opcoes = {initialView: visualizacaoInicial(preferencias, celular),
            slotMinTime: faixa.inicio, slotMaxTime: faixa.fim};
        if (preferencias?.faixa_ativa) opcoes.scrollTime = faixa.inicio;
        return opcoes;
    }
    function conectar(calendar, preferencias) {
        const controles = document.getElementById('calendario-faixa');
        const botao = document.getElementById('calendario-dia-completo');
        const aviso = document.getElementById('calendario-faixa-aviso');
        let completo = false, agendado = false;
        function atualizar() {
            const grade = ['timeGridWeek', 'timeGridDay'].includes(calendar.view.type);
            const ativa = Boolean(preferencias?.faixa_ativa);
            if (controles) controles.hidden = !ativa || !grade;
            if (!ativa || !grade) return;
            const faixa = calcularFaixa(preferencias, calendar.getEvents(),
                {start: calendar.view.activeStart, end: calendar.view.activeEnd}, completo);
            const mudou = calendar.getOption('slotMinTime') !== faixa.inicio
                || calendar.getOption('slotMaxTime') !== faixa.fim;
            if (mudou) {
                calendar.batchRendering(() => {
                    calendar.setOption('slotMinTime', faixa.inicio);
                    calendar.setOption('slotMaxTime', faixa.fim);
                });
                calendar.scrollToTime(faixa.inicio);
            }
            if (botao) {
                botao.textContent = completo ? 'Usar faixa configurada' : 'Mostrar dia completo';
                botao.setAttribute('aria-pressed', String(completo));
            }
            if (aviso) {
                aviso.hidden = !faixa.ampliada;
                aviso.textContent = faixa.ampliada
                    ? 'A faixa foi ampliada para mostrar todos os horários deste período.' : '';
            }
        }
        function solicitarAtualizacao() {
            if (agendado) return;
            agendado = true;
            window.requestAnimationFrame(() => { agendado = false; atualizar(); });
        }
        calendar.on('datesSet', solicitarAtualizacao);
        calendar.on('eventsSet', solicitarAtualizacao);
        botao?.addEventListener('click', () => { completo = !completo; solicitarAtualizacao(); });
        solicitarAtualizacao();
    }
    window.PreferenciasCalendario = Object.freeze({visualizacaoInicial, calcularFaixa, opcoesIniciais, conectar});
})();
