(() => {
    'use strict';
    let calendario, atual, ocupado = false, carregando = false, sincronizado = true, revisao = 0;
    let opcoes = [];
    const el = id => document.getElementById(id);
    const futuro = inicio => !horarioJaIniciou(inicio);
    function controles() {
        const travado = ocupado || carregando || !sincronizado;
        const passado = atual && !futuro(atual.inicio);
        const alterado = el('indisponibilidade-texto').value.trim() !== (atual?.texto || '');
        el('indisponibilidade-texto').disabled = ocupado || carregando || passado;
        el('indisponibilidade-salvar').disabled = travado || passado;
        el('indisponibilidade-liberar').disabled = travado || passado;
        const selecionados = opcoes.filter(opcao => opcao.input.checked);
        el('indisponibilidade-aplicar').disabled = travado || alterado || selecionados.length === 0;
        el('indisponibilidade-recarregar').disabled = ocupado || carregando;
        opcoes.forEach(opcao => { opcao.input.disabled = travado || alterado; });
        el('indisponibilidade-selecao-resumo').textContent = alterado
            ? 'Salve a justificativa antes de aplicá-la a outros horários.'
            : `${selecionados.length} horário(s) selecionado(s).`;
    }
    function enviar(dados, sucesso) {
        if (ocupado) return;
        ocupado = true;
        const versaoTela = revisao;
        const pedido = Operacoes.ajax({
            grupo: 'agenda', url: LaravelConfig.rotas.acao, data: dados, json: true,
            botoes: '#indisponibilidade-painel button', texto: 'Salvando…',
            sucesso(resposta) {
                calendario.refetchEvents();
                if (versaoTela === revisao) sucesso(resposta);
            },
            erro(mensagem) { alert(mensagem); }
        });
        if (pedido) pedido.always(() => {
            ocupado = false;
            // Operacoes restaura os botões no complete do AJAX; aplica o estado atual depois.
            queueMicrotask(controles);
        });
        else { ocupado = false; controles(); }
    }
    function erroLista(mensagem) {
        const erro = new Error(mensagem); erro.mensagemPublica = mensagem; return erro;
    }
    async function carregarDia(mensagem = '') {
        const versaoTela = revisao;
        carregando = true; sincronizado = false; opcoes = [];
        el('indisponibilidade-outros').hidden = false;
        el('indisponibilidade-lista').replaceChildren();
        el('indisponibilidade-lista-status').textContent = 'Carregando horários desta data…';
        el('indisponibilidade-retorno').textContent = mensagem;
        el('indisponibilidade-recarregar').hidden = true;
        controles();
        try {
            const resposta = await fetch(LaravelConfig.rotas.eventos, {
                credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}
            });
            if (!resposta.ok) throw erroLista('Não foi possível atualizar a agenda. Tente recarregar os horários.');
            const eventos = await resposta.json();
            if (versaoTela !== revisao) return;
            if (!Array.isArray(eventos)) throw erroLista('Não foi possível ler a agenda. Recarregue a lista ou faça login novamente.');
            const origem = eventos.find(evento => String(evento.id) === atual.id);
            if (!origem || !origem.extendedProps.justificativa_indisponibilidade) {
                throw erroLista('Este horário foi alterado. Feche o modal e atualize a agenda.');
            }
            atual.versao = origem.extendedProps.versao;
            atual.texto = origem.extendedProps.justificativa_indisponibilidade;
            atual.data = origem.start.slice(0, 10);
            atual.inicio = origem.extendedProps.inicio_agendamento || origem.start;
            el('indisponibilidade-texto').value = atual.texto;
            el('indisponibilidade-data').textContent = new Date(origem.start).toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short'});
            el('indisponibilidade-outros-data').textContent = new Date(origem.start).toLocaleDateString('pt-BR');
            const livres = eventos.filter(evento => String(evento.id) !== atual.id
                && evento.start.slice(0, 10) === atual.data
                && Number(evento.extendedProps.disponivel) === 1
                && Number(evento.extendedProps.confirmado) !== 1
                && !evento.extendedProps.justificativa_indisponibilidade
                && futuro(evento.extendedProps.inicio_agendamento || evento.start))
                .sort((a, b) => a.start.localeCompare(b.start));
            for (const evento of livres) {
                const label = document.createElement('label'); label.className = 'indisponibilidade-opcao';
                const input = document.createElement('input'); input.type = 'checkbox';
                input.addEventListener('change', () => {
                    if (opcoes.filter(opcao => opcao.input.checked).length > 200) {
                        input.checked = false; alert('Selecione até 200 horários por vez.');
                    }
                    controles();
                });
                const texto = document.createElement('span');
                texto.textContent = new Date(evento.start).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                label.append(input, texto); el('indisponibilidade-lista').append(label);
                opcoes.push({input, id: Number(evento.id), versao: evento.extendedProps.versao});
            }
            el('indisponibilidade-lista-status').textContent = livres.length
                ? 'Marque as outras vagas que devem receber a mesma justificativa.'
                : 'Não há outros horários livres e futuros nesta data.';
            sincronizado = true;
        } catch (erro) {
            if (versaoTela !== revisao) return;
            el('indisponibilidade-lista-status').textContent = erro.mensagemPublica || 'Não foi possível carregar os horários. Tente recarregar a lista.';
            el('indisponibilidade-recarregar').hidden = false;
        } finally {
            if (versaoTela === revisao) { carregando = false; controles(); }
        }
    }
    function configurarModal(evento) {
        revisao++; opcoes = []; carregando = false; sincronizado = true;
        const props = evento.extendedProps;
        atual = {id: String(evento.id), versao: props.versao, texto: props.justificativa_indisponibilidade || '',
            data: '', inicio: props.inicio_agendamento || evento.start};
        const bloqueado = Boolean(atual.texto), passou = !futuro(atual.inicio);
        document.querySelector('#modal .modal-content').classList.toggle('horario-editavel', Number(props.disponivel) === 1 && !bloqueado);
        el('indisponibilidade-painel').hidden = !bloqueado;
        el('indisponibilidade-outros').hidden = true;
        el('indisponibilidade-retorno').textContent = '';
        el('indisponibilidade-lista').replaceChildren();
        el('justificarIndisponibilidadeBtn').disabled = passou;
        el('justificarIndisponibilidadeBtn').setAttribute('aria-expanded', String(bloqueado));
        el('indisponibilidade-texto').value = atual.texto;
        el('indisponibilidade-liberar').hidden = !bloqueado || passou;
        el('indisponibilidade-data').textContent = evento.start.toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short'});
        if (bloqueado) {
            mostrarBloqueio(); carregarDia();
        }
        controles();
    }
    function mostrarBloqueio() {
        el('modalTitle').textContent = 'Indisponibilidade do horário';
        document.querySelector('#modal .modal-content').classList.remove('horario-editavel');
        $('#form-disponivel, #agendadoInfo, #justificativaInfo, #confirmBtn, #cancelByPsicologaBtn, #deleteBtn, #prontuarioBtn, #consultarAlunoBtn, #faltaBtn, #corrigirSituacaoBtn').hide();
        el('indisponibilidade-painel').hidden = false;
        el('indisponibilidade-liberar').hidden = !futuro(atual.inicio);
    }
    document.addEventListener('DOMContentLoaded', () => {
        el('justificarIndisponibilidadeBtn').addEventListener('click', () => {
            const painel = el('indisponibilidade-painel'); painel.hidden = !painel.hidden;
            el('justificarIndisponibilidadeBtn').setAttribute('aria-expanded', String(!painel.hidden));
            if (!painel.hidden) el('indisponibilidade-texto').focus();
        });
        el('indisponibilidade-texto').addEventListener('input', controles);
        el('indisponibilidade-form').addEventListener('submit', e => {
            e.preventDefault();
            if (ocupado || carregando || !sincronizado) return;
            const texto = el('indisponibilidade-texto').value.trim();
            if (!texto) { el('indisponibilidade-texto').focus(); return; }
            enviar({action: 'justificar_indisponibilidade', justificativa: texto,
                horarios: [{id: Number(atual.id), versao: atual.versao}]}, resposta => {
                atual.texto = texto; mostrarBloqueio(); carregarDia(resposta.message);
            });
        });
        el('indisponibilidade-liberar').addEventListener('click', () => {
            if (ocupado || carregando || !sincronizado) return;
            enviar({action: 'liberar_indisponibilidade', id: Number(atual.id), versao: atual.versao}, resposta => {
                closeModal(); alert(resposta.message);
            });
        });
        el('indisponibilidade-recarregar').addEventListener('click', () => carregarDia());
        el('indisponibilidade-aplicar').addEventListener('click', () => {
            const selecionados = opcoes.filter(opcao => opcao.input.checked);
            if (ocupado || carregando || !sincronizado || !selecionados.length
                || el('indisponibilidade-texto').value.trim() !== atual.texto) return;
            enviar({action: 'justificar_indisponibilidade', justificativa: atual.texto,
                horarios: selecionados.map(({id, versao}) => ({id, versao}))}, resposta => carregarDia(resposta.message));
        });
    });
    window.IndisponibilidadeAgenda = {conectar: c => { calendario = c; }, configurarModal};
})();
