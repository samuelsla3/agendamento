(() => {
    'use strict';
    // Conta pontos de código Unicode, como a validação de strings do Laravel.
    const quantidade = texto => Array.from(texto).length;
    function atualizar(campo) {
        const limite = Number(campo.dataset.limiteCaracteres);
        const contador = document.getElementById(campo.dataset.contadorCaracteres);
        const total = quantidade(campo.value), passou = total > limite;
        if (contador) {
            contador.textContent = `${total.toLocaleString('pt-BR')} / ${limite.toLocaleString('pt-BR')} caracteres`
                + (passou ? ' — reduza o texto antes de salvar.' : '');
            contador.style.color = passou ? '#b42318' : '#56675a';
        }
        campo.setCustomValidity(passou ? `Este campo permite até ${limite.toLocaleString('pt-BR')} caracteres.` : '');
        campo.setAttribute('aria-invalid', String(passou));
    }
    window.AtualizarContadoresTexto = () => document.querySelectorAll('[data-limite-caracteres]').forEach(atualizar);
    document.addEventListener('DOMContentLoaded', () => {
        window.AtualizarContadoresTexto();
        ['input', 'change', 'focusin'].forEach(nome => document.addEventListener(nome, event => {
            if (event.target.matches('[data-limite-caracteres]')) atualizar(event.target);
        }));
        document.addEventListener('reset', () => setTimeout(window.AtualizarContadoresTexto, 0), true);
        // Captura antes dos handlers AJAX; não deixa enviar texto acima do limite nem o corta.
        document.addEventListener('submit', event => {
            for (const campo of event.target.querySelectorAll('[data-limite-caracteres]')) {
                atualizar(campo);
                if (!campo.checkValidity()) {
                    event.preventDefault(); event.stopImmediatePropagation(); campo.reportValidity(); return;
                }
            }
        }, true);
    });
})();
