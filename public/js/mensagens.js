(() => {
    'use strict';
    function textoErro(dados, status) {
        const primeira = dados.errors && Object.values(dados.errors).flat()[0];
        if (primeira) return primeira;
        if (status === 413) return 'O servidor não recebeu os anexos por causa do tamanho. Selecione arquivos menores ou procure o responsável pelo sistema.';
        if (status === 429) return 'Muitas tentativas neste intervalo. Aguarde antes de tentar novamente.';
        return dados.message || 'Não foi possível concluir. Tente novamente.';
    }
    function validarArquivos(arquivos, quantidade, tamanho) {
        if (arquivos.length > quantidade) return `Selecione no máximo ${quantidade} arquivos por mensagem.`;
        for (const arquivo of arquivos) {
            if (!/\.(pdf|jpe?g|png|webp)$/i.test(arquivo.name)) return 'Selecione somente PDF, JPG, PNG ou WEBP.';
            if (!arquivo.size || arquivo.size > tamanho) return 'Cada arquivo pode ter até 10 MB.';
        }
        return '';
    }
    function montarEmail(email, assunto, texto, comTexto = true) {
        return 'mailto:' + encodeURIComponent(email) + '?subject=' + encodeURIComponent(assunto)
            + (comTexto ? '&body=' + encodeURIComponent(texto) : '');
    }
    // Helpers usados pela interface; não guardam conteúdo de conversas.
    window.MensagensUtil = {validarArquivos, montarEmail};

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('mensagens-modal'), origemDados = document.getElementById('mensagens-dados');
        if (!modal || !origemDados) return;
        const dados = JSON.parse(origemDados.textContent), el = id => document.getElementById('mensagens-' + id);
        const botoesAbrir = Array.from(document.querySelectorAll('[data-abrir-mensagens]'));
        const rascunhos = new Map(), itens = new Map();
        let selecionada = null, bloqueado = !!dados.psicologa, enviando = false, renovando = false;
        let modo = 'conversas', aba = 'interno', paginaContatos = 1, ultimaContatos = 1;
        let geracao = 0, consultaResumo = 0, consultaLista = 0, selecaoNumero = 0;
        let lendo = false, marcando = false, marcada = 0, atividade = -1, atividadeRenovada = -1;
        let baseServidor = 0, marcoServidor = performance.now(), expira = 0, listaOpcoes = new Map();
        let debounce = null, gatilho = null;

        function aviso(campo, texto, erro = false) {
            if (!campo) return;
            campo.textContent = texto; campo.hidden = !texto; campo.classList.toggle('erro', erro);
        }
        function rascunho() { return selecionada ? rascunhos.get(selecionada.id) : null; }
        function salvarRascunho() {
            const r = rascunho();
            if (!r || bloqueado || el('conversa-area').hidden) return;
            r.texto = el('texto').value;
            r.assunto = el('email-assunto').value; r.emailTexto = el('email-texto').value;
        }
        function temRascunho() {
            salvarRascunho();
            return enviando || Array.from(rascunhos.values()).some(r => r.texto.trim() || r.arquivos.length || r.emailTexto.trim());
        }
        function relogio() { return baseServidor + (performance.now() - marcoServidor) / 1000; }
        function prazo(resposta) {
            if (Number.isFinite(Number(resposta.agora))) { baseServidor = Number(resposta.agora); marcoServidor = performance.now(); }
            if (resposta.expira_em !== undefined) expira = Number(resposta.expira_em) || 0;
        }
        function csrf(novo) {
            if (!novo) return;
            dados.csrf = novo;
            document.querySelectorAll('input[name="_token"]').forEach(campo => { campo.value = novo; });
            const meta = document.querySelector('meta[name="csrf-token"]'); if (meta) meta.content = novo;
            if (typeof LaravelConfig !== 'undefined') LaravelConfig.csrfToken = novo;
        }
        async function api(url, metodo = 'GET', corpo = null, tempo = 20000) {
            const controle = new AbortController(), quando = geracao;
            const timeout = setTimeout(() => controle.abort(), tempo);
            const headers = {Accept: 'application/json', 'X-Mensagens-Contexto': dados.contexto};
            if (metodo !== 'GET') headers['X-CSRF-TOKEN'] = dados.csrf;
            let body = corpo;
            if (corpo && !(corpo instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(corpo); }
            try {
                const resposta = await fetch(url, {method: metodo, credentials: 'same-origin', cache: 'no-store', headers, body, signal: controle.signal});
                if (resposta.redirected || [401, 403, 419].includes(resposta.status)) {
                    const erro = new Error('Sua sessão precisa ser renovada. Atualize a página e entre novamente.');
                    erro.status = resposta.status === 200 ? 401 : resposta.status; erro.geracao = quando; throw erro;
                }
                let resultado = {}; try { resultado = await resposta.json(); } catch { /* Erro do servidor sem JSON. */ }
                if (!resposta.ok) { const erro = new Error(textoErro(resultado, resposta.status)); erro.status = resposta.status; erro.geracao = quando; throw erro; }
                if (!Object.keys(resultado).length) throw new Error('resposta_invalida');
                return resultado;
            } catch (erro) { if (erro.geracao === undefined) erro.geracao = quando; throw erro; }
            finally { clearTimeout(timeout); }
        }
        function limparTela() {
            itens.clear(); el('itens').replaceChildren(); el('texto').value = ''; el('selecionados').replaceChildren();
            el('email-texto').value = ''; el('email-assunto').value = ''; el('email-destino').textContent = '';
            el('destinatario-nome').textContent = ''; el('destinatario-info').textContent = '';
            el('conversa-area').hidden = true; modal.classList.remove('com-conversa');
            if (dados.psicologa) el('lista-contatos').replaceChildren();
        }
        function bloquear() {
            if (!bloqueado) { salvarRascunho(); geracao++; }
            bloqueado = true; limparTela(); el('area').hidden = true;
            if (dados.psicologa) el('bloqueio').hidden = false;
        }
        function tratarErro(erro, campo = el('aviso')) {
            if (erro.geracao !== undefined && erro.geracao !== geracao) return;
            if (erro.status === 423 && dados.psicologa) {
                bloquear(); aviso(el('aviso'), 'O acesso às conversas foi bloqueado. Informe a senha do prontuário para continuar.'); return;
            }
            if ([401, 403, 419].includes(erro.status)) {
                salvarRascunho(); geracao++; bloqueado = true; limparTela(); el('area').hidden = true;
                if (dados.psicologa) el('bloqueio').hidden = true;
                aviso(el('aviso'), erro.message, true); return;
            }
            aviso(campo, erro.status && erro.status < 500 ? erro.message : 'Não foi possível confirmar o resultado. Confira a conexão e tente novamente.', true);
        }
        function badge(resposta) {
            prazo(resposta);
            if (dados.psicologa && resposta.desbloqueado === false && modal.open && !bloqueado) bloquear();
            const total = Number(resposta.nao_lidas) || 0;
            document.querySelectorAll('[data-mensagens-badge]').forEach(span => {
                span.hidden = total === 0; span.textContent = total > 99 ? '99+' : String(total);
                span.setAttribute('aria-label', `${total} ${total === 1 ? 'mensagem não lida' : 'mensagens não lidas'}`);
            });
        }
        async function atualizarResumo() {
            const numero = ++consultaResumo, g = geracao;
            const resposta = await api(dados.resumo);
            if (numero !== consultaResumo || g !== geracao) return resposta;
            if (!!resposta.psicologa !== !!dados.psicologa) {
                const erro = new Error('O perfil de acesso mudou. Atualize a página e entre novamente.');
                erro.status = 403; erro.geracao = g; throw erro;
            }
            badge(resposta);
            if (dados.psicologa && !resposta.desbloqueado && modal.open) bloquear();
            return resposta;
        }
        function parametros() {
            const nome = el('nome').value.trim(), opcao = listaOpcoes.get(nome);
            return {nome: opcao ? opcao.nome : nome, turma: el('turma').value.trim(), matricula: el('matricula').value.trim()};
        }
        async function carregarContatos(pagina = 1, silencioso = false) {
            if (!dados.psicologa || bloqueado || !modal.open) return;
            const numero = ++consultaLista, atualGeracao = geracao, tipo = modo, filtros = parametros();
            const url = new URL(tipo === 'novo' ? dados.alunos : dados.contatos, window.location.href);
            Object.entries(filtros).forEach(([k,v]) => { if (v) url.searchParams.set(k, v); });
            if (tipo === 'conversas') url.searchParams.set('page', pagina);
            if (!silencioso) aviso(el('lista-aviso'), 'Buscando contatos…');
            try {
                const resposta = await api(url.href);
                if (numero !== consultaLista || atualGeracao !== geracao || tipo !== modo) return;
                const contatos = tipo === 'novo' ? resposta.alunos : resposta.contatos;
                if (!Array.isArray(contatos)) throw new Error('lista_invalida');
                const scroll = el('lista-contatos').scrollTop;
                el('lista-contatos').replaceChildren(); el('nomes').replaceChildren(); listaOpcoes = new Map();
                contatos.forEach(contato => {
                    const rotulo = `${contato.nome} — ${contato.turma} — ${contato.matricula}`;
                    listaOpcoes.set(rotulo, contato);
                    const opcao = document.createElement('option'); opcao.value = rotulo; el('nomes').append(opcao);
                    const botao = document.createElement('button'); botao.type = 'button'; botao.className = 'mensagens-contato';
                    if (selecionada && Number(contato.aluno_id ?? contato.id) === selecionada.aluno_id) botao.classList.add('ativo');
                    const avatar = document.createElement('span'); avatar.className = 'mensagens-avatar';
                    avatar.textContent = contato.nome.trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
                    const texto = document.createElement('span'); texto.className = 'mensagens-contato-texto';
                    const nome = document.createElement('span'); nome.className = 'mensagens-contato-nome'; nome.textContent = contato.nome;
                    const info = document.createElement('span'); info.className = 'mensagens-contato-info'; info.textContent = `${contato.turma} · ${contato.matricula}`;
                    texto.append(nome, info); botao.append(avatar, texto);
                    if (Number(contato.nao_lidas) > 0) { const bolinha = document.createElement('span'); bolinha.className = 'mensagens-badge'; bolinha.textContent = contato.nao_lidas; botao.append(bolinha); }
                    botao.addEventListener('click', () => abrirConversa(Number(contato.aluno_id ?? contato.id)));
                    el('lista-contatos').append(botao);
                });
                el('lista-contatos').scrollTop = scroll;
                el('turmas').replaceChildren();
                (resposta.turmas || []).forEach(t => { const o = document.createElement('option'); o.value = t; el('turmas').append(o); });
                paginaContatos = Number(resposta.pagina) || 1; ultimaContatos = Number(resposta.ultima_pagina) || 1;
                el('contatos-paginas').hidden = tipo !== 'conversas' || ultimaContatos <= 1;
                el('contatos-pagina').textContent = `${paginaContatos} / ${ultimaContatos}`;
                el('contatos-anterior').disabled = paginaContatos <= 1; el('contatos-proxima').disabled = paginaContatos >= ultimaContatos;
                aviso(el('lista-aviso'), contatos.length ? (tipo === 'novo' && contatos.length >= 50 ? 'Use os filtros para refinar os primeiros 50 resultados.' : '')
                    : tipo === 'novo' ? 'Nenhum aluno encontrado com esses filtros.' : 'Nenhuma conversa encontrada. Use Novo contato para procurar um aluno.');
            } catch (erro) { if (numero === consultaLista) tratarErro(erro, el('lista-aviso')); }
        }
        function destino(conversa) {
            if (selecionada?.id !== Number(conversa.id)) { aviso(el('email-aviso'), ''); el('email-sem-texto').hidden = true; }
            selecionada = {...conversa, id: Number(conversa.id), aluno_id: Number(conversa.aluno_id)};
            el('destinatario-nome').textContent = conversa.nome;
            el('destinatario-info').textContent = [conversa.turma, conversa.matricula].filter(Boolean).join(' · ');
            el('aba-email').hidden = !conversa.email;
            el('email-destino').textContent = conversa.email || '';
            el('email-confirmacao').hidden = !dados.psicologa || conversa.email_confirmado;
        }
        function restaurarRascunho(mostrarAviso = true) {
            const r = rascunho(); if (!r) return;
            el('texto').value = r.texto; el('texto').readOnly = r.incerto;
            el('email-assunto').value = r.assunto; el('email-texto').value = r.emailTexto;
            el('texto-contador').textContent = r.texto.length.toLocaleString('pt-BR') + ' / 10.000';
            el('enviar').textContent = r.incerto ? 'Tentar novamente' : 'Enviar';
            el('anexar').disabled = r.incerto || enviando;
            el('selecionados').replaceChildren();
            r.arquivos.forEach((arquivo, i) => {
                const chip = document.createElement('div'); chip.className = 'mensagens-arquivo-chip';
                const texto = document.createElement('span'); texto.textContent = `${arquivo.name} · ${(arquivo.size / 1048576).toFixed(1)} MB`;
                const remover = document.createElement('button'); remover.type = 'button'; remover.textContent = '×'; remover.setAttribute('aria-label', 'Remover ' + arquivo.name);
                remover.disabled = r.incerto || enviando;
                remover.addEventListener('click', () => { if (!enviando && !r.incerto) { r.arquivos.splice(i, 1); restaurarRascunho(); } });
                chip.append(texto, remover); el('selecionados').append(chip);
            });
            if (mostrarAviso) aviso(el('envio-aviso'), r.incerto ? 'O envio ficou sem confirmação. Tente novamente com o mesmo texto e arquivos para conferir sem duplicar.' : '');
        }
        function pertoDoFim() { const h = el('historico'); return h.scrollHeight - h.scrollTop - h.clientHeight < 90; }
        function adicionarMensagens(mensagens, anteriores = false) {
            const perto = pertoDoFim(), h = el('historico'), altura = h.scrollHeight, topo = h.scrollTop;
            const fragmento = document.createDocumentFragment();
            mensagens.forEach(m => {
                if (itens.has(Number(m.id))) return;
                itens.set(Number(m.id), m);
                const artigo = document.createElement('article'); artigo.className = 'mensagens-item' + (m.minha ? ' minha' : '');
                const balao = document.createElement('div'); balao.className = 'mensagens-balao';
                if (m.texto) { const texto = document.createElement('p'); texto.className = 'mensagens-conteudo'; texto.textContent = m.texto; balao.append(texto); }
                (m.anexos || []).forEach(a => {
                    let url; try { url = new URL(a.url, window.location.href); } catch { return; }
                    if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) return;
                    const link = document.createElement('a'); link.className = 'mensagens-anexo'; link.href = url.href;
                    link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = `↓ ${a.nome} · ${(a.tamanho / 1048576).toFixed(1)} MB`;
                    balao.append(link);
                });
                const hora = document.createElement('span'); hora.className = 'mensagens-hora'; hora.textContent = m.hora; balao.append(hora);
                artigo.append(balao); fragmento.append(artigo);
            });
            if (anteriores) { el('itens').prepend(fragmento); h.scrollTop = topo + h.scrollHeight - altura; }
            else { el('itens').append(fragmento); if (perto || enviando) h.scrollTop = h.scrollHeight; }
        }
        async function marcarLidas() {
            if (!selecionada || bloqueado || !modal.open || document.visibilityState !== 'visible' || aba !== 'interno' || !pertoDoFim() || marcando || !itens.size) return;
            const ate = Math.max(...itens.keys()); if (ate <= marcada) return;
            const id = selecionada.id, g = geracao; marcando = true;
            try { const resposta = await api(dados.lidas.replace('__ID__', id), 'POST', {ate}); if (g === geracao && selecionada?.id === id) { marcada = ate; badge(resposta); } }
            catch (erro) { tratarErro(erro); }
            finally { marcando = false; }
        }
        async function carregarHistorico(anteriores = false, inicial = false) {
            if (!selecionada || bloqueado || lendo || !modal.open) return;
            const id = selecionada.id, g = geracao, numero = selecaoNumero;
            const leitura = {}; lendo = leitura;
            const url = new URL(dados.historico.replace('__ID__', id), window.location.href);
            if (itens.size && !inicial) url.searchParams.set(anteriores ? 'antes' : 'depois', anteriores ? Math.min(...itens.keys()) : Math.max(...itens.keys()));
            try {
                const resposta = await api(url.href);
                if (g !== geracao || numero !== selecaoNumero || bloqueado || selecionada?.id !== id) return;
                destino(resposta.conversa); prazo(resposta);
                if (inicial) { itens.clear(); el('itens').replaceChildren(); }
                adicionarMensagens(resposta.mensagens, anteriores);
                if (inicial || anteriores) el('anteriores').hidden = !resposta.mais;
                if (inicial) el('historico').scrollTop = el('historico').scrollHeight;
                el('enviar').disabled = false;
                await marcarLidas();
                if (resposta.mais && !anteriores && !inicial) setTimeout(() => carregarHistorico(), 50);
            } catch (erro) { tratarErro(erro); }
            finally { if (lendo === leitura) lendo = false; }
        }
        function trocarAba(nova) {
            if (enviando || (nova === 'email' && !selecionada?.email)) return;
            salvarRascunho(); aba = nova;
            el('interno-area').hidden = nova !== 'interno'; el('email-form').hidden = nova !== 'email';
            ['interno', 'email'].forEach(a => { el('aba-' + a).classList.toggle('ativo', a === nova); el('aba-' + a).setAttribute('aria-pressed', String(a === nova)); });
            if (nova === 'interno') { el('historico').scrollTop = el('historico').scrollHeight; marcarLidas(); }
        }
        async function abrirConversa(alunoId = null) {
            if (enviando || bloqueado) return;
            salvarRascunho(); const numero = ++selecaoNumero, g = geracao;
            el('enviar').disabled = true; aviso(el('aviso'), 'Abrindo conversa…');
            try {
                const resposta = await api(dados.abrir, 'POST', alunoId ? {aluno_id: alunoId} : {});
                if (numero !== selecaoNumero || g !== geracao) return;
                destino(resposta.conversa); itens.clear(); marcada = 0; lendo = false;
                if (!rascunhos.has(selecionada.id)) rascunhos.set(selecionada.id, {texto: '', arquivos: [], chave: resposta.nova_chave,
                    incerto: false, assunto: 'Contato pelo sistema de agendamento', emailTexto: ''});
                el('vazio').hidden = true; el('conversa-area').hidden = false; modal.classList.add('com-conversa');
                restaurarRascunho(); trocarAba('interno'); aviso(el('aviso'), '');
                await carregarHistorico(false, true);
            } catch (erro) { tratarErro(erro); }
        }
        async function ativar() {
            bloqueado = false; el('area').hidden = false;
            if (dados.psicologa) { el('bloqueio').hidden = true; await carregarContatos(); if (selecionada) await abrirConversa(selecionada.aluno_id); }
            else await abrirConversa();
        }
        async function abrirModal(botao) {
            const g = geracao;
            gatilho = botao; if (!modal.open) modal.showModal();
            botoesAbrir.forEach(b => b.setAttribute('aria-expanded', 'true'));
            aviso(el('aviso'), 'Conferindo seu acesso…');
            try {
                const resposta = await atualizarResumo();
                if (g !== geracao && !bloqueado) return;
                if (dados.psicologa && !resposta.desbloqueado) { bloquear(); aviso(el('aviso'), ''); el('senha').focus(); }
                else { aviso(el('aviso'), ''); await ativar(); }
            } catch (erro) { tratarErro(erro); }
        }
        function fecharModal() {
            if (enviando) return;
            salvarRascunho(); modal.close();
        }
        botoesAbrir.forEach(botao => botao.addEventListener('click', () => abrirModal(botao)));
        el('fechar').addEventListener('click', fecharModal);
        modal.addEventListener('cancel', event => { event.preventDefault(); fecharModal(); });
        modal.addEventListener('close', () => {
            salvarRascunho(); botoesAbrir.forEach(b => b.setAttribute('aria-expanded', 'false')); gatilho?.focus();
        });
        window.addEventListener('beforeunload', event => { if (temRascunho()) { event.preventDefault(); event.returnValue = ''; } });
        ['pointerdown', 'keydown', 'input', 'scroll'].forEach(nome => document.addEventListener(nome, event => {
            if (event.isTrusted && modal.open && document.visibilityState === 'visible') atividade = performance.now();
        }, {passive: true, capture: true}));
        async function renovarSePreciso() {
            if (!dados.psicologa || bloqueado || !modal.open || renovando || atividade <= atividadeRenovada || performance.now() - atividade > 60000) return;
            const marca = atividade, g = geracao; renovando = true;
            try { const resposta = await api(dados.renovar, 'POST', {}); if (g === geracao) { prazo(resposta); atividadeRenovada = marca; } }
            catch (erro) { if (erro.geracao === geracao && [401,403,419,423].includes(erro.status)) { bloquear(); tratarErro(erro); } }
            finally { renovando = false; }
        }
        if (dados.psicologa) {
            el('senha-form').addEventListener('submit', async event => {
                event.preventDefault(); const botao = el('senha-enviar'); if (botao.disabled) return;
                botao.disabled = true; botao.textContent = 'Desbloqueando…';
                try {
                    const resposta = await api(dados.desbloquear, 'POST', {senha: el('senha').value});
                    csrf(resposta.csrf); geracao++; prazo(resposta); badge(resposta); atividadeRenovada = atividade;
                    aviso(el('aviso'), ''); await ativar();
                } catch (erro) { tratarErro(erro); }
                finally { el('senha').value = ''; botao.disabled = false; botao.textContent = 'Desbloquear conversas'; }
            });
            ['conversas', 'novo'].forEach(m => el('modo-' + m).addEventListener('click', () => {
                if (enviando) return;
                modo = m; ['conversas', 'novo'].forEach(t => { el('modo-' + t).classList.toggle('ativo', t === m); el('modo-' + t).setAttribute('aria-pressed', String(t === m)); });
                carregarContatos(1);
            }));
            ['nome', 'turma', 'matricula'].forEach(campo => el(campo).addEventListener('input', () => {
                if (enviando) return; ++consultaLista; clearTimeout(debounce);
                const opcao = campo === 'nome' ? listaOpcoes.get(el('nome').value) : null;
                if (opcao) { el('nome').value = opcao.nome; abrirConversa(Number(opcao.aluno_id ?? opcao.id)); return; }
                debounce = setTimeout(() => carregarContatos(1), 300);
            }));
            el('contatos-anterior').addEventListener('click', () => carregarContatos(paginaContatos - 1));
            el('contatos-proxima').addEventListener('click', () => carregarContatos(paginaContatos + 1));
            el('voltar-contatos').addEventListener('click', () => { if (!enviando) { salvarRascunho(); modal.classList.remove('com-conversa'); } });
        }
        el('texto').addEventListener('input', () => { salvarRascunho(); el('texto-contador').textContent = el('texto').value.length.toLocaleString('pt-BR') + ' / 10.000'; });
        ['email-texto','email-assunto'].forEach(id => el(id).addEventListener('input', salvarRascunho));
        el('anexar').addEventListener('click', () => { if (!enviando && !rascunho()?.incerto) el('arquivos').click(); });
        el('arquivos').addEventListener('change', () => {
            const r = rascunho(); if (!r || enviando || r.incerto) return;
            const arquivos = [...r.arquivos, ...Array.from(el('arquivos').files || [])], erro = validarArquivos(arquivos, dados.arquivosMaximo, dados.arquivoMaximo);
            el('arquivos').value = ''; if (erro) { aviso(el('envio-aviso'), erro, true); return; }
            r.arquivos = arquivos; restaurarRascunho();
        });
        el('envio-form').addEventListener('submit', async event => {
            event.preventDefault(); if (!selecionada || enviando || bloqueado) return;
            salvarRascunho(); const r = rascunho(), id = selecionada.id, g = geracao;
            if (!r.texto.trim() && !r.arquivos.length) { aviso(el('envio-aviso'), 'Escreva uma mensagem ou selecione um arquivo.', true); return; }
            const erroArquivo = validarArquivos(r.arquivos, dados.arquivosMaximo, dados.arquivoMaximo);
            if (erroArquivo) { aviso(el('envio-aviso'), erroArquivo, true); return; }
            enviando = true; el('enviar').disabled = true; el('enviar').textContent = 'Enviando…'; el('fechar').disabled = true;
            el('selecionados').querySelectorAll('button').forEach(b => { b.disabled = true; });
            el('texto').readOnly = true; el('anexar').disabled = true; aviso(el('envio-aviso'), '');
            try {
                if (!r.chave) { const abertura = await api(dados.abrir, 'POST', dados.psicologa ? {aluno_id: selecionada.aluno_id} : {}); r.chave = abertura.nova_chave; }
                const corpo = new FormData(); corpo.append('texto', r.texto); corpo.append('chave_envio', r.chave);
                r.arquivos.forEach(arquivo => corpo.append('arquivos[]', arquivo, arquivo.name));
                const resposta = await api(dados.enviar.replace('__ID__', id), 'POST', corpo, 120000);
                if (resposta.status !== 'success' || !resposta.mensagem || !resposta.nova_chave) throw new Error('envio_incerto');
                r.texto = ''; r.arquivos = []; r.incerto = false; r.chave = resposta.nova_chave;
                if (g === geracao && selecionada?.id === id) { adicionarMensagens([resposta.mensagem]); restaurarRascunho(); badge(resposta); }
            } catch (erro) {
                r.incerto = ![409,413,422,429].includes(erro.status);
                if (erro.status === 409) r.chave = null;
                tratarErro(erro, el('envio-aviso'));
            } finally {
                enviando = false; el('enviar').disabled = false; el('fechar').disabled = false;
                if (!bloqueado && selecionada?.id === id) restaurarRascunho(false);
                if (!bloqueado && selecionada?.id === id && r.incerto) aviso(el('envio-aviso'), 'O envio ficou sem confirmação. Seu rascunho foi mantido; use Tentar novamente para conferir sem duplicar a mensagem.', true);
                if (!bloqueado) { await carregarHistorico(); await marcarLidas(); if (dados.psicologa) carregarContatos(paginaContatos, true); }
            }
        });
        el('anteriores').addEventListener('click', () => carregarHistorico(true));
        el('historico').addEventListener('scroll', () => marcarLidas(), {passive: true});
        el('aba-interno').addEventListener('click', () => trocarAba('interno'));
        el('aba-email').addEventListener('click', () => trocarAba('email'));
        function abrirEmail(comTexto = true) {
            if (!selecionada?.email || bloqueado) return;
            salvarRascunho(); const r = rascunho(), url = montarEmail(selecionada.email, r.assunto.trim(), r.emailTexto, comTexto);
            if (comTexto && url.length > 1800) {
                aviso(el('email-aviso'), 'Seu texto é longo para o atalho de e-mail. Copie o texto e use Abrir e-mail sem o texto para colá-lo no seu aplicativo.');
                el('email-sem-texto').hidden = false; return;
            }
            const link = document.createElement('a'); link.href = url; link.click();
            aviso(el('email-aviso'), 'O e-mail foi preparado para seu aplicativo. Confira e envie por lá.');
        }
        el('email-form').addEventListener('submit', event => { event.preventDefault(); abrirEmail(); });
        el('email-sem-texto').addEventListener('click', () => abrirEmail(false));
        el('email-copiar').addEventListener('click', async () => {
            const texto = el('email-texto').value;
            try {
                if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(texto);
                else { el('email-texto').focus(); el('email-texto').select(); if (!document.execCommand('copy')) throw new Error('copiar'); }
                aviso(el('email-aviso'), 'Texto copiado. Cole no seu aplicativo de e-mail.');
            } catch { aviso(el('email-aviso'), 'Selecione o texto e copie manualmente para seu aplicativo de e-mail.'); el('email-texto').focus(); el('email-texto').select(); }
        });
        async function acompanhar() {
            if (document.visibilityState !== 'visible') return;
            await renovarSePreciso();
            try {
                await atualizarResumo();
                if (modal.open && !bloqueado && !enviando) {
                    await carregarHistorico();
                    if (dados.psicologa) carregarContatos(paginaContatos, true);
                }
            } catch (erro) {
                if (dados.psicologa && modal.open && expira && relogio() >= expira) bloquear();
                if ([401,403,419,423].includes(erro.status)) tratarErro(erro);
            }
        }
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') acompanhar(); });
        function ajustarTela() {
            if (!window.visualViewport) return;
            if (!window.matchMedia?.('(max-width: 767px)').matches) {
                ['height','top','bottom','margin'].forEach(p => modal.style.removeProperty(p));
                modal.classList.remove('teclado-aberto'); return;
            }
            const viewport = window.visualViewport;
            modal.style.height = Math.max(200, viewport.height - 12) + 'px';
            modal.style.top = (viewport.offsetTop + 6) + 'px'; modal.style.bottom = 'auto'; modal.style.margin = '0 auto';
            modal.classList.toggle('teclado-aberto', viewport.height < window.innerHeight * .8);
        }
        window.visualViewport?.addEventListener('resize', ajustarTela);
        window.visualViewport?.addEventListener('scroll', ajustarTela);
        ajustarTela();
        window.addEventListener('pageshow', () => acompanhar());
        setInterval(acompanhar, Math.max(15, Number(dados.intervalo) || 30) * 1000);
        acompanhar();
    });
})();
