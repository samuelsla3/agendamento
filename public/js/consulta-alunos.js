(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('consulta-alunos-modal');
        if (!modal) return;
        const el = id => document.getElementById('consulta-' + id);
        let pagina = 1, ultima = 1, busca = '', selecionado = null, listaVersao = 0, detalheVersao = 0;
        let listaControle, detalheControle;
        const criar = (tag, texto, classe) => {
            const node = document.createElement(tag); if (texto !== undefined) node.textContent = texto;
            if (classe) node.className = classe; return node;
        };
        const dataHora = (data, hora) => {
            const partes = /^(\d{4})-(\d{2})-(\d{2})/.exec(data || '');
            return partes ? `${partes[3]}/${partes[2]}/${partes[1]}${hora ? ' às ' + hora.slice(0, 5) : ''}` : 'Data não preservada';
        };
        async function receber(url, controle) {
            const r = await fetch(url, {headers: {Accept: 'application/json'}, credentials: 'same-origin', cache: 'no-store', signal: controle.signal});
            if (!r.ok || r.redirected) {
                const erro = new Error([401, 403, 419].includes(r.status) || r.redirected
                    ? 'Sua sessão expirou ou este acesso não está autorizado. Atualize a página.'
                    : 'Não foi possível consultar. Tente novamente.');
                erro.sessao = [401, 403, 419].includes(r.status) || r.redirected; throw erro;
            }
            return r.json();
        }
        function falha(erro) {
            if (erro.name === 'AbortError') return;
            el('aviso').textContent = erro.message;
            if (erro.sessao) { el('lista').replaceChildren(); el('detalhe').replaceChildren(); selecionado = null; }
        }
        async function listar(p = 1) {
            const versao = ++listaVersao;
            listaControle?.abort(); listaControle = new AbortController();
            el('aviso').textContent = 'Pesquisando…'; el('anterior').disabled = true; el('proxima').disabled = true;
            el('lista').replaceChildren();
            const url = new URL(modal.dataset.lista, location.href); url.searchParams.set('busca', busca); url.searchParams.set('page', p);
            try {
                const r = await receber(url, listaControle);
                if (versao !== listaVersao || !modal.open) return;
                pagina = r.pagina; ultima = r.ultima_pagina;
                el('aviso').textContent = r.alunos.length ? '' : 'Nenhum aluno encontrado.';
                el('total').textContent = `${r.total} aluno(s) encontrado(s)`;
                el('pagina').textContent = `${pagina} / ${ultima}`;
                el('anterior').disabled = pagina <= 1; el('proxima').disabled = pagina >= ultima;
                r.alunos.forEach(a => {
                    const b = criar('button', undefined, 'consulta-aluno'); b.type = 'button'; b.dataset.aluno = a.id;
                    b.setAttribute('aria-pressed', String(a.id === selecionado));
                    b.append(criar('strong', a.nome), criar('span', `${a.matricula || 'Sem matrícula'} · ${a.turma}`));
                    b.addEventListener('click', () => detalhar(a.id)); el('lista').append(b);
                });
            } catch (erro) { if (versao === listaVersao && modal.open) falha(erro); }
        }
        async function detalhar(id, p = 1) {
            const versao = ++detalheVersao; selecionado = id;
            detalheControle?.abort(); detalheControle = new AbortController();
            el('detalhe').replaceChildren(criar('p', 'Consultando aluno…'));
            el('lista').querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', String(Number(b.dataset.aluno) === id)));
            const url = new URL(modal.dataset.detalhe.replace('__ID__', id), location.href); url.searchParams.set('page', p);
            try {
                const r = await receber(url, detalheControle);
                if (versao !== detalheVersao || !modal.open || selecionado !== id) return;
                const a = r.aluno, s = r.resumo, painel = el('detalhe'); painel.replaceChildren();
                const titulo = criar('h3', a.nome); titulo.tabIndex = -1;
                painel.append(titulo, criar('p', `Matrícula: ${a.matricula || 'Não informada'} · Turma: ${a.turma}`));
                const atalhos = criar('div', undefined, 'consulta-atalhos');
                const prontuario = criar('a', 'Prontuário', 'btn btn-info');
                const destino = new URL(a.prontuario, location.href);
                if (destino.origin === location.origin) { prontuario.href = destino.href; atalhos.append(prontuario); }
                const mensagens = criar('button', 'Mensagens/e-mail', 'btn btn-primary'); mensagens.type = 'button';
                mensagens.disabled = a.provisorio;
                mensagens.addEventListener('click', async () => {
                    if (typeof window.MensagensAbrirAluno !== 'function') { el('aviso').textContent = 'Atualize a página para carregar as mensagens.'; return; }
                    mensagens.disabled = true;
                    try {
                        const aberto = await window.MensagensAbrirAluno(a.id, document.getElementById('abrir-consulta-alunos'));
                        if (aberto) modal.close();
                        else el('aviso').textContent = 'Conclua o envio da mensagem atual antes de abrir outra conversa.';
                    } finally { mensagens.disabled = a.provisorio; }
                });
                atalhos.append(mensagens); painel.append(atalhos);
                if (a.provisorio) painel.append(criar('p', 'Cadastro provisório: o aluno precisa concluir seu acesso para usar mensagens internas.', 'consulta-nota'));
                const insights = criar('div', undefined, 'consulta-insights');
                [[s.realizados, 'Atendimentos realizados'], [s.proximos, 'Próximos agendamentos'], [s.faltas, 'Não compareceu'],
                    [s.cancelados_aluno, 'Cancelados pelo aluno'], [s.cancelados_psicologa, 'Cancelados pela psicóloga'],
                    [s.pendentes_passados, 'Horários passados em aberto']].forEach(([n, label]) => {
                    const card = criar('div', undefined, 'consulta-insight'); card.append(criar('strong', n), criar('span', label)); insights.append(card);
                });
                painel.append(insights, criar('p', 'Contagens de todo o histórico, com o mesmo critério do relatório.', 'consulta-nota'));
                if (s.ultimo_realizado) painel.append(criar('p', 'Último atendimento realizado com data registrada: ' + dataHora(s.ultimo_realizado.data_atendimento, s.ultimo_realizado.hora_atendimento)));
                if (r.realizados_sem_data) painel.append(criar('p', `${r.realizados_sem_data} atendimento(s) realizado(s) sem data preservada, incluído(s) no total.`, 'consulta-nota'));
                if (r.sem_matricula) painel.append(criar('p', 'Sem matrícula: não é possível associar com segurança os registros antigos pelo nome.', 'consulta-nota'));
                painel.append(criar('h4', 'Próximos atendimentos'));
                if (!r.proximos.length) painel.append(criar('p', 'Nenhum atendimento futuro marcado.'));
                r.proximos.forEach(h => painel.append(criar('div', dataHora(h.data, h.hora), 'consulta-registro')));
                if (s.proximos > r.proximos.length) painel.append(criar('p', `Mostrando os ${r.proximos.length} mais próximos de ${s.proximos}. Consulte a agenda para os demais.`, 'consulta-nota'));
                if (s.pendentes_passados) painel.append(criar('p', 'Há horários passados que ainda precisam de encerramento na agenda.', 'consulta-nota'));
                painel.append(criar('h4', 'Histórico de atendimentos'));
                if (!r.historico.length) painel.append(criar('p', 'Nenhum atendimento encerrado ou cancelado registrado.'));
                r.historico.forEach(h => {
                    const linha = criar('div', undefined, 'consulta-registro');
                    linha.append(criar('strong', h.status), criar('span', dataHora(h.data_atendimento, h.hora_atendimento))); painel.append(linha);
                });
                if (r.ultima_pagina > 1) {
                    const nav = criar('nav', undefined, 'consulta-paginacao'); nav.setAttribute('aria-label', 'Páginas do histórico');
                    const ant = criar('button', 'Anterior'), prox = criar('button', 'Próxima'); ant.type = prox.type = 'button';
                    ant.disabled = r.pagina <= 1; prox.disabled = r.pagina >= r.ultima_pagina;
                    ant.addEventListener('click', () => detalhar(id, r.pagina - 1)); prox.addEventListener('click', () => detalhar(id, r.pagina + 1));
                    nav.append(ant, criar('span', `${r.pagina} / ${r.ultima_pagina}`), prox); painel.append(nav);
                }
                titulo.focus({preventScroll: true});
                if (matchMedia('(max-width: 700px)').matches) titulo.scrollIntoView({behavior: 'smooth', block: 'start'});
            } catch (erro) { if (versao === detalheVersao && modal.open) { el('detalhe').replaceChildren(); falha(erro); } }
        }
        document.getElementById('abrir-consulta-alunos').addEventListener('click', () => { if (!modal.open) modal.showModal(); listar(1); });
        el('fechar').addEventListener('click', () => modal.close());
        modal.addEventListener('close', () => {
            ++listaVersao; ++detalheVersao; listaControle?.abort(); detalheControle?.abort(); selecionado = null;
            el('lista').replaceChildren(); el('detalhe').replaceChildren(criar('p', 'Selecione um aluno para consultar os atendimentos.'));
            el('aviso').textContent = ''; el('total').textContent = ''; el('pagina').textContent = '';
        });
        el('busca-form').addEventListener('submit', event => {
            event.preventDefault(); busca = el('busca').value.trim(); selecionado = null; ++detalheVersao; detalheControle?.abort();
            el('detalhe').replaceChildren(criar('p', 'Selecione um aluno para consultar os atendimentos.')); listar(1);
        });
        el('anterior').addEventListener('click', () => { if (pagina > 1) listar(pagina - 1); });
        el('proxima').addEventListener('click', () => { if (pagina < ultima) listar(pagina + 1); });
    });
})();
