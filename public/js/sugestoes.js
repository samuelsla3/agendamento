(() => {
    'use strict';

    async function consultar(url, opcoes = {}) {
        const controle = new AbortController();
        const prazo = setTimeout(() => controle.abort(), 20000);
        try {
            const resposta = await fetch(url, {credentials: 'same-origin', cache: 'no-store',
                ...opcoes, signal: controle.signal,
                headers: {Accept: 'application/json', ...opcoes.headers}});
            if (resposta.redirected || [401, 403, 419].includes(resposta.status)) {
                const erro = new Error('Sua sessão precisa ser renovada. Atualize a página e faça login novamente.');
                erro.status = resposta.status === 200 ? 401 : resposta.status;
                throw erro;
            }
            const dados = await resposta.json();
            if (!resposta.ok) {
                const erro = new Error(dados.message || 'Não foi possível concluir. Tente novamente.');
                erro.status = resposta.status; erro.dados = dados; throw erro;
            }
            return dados;
        } finally { clearTimeout(prazo); }
    }

    function iniciarAluno() {
        const modal = document.getElementById('sugestoes-modal');
        if (!modal) return;
        const el = id => document.getElementById(id);
        const abrir = el('abrir-sugestao'), form = el('sugestoes-form'), campo = el('sugestoes-texto');
        const botoesAbrir = [abrir, ...document.querySelectorAll('[data-abrir-sugestao]')];
        let origemAbertura = abrir;
        const enviar = el('enviar-sugestao'), chave = el('sugestoes-chave'), retorno = el('sugestoes-retorno');
        const fechar = [el('fechar-sugestao'), el('cancelar-sugestao')];
        let ocupado = false, incerto = false, ultimoTexto = null;
        const textoBotao = enviar.textContent;
        function contar() { el('sugestoes-contador').textContent = campo.value.length.toLocaleString('pt-BR') + ' / 10.000 caracteres'; }
        function mensagem(texto, erro = false) {
            retorno.hidden = false; retorno.textContent = texto;
            retorno.classList.toggle('sugestoes-retorno--erro', erro);
        }
        function mostrar() {
            if (!modal.open) modal.showModal();
            botoesAbrir.forEach(botao => botao.setAttribute('aria-expanded', 'true')); campo.focus();
        }
        botoesAbrir.forEach(botao => botao.addEventListener('click', () => {
            origemAbertura = botao;
            mostrar();
        }));
        fechar.forEach(botao => botao.addEventListener('click', () => { if (!ocupado) modal.close(); }));
        modal.addEventListener('cancel', event => { if (ocupado) event.preventDefault(); });
        modal.addEventListener('close', () => {
            botoesAbrir.forEach(botao => botao.setAttribute('aria-expanded', 'false'));
            origemAbertura.focus();
        });
        campo.addEventListener('input', contar);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (ocupado) return;
            const texto = campo.value.trim();
            if (texto.length < 3 || !form.reportValidity()) { mensagem('Escreva pelo menos 3 caracteres antes de enviar.', true); return; }
            if (incerto && texto !== ultimoTexto) {
                mensagem('A tentativa anterior ficou sem confirmação. Envie novamente o mesmo texto para conferir o resultado.', true); return;
            }
            ocupado = true; ultimoTexto = texto;
            enviar.disabled = true; enviar.textContent = 'Enviando…'; enviar.setAttribute('aria-busy', 'true');
            form.setAttribute('aria-busy', 'true'); campo.readOnly = true;
            fechar.forEach(botao => { botao.disabled = true; }); retorno.hidden = true;
            try {
                const dados = await consultar(form.action, {method: 'POST', headers: {
                    'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                }, body: JSON.stringify({sugestao: texto, chave_envio: chave.value})});
                if (dados.status !== 'success' || !dados.nova_chave) throw new Error('resposta_invalida');
                incerto = false; campo.value = ''; chave.value = dados.nova_chave;
                contar(); mensagem(dados.message);
            } catch (erro) {
                incerto = !erro.status || erro.status >= 500;
                if (erro.status === 409 && erro.dados?.nova_chave) chave.value = erro.dados.nova_chave;
                const mensagemErro = [401, 403, 419].includes(erro.status)
                    ? 'Sua sessão precisa ser renovada. Copie seu texto antes de atualizar a página e entrar novamente.' : erro.message;
                mensagem(incerto
                    ? 'Não foi possível confirmar o envio. Seu texto foi mantido; clique em enviar novamente para conferir sem duplicar a sugestão.'
                    : mensagemErro, true);
            } finally {
                ocupado = false; enviar.disabled = false; enviar.textContent = textoBotao;
                enviar.removeAttribute('aria-busy'); form.removeAttribute('aria-busy');
                campo.readOnly = incerto; fechar.forEach(botao => { botao.disabled = false; });
            }
        });
        contar();
        if (modal.dataset.abrir === '1') mostrar();
    }

    function iniciarPsicologa() {
        const painel = document.getElementById('sugestoes-painel');
        if (!painel) return;
        const el = id => document.getElementById(id);
        const anterior = el('sugestoes-anterior'), proxima = el('sugestoes-proxima');
        const lista = el('sugestoes-lista'), retorno = el('sugestoes-lista-retorno');
        let ocupado = false, pagina = 1, ultima = 1;
        async function carregar(numero, indicador = null) {
            if (ocupado) return;
            ocupado = true;
            const texto = indicador?.textContent;
            [anterior, proxima].forEach(botao => { botao.disabled = true; });
            painel.setAttribute('aria-busy', 'true');
            if (indicador) { indicador.textContent = 'Carregando…'; indicador.setAttribute('aria-busy', 'true'); }
            else el('sugestoes-total').textContent = 'Carregando…';
            retorno.hidden = true;
            try {
                const url = new URL(painel.dataset.listarUrl, window.location.href);
                url.searchParams.set('pagina', String(numero));
                const dados = await consultar(url.href);
                if (!Array.isArray(dados.sugestoes)) throw new Error('resposta_invalida');
                lista.replaceChildren();
                dados.sugestoes.forEach(sugestao => {
                    const item = document.createElement('article'), titulo = document.createElement('h3'), conteudo = document.createElement('p');
                    item.className = 'sugestoes-item';
                    titulo.textContent = 'Recebida em ' + sugestao.dia;
                    conteudo.textContent = sugestao.texto; // Nunca interpreta texto enviado como HTML.
                    item.append(titulo, conteudo); lista.append(item);
                });
                if (!dados.sugestoes.length) {
                    const vazio = document.createElement('p'); vazio.textContent = 'Nenhuma sugestão recebida ainda.'; lista.append(vazio);
                }
                pagina = Number(dados.pagina); ultima = Number(dados.ultima_pagina);
                el('sugestoes-total').textContent = Number(dados.total).toLocaleString('pt-BR') + (Number(dados.total) === 1 ? ' sugestão recebida' : ' sugestões recebidas');
                el('sugestoes-pagina').textContent = `Página ${pagina} de ${ultima}`;
                el('sugestoes-paginacao').hidden = ultima <= 1;
            } catch (erro) {
                if ([401, 403, 419].includes(erro.status)) lista.replaceChildren();
                el('sugestoes-total').textContent = '';
                retorno.hidden = false; retorno.classList.add('sugestoes-retorno--erro');
                retorno.textContent = erro.status ? erro.message : 'Não foi possível carregar as sugestões. Feche e abra a seção para tentar novamente.';
            } finally {
                ocupado = false; painel.removeAttribute('aria-busy');
                anterior.disabled = pagina <= 1; proxima.disabled = pagina >= ultima;
                if (indicador) { indicador.textContent = texto; indicador.removeAttribute('aria-busy'); }
            }
        }
        painel.addEventListener('toggle', () => { if (painel.open) carregar(1); });
        anterior.addEventListener('click', () => carregar(pagina - 1, anterior));
        proxima.addEventListener('click', () => carregar(pagina + 1, proxima));
        if (painel.open) carregar(1);
    }

    document.addEventListener('DOMContentLoaded', () => { iniciarAluno(); iniciarPsicologa(); });
})();
