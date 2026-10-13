(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const corpo = document.body;
        if (!corpo.dataset.prontuarioStatus) return;
        let ocupado = false, encerrado = false, atividade = 0, ultimaEnviada = 0;
        let base = Number(corpo.dataset.prontuarioAgora) * 1000, marco = performance.now();
        let expira = Number(corpo.dataset.prontuarioExpira) * 1000;
        const agora = () => base + performance.now() - marco;
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
        ['keydown', 'pointerdown', 'input', 'scroll'].forEach(nome => {
            document.addEventListener(nome, evento => {
                if (evento.isTrusted && document.visibilityState === 'visible') atividade = performance.now() + 1;
            }, {passive: true, capture: true});
        });
        function bloquear(login = false) {
            if (encerrado) return;
            encerrado = true;
            // Retira dados clínicos do DOM antes de tentar navegar, inclusive offline.
            const mensagem = document.createElement('p');
            mensagem.textContent = 'O prontuário foi bloqueado. Entre novamente para continuar.';
            mensagem.setAttribute('role', 'alert');
            corpo.replaceChildren(mensagem);
            window.location.replace(login ? corpo.dataset.prontuarioLogin : corpo.dataset.prontuarioAgenda);
        }
        async function verificar() {
            if (ocupado || encerrado) return;
            ocupado = true;
            const renovar = document.visibilityState === 'visible' && atividade > ultimaEnviada
                && performance.now() - atividade < 60000;
            const atividadeEnviada = atividade;
            const abortar = new AbortController();
            const timeout = setTimeout(() => abortar.abort(), 5000);
            try {
                const resposta = await fetch(renovar ? corpo.dataset.prontuarioRenovar : corpo.dataset.prontuarioStatus,
                    {method: renovar ? 'POST' : 'GET', cache: 'no-store', credentials: 'same-origin', signal: abortar.signal,
                        headers: {Accept: 'application/json', ...(renovar ? {'X-CSRF-TOKEN': csrf()} : {})}});
                if (resposta.redirected || [401, 403, 419].includes(resposta.status)) {
                    bloquear(resposta.status === 401 || resposta.status === 419); return;
                }
                if (!resposta.ok) throw new Error('consulta');
                const dados = await resposta.json();
                if (dados.valido !== true || !Number.isFinite(Number(dados.agora))
                    || !Number.isFinite(Number(dados.expira_em)) || Number(dados.expira_em) <= Number(dados.agora)) {
                    bloquear(); return;
                }
                base = Number(dados.agora) * 1000; marco = performance.now();
                expira = Number(dados.expira_em) * 1000;
                if (renovar) ultimaEnviada = atividadeEnviada;
            } catch {
                if (!Number.isFinite(expira) || agora() >= expira) bloquear();
            } finally { clearTimeout(timeout); ocupado = false; }
        }
        document.addEventListener('visibilitychange', verificar);
        window.addEventListener('pageshow', verificar);
        setInterval(verificar, 30000);
        verificar();
    });
})();
