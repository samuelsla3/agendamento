(() => {
    'use strict';
    const estados = new Map();
    const formularios = new Map();
    function uuid() {
        if (window.crypto.randomUUID) return window.crypto.randomUUID();
        const bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 15) | 64;
        bytes[8] = (bytes[8] & 63) | 128;
        const h = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
        return `${h.slice(0,8)}-${h.slice(8,12)}-${h.slice(12,16)}-${h.slice(16,20)}-${h.slice(20)}`;
    }
    function bloquear(elementos, texto) {
        const anteriores = Array.from(elementos).map(el => ({el, disabled: el.disabled, html: el.innerHTML,
            value: el.value, cursor: el.style.cursor, aria: el.getAttribute('aria-busy')}));
        anteriores.forEach(({el, disabled}) => {
            if (disabled) return;
            el.disabled = true;
            el.setAttribute('aria-busy', 'true');
            el.style.cursor = 'wait';
            if (el.tagName === 'INPUT') el.value = texto;
            else el.textContent = texto;
        });
        return () => anteriores.forEach(({el, disabled, html, value, cursor, aria}) => {
            el.disabled = disabled;
            if (el.tagName === 'INPUT') el.value = value;
            else el.innerHTML = html;
            el.style.cursor = cursor;
            if (aria === null) el.removeAttribute('aria-busy');
            else el.setAttribute('aria-busy', aria);
        });
    }
    function mensagemErro(xhr) {
        if (xhr.status === 419) return 'Sua sessão expirou. Atualize a página antes de continuar.';
        if (xhr.status === 0 || xhr.status >= 500) return 'Não foi possível confirmar o resultado. Confira a agenda ou tente novamente sem alterar os dados; a mesma tentativa será reutilizada.';
        return xhr.responseJSON?.message || 'Não foi possível concluir. Atualize a página e verifique os dados.';
    }
    function ajax(op) {
        const grupo = op.grupo || 'agenda';
        let estado = estados.get(grupo);
        if (estado?.busy) return null;
        const fingerprint = JSON.stringify([op.url, op.data]);
        if (estado?.incerto && estado.fingerprint !== fingerprint) {
            (op.erro || window.alert)('A tentativa anterior ficou sem confirmação. Confira o resultado antes de enviar outros dados.');
            return null;
        }
        if (!estado || estado.fingerprint !== fingerprint) estado = {key: uuid(), fingerprint};
        estado.busy = true;
        estados.set(grupo, estado);
        const restaurar = bloquear(document.querySelectorAll(op.botoes || 'button[type="submit"]'), op.texto || 'Processando…');
        let navegando = false;
        let dados = typeof op.data === 'string' ? op.data : {...op.data};
        if (!op.leitura) {
            if (typeof dados === 'string') dados += '&_operation_id=' + encodeURIComponent(estado.key);
            else dados._operation_id = estado.key;
        }
        return window.jQuery.ajax({
            url: op.url, type: 'POST',
            headers: {'X-CSRF-TOKEN': LaravelConfig.csrfToken, 'Accept': 'application/json'},
            data: op.json ? JSON.stringify(dados) : dados,
            contentType: op.json ? 'application/json' : 'application/x-www-form-urlencoded; charset=UTF-8',
            success(response) {
                estado.incerto = false;
                if (response?.status === 'error') {
                    estados.delete(grupo);
                    (op.erro || window.alert)(response.message);
                    return;
                }
                if (op.navegar) navegando = true;
                op.sucesso?.(response);
                if (!navegando) estados.delete(grupo);
            },
            error(xhr) {
                estado.incerto = xhr.status === 0 || xhr.status >= 500;
                if (!estado.incerto) estados.delete(grupo);
                (op.erro || window.alert)(mensagemErro(xhr));
            },
            complete() {
                if (!navegando) { estado.busy = false; restaurar(); }
            }
        });
    }
    // Bubble no window: respeita validação nativa, onsubmit/confirm e handlers AJAX.
    window.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.method.toLowerCase() === 'get') return;
        if (formularios.has(form)) { event.preventDefault(); return; }
        const texto = form.dataset.textoEnvio || 'Processando…';
        formularios.set(form, bloquear(form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'), texto));
        form.setAttribute('aria-busy', 'true');
        let aviso = form.querySelector('[data-andamento-envio]');
        if (!aviso) {
            aviso = document.createElement('p');
            aviso.dataset.andamentoEnvio = '';
            aviso.setAttribute('role', 'status');
            form.append(aviso);
        }
        aviso.textContent = texto;
    });
    // Restaurar a interface ao voltar pelo histórico; não trocar a chave da operação.
    window.addEventListener('pageshow', () => {
        formularios.forEach((restaurar, form) => {
            restaurar(); form.removeAttribute('aria-busy');
            form.querySelector('[data-andamento-envio]')?.remove();
        });
        formularios.clear();
    });
    window.Operacoes = {ajax};
})();
