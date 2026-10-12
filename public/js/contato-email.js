(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('contato-email-modal');
        if (!modal) return;
        let dados = JSON.parse(document.getElementById('contato-email-dados').textContent);
        let ocupado = false;
        let consulta = 0;
        let gatilho = null;
        let base = Date.parse(dados.agora);
        let marco = performance.now();
        const el = id => document.getElementById('contato-email-' + id);
        const agora = () => base + performance.now() - marco;
        const aviso = (texto, erro = false) => {
            el('retorno').hidden = false;
            el('retorno').textContent = texto;
            el('retorno').classList.toggle('contato-email-retorno--erro', erro);
        };
        const envioTexto = estado => ({
            enviado: 'Código enviado. Confira a caixa de entrada e o spam.',
            pendente: 'O código aguarda envio. Atualize a situação em instantes.',
            em_envio: 'O envio está em andamento. Atualize a situação em instantes.',
            falhou: 'Não foi possível enviar o código. Você pode solicitar outro código após a espera ou procurar o setor.',
            revisar: 'O envio ficou sem confirmação. Confira sua caixa de entrada e o spam antes de solicitar outro código.',
            descartado: 'Esse código não pode mais ser enviado. Solicite outro código.',
            sem_contato: 'Não foi possível enviar para esse endereço. Confira o e-mail informado.',
        })[estado] || '';
        function atualizarBotoes() {
            const faltam = dados.reenviar_em ? Math.max(0, Math.ceil((Date.parse(dados.reenviar_em) - agora()) / 1000)) : 0;
            el('enviar-btn').disabled = ocupado || faltam > 0;
            el('confirmar-btn').disabled = ocupado || !dados.codigo_ativo
                || (dados.expira_em && Date.parse(dados.expira_em) <= agora());
            el('espera').textContent = faltam ? `Você pode solicitar outro código em ${faltam}s.` : '';
        }
        function renderizar(novos) {
            const mudou = novos.versao !== dados.versao;
            dados = novos;
            base = Date.parse(dados.agora); marco = performance.now();
            el('atual').textContent = dados.confirmado ? `E-mail confirmado: ${dados.atual}` : 'Seu e-mail de contato ainda não está confirmado.';
            el('codigo-area').hidden = !dados.pendente;
            el('destino').textContent = dados.pendente ? `Código solicitado para ${dados.pendente}. Use o código mais recente.` : '';
            el('envio').textContent = dados.pendente && !dados.codigo_ativo
                ? 'O código expirou ou atingiu o limite de tentativas. Solicite outro.' : envioTexto(dados.envio);
            el('versao').value = String(dados.versao);
            el('enviar-btn').textContent = dados.pendente ? 'Enviar outro código' : 'Enviar código';
            if (mudou) el('codigo').value = '';
            atualizarBotoes();
        }
        async function atualizar() {
            const numero = ++consulta;
            try {
                const resposta = await fetch(modal.dataset.statusUrl, {cache: 'no-store', credentials: 'same-origin', headers: {Accept: 'application/json'}});
                if (resposta.redirected || [401, 403, 419].includes(resposta.status)) throw new Error('sessao');
                if (!resposta.ok) throw new Error('consulta');
                const novos = await resposta.json();
                if (numero === consulta) renderizar(novos);
            } catch (erro) {
                if (numero !== consulta) return;
                aviso(erro.message === 'sessao' ? 'Sua sessão expirou. Atualize a página e faça login novamente.' : 'Não foi possível atualizar a situação. Tente atualizar a página.', true);
            }
        }
        function abrir(botao) {
            gatilho = botao || null;
            el('endereco').value = dados.pendente || dados.atual || '';
            if (!modal.open) modal.showModal();
            if (gatilho) gatilho.setAttribute('aria-expanded', 'true');
            atualizar();
        }
        document.querySelectorAll('[data-abrir-contato-email]').forEach(botao => botao.addEventListener('click', () => abrir(botao)));
        ['fechar', 'depois'].forEach(id => el(id).addEventListener('click', () => modal.close()));
        modal.addEventListener('close', () => {
            gatilho?.setAttribute('aria-expanded', 'false');
            gatilho?.focus(); gatilho = null;
        });
        modal.addEventListener('click', event => {
            if (event.target !== modal) return;
            const rect = modal.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) modal.close();
        });
        el('atualizar').addEventListener('click', atualizar);
        function enviar(form, conteudo, confirmar) {
            if (ocupado) return;
            ++consulta; // Uma consulta antiga não pode restaurar a versão anterior do código.
            ocupado = true;
            const pedido = Operacoes.ajax({
                grupo: 'contato-email', url: form.action, data: conteudo,
                botoes: '#contato-email-enviar-btn, #contato-email-confirmar-btn',
                texto: confirmar ? 'Confirmando…' : 'Solicitando código…',
                sucesso(resposta) {
                    ocupado = false;
                    aviso(resposta.message, resposta.status !== 'success');
                    if (confirmar && resposta.status === 'success') {
                        window.location.reload();
                    } else {
                        atualizar();
                    }
                },
                erro(mensagem) {
                    ocupado = false; aviso(mensagem, true);
                    setTimeout(atualizarBotoes, 0);
                },
            });
            if (!pedido) { ocupado = false; atualizarBotoes(); }
        }
        el('enviar').addEventListener('submit', event => {
            event.preventDefault();
            if (dados.reenviar_em && Date.parse(dados.reenviar_em) > agora()) {
                aviso('Aguarde o intervalo indicado para solicitar outro código.', true); return;
            }
            const email = el('endereco').value.trim();
            if (!email || !el('endereco').checkValidity()) { el('endereco').reportValidity(); return; }
            enviar(el('enviar'), {email}, false);
        });
        el('confirmar').addEventListener('submit', event => {
            event.preventDefault();
            const codigo = el('codigo').value.trim();
            if (!/^[0-9]{6}$/.test(codigo)) { aviso('Informe o código de seis números.', true); return; }
            if (!dados.codigo_ativo || Date.parse(dados.expira_em) <= agora()) { aviso('O código expirou ou atingiu o limite. Solicite outro código.', true); return; }
            enviar(el('confirmar'), {codigo, versao: el('versao').value}, true);
        });
        renderizar(dados);
        setInterval(() => { if (modal.open) atualizarBotoes(); }, 1000);
        if (modal.dataset.abrir === '1') abrir();
    });
})();
