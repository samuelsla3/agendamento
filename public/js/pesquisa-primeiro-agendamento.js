(() => {
    'use strict';
    const modal = document.getElementById('pesquisa-modal'), config = window.PesquisaConfig;
    if (!modal || !config) return;
    const form = document.getElementById('pesquisa-form');
    const erro = document.getElementById('pesquisa-erro');
    let ocupado = false, respondeu = false, abriu = false;
    const recebido = performance.now(), agora = Date.parse(config.agora), fim = Date.parse(config.fim);
    const encerrou = () => agora + performance.now() - recebido >= fim;
    const voltar = () => typeof window.refreshCalendar === 'function' ? window.refreshCalendar() : location.reload();
    async function requisitar(url, dados = {}, keepalive = false) {
        const controlador = new AbortController();
        const limite = setTimeout(() => controlador.abort(), 12000);
        try {
            const resposta = await fetch(url, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store', keepalive, signal: controlador.signal,
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf},
                body: JSON.stringify(dados)
            });
            const corpo = await resposta.json().catch(() => ({}));
            if (!resposta.ok) {
                const mensagem = resposta.status === 410 ? 'O período da pesquisa terminou. Seu agendamento continua confirmado.'
                    : resposta.status === 419 ? 'Sua sessão expirou. Atualize a página; seu agendamento já está confirmado.'
                    : resposta.status === 422 ? (Object.values(corpo.errors || {}).flat()[0] || 'Confira as respostas antes de enviar.')
                    : 'Não foi possível registrar a pesquisa. Tente novamente ou feche a janela; seu agendamento continua confirmado.';
                const falha = new Error(mensagem); falha.publica = true; throw falha;
            }
            return corpo;
        } catch (falha) {
            if (falha.publica) throw falha;
            throw new Error('Não foi possível enviar a pesquisa. Tente novamente ou feche a janela; seu agendamento continua confirmado.');
        } finally { clearTimeout(limite); }
    }
    function fechar() {
        if (ocupado) return;
        modal.hidden = true;
        document.body.classList.remove('pesquisa-aberta');
        if (!respondeu && !encerrou()) requisitar(config.rotas.dispensar, {}, true).catch(() => {});
        voltar();
    }
    function pintarEstrelas(nota) {
        modal.querySelectorAll('[data-estrela]').forEach(label => label.classList.toggle('pesquisa-estrela-ativa', Number(label.dataset.estrela) <= nota));
    }
    const notaMarcada = () => Number(form.querySelector('[name="estrelas"]:checked')?.value ?? -1);
    form.querySelectorAll('[name="estrelas"]').forEach(input => input.addEventListener('change', () => {
        const nota = notaMarcada(); pintarEstrelas(nota);
        document.getElementById('pesquisa-nota').textContent = `${nota} de 5 estrelas.`;
    }));
    modal.querySelectorAll('[data-estrela]').forEach(label => {
        label.addEventListener('mouseenter', () => pintarEstrelas(Number(label.dataset.estrela)));
        label.addEventListener('focusin', () => pintarEstrelas(Number(label.dataset.estrela)));
    });
    document.getElementById('pesquisa-estrelas').addEventListener('mouseleave', () => pintarEstrelas(notaMarcada()));
    document.getElementById('pesquisa-estrelas').addEventListener('focusout', () => pintarEstrelas(notaMarcada()));
    modal.querySelectorAll('[data-fechar-pesquisa]').forEach(botao => botao.addEventListener('click', fechar));
    modal.addEventListener('click', evento => { if (evento.target === modal) fechar(); });
    document.addEventListener('keydown', evento => {
        if (modal.hidden) return;
        if (evento.key === 'Escape') { evento.preventDefault(); evento.stopImmediatePropagation(); fechar(); }
        if (evento.key === 'Tab') {
            const focaveis = [...modal.querySelectorAll('button, input, textarea')]
                .filter(campo => !campo.disabled && campo.getClientRects().length > 0);
            const primeiro = focaveis[0], ultimo = focaveis[focaveis.length - 1];
            if (!primeiro) return;
            if (evento.shiftKey && document.activeElement === primeiro) { evento.preventDefault(); ultimo.focus(); }
            else if (!evento.shiftKey && document.activeElement === ultimo) { evento.preventDefault(); primeiro.focus(); }
        }
    }, true);
    form.addEventListener('submit', async evento => {
        evento.preventDefault();
        if (ocupado) return;
        if (encerrou()) { erro.textContent = 'O período da pesquisa terminou. Seu agendamento continua confirmado.'; erro.hidden = false; return; }
        if (!form.reportValidity()) return;
        const dados = Object.fromEntries(new FormData(form)); dados.estrelas = Number(dados.estrelas);
        ocupado = true; erro.hidden = true;
        const botoes = [...modal.querySelectorAll('button')]; botoes.forEach(botao => { botao.disabled = true; });
        try {
            const confirmacao = await requisitar(config.rotas.responder, dados);
            if (confirmacao.status !== 'success') throw new Error('Não foi possível confirmar o envio da pesquisa. Tente novamente ou feche a janela.');
            respondeu = true; form.hidden = true;
            document.getElementById('pesquisa-sucesso').hidden = false;
            document.getElementById('pesquisa-agradecimento').focus();
        } catch (falha) {
            erro.textContent = falha.message || 'Não foi possível enviar. Tente novamente ou escolha Agora não.'; erro.hidden = false;
        } finally { ocupado = false; botoes.forEach(botao => { botao.disabled = false; }); conferirPrazo(); }
    });
    function conferirPrazo() { if (abriu && !modal.hidden && encerrou() && !ocupado) fechar(); }
    document.addEventListener('visibilitychange', conferirPrazo);
    window.PesquisaPrimeiroAgendamento = {
        async abrir() {
            if (abriu || encerrou()) return false;
            try {
                const convite = await requisitar(config.rotas.convite);
                if (!convite.exibir || encerrou()) return false;
                abriu = true; modal.hidden = false; document.body.classList.add('pesquisa-aberta');
                modal.querySelector('[data-fechar-pesquisa]').focus();
                setTimeout(conferirPrazo, Math.max(1, Math.min(fim - agora - (performance.now() - recebido), 2147483647)));
                return true;
            } catch (_) { return false; } // A pesquisa nunca desfaz nem impede a confirmação do agendamento.
        }
    };
})();
