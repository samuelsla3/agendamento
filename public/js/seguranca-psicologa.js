(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-mostrar-senha]').forEach(botao => {
            botao.addEventListener('click', () => {
                const campo = document.getElementById(botao.dataset.mostrarSenha);
                if (!campo) return;
                const visivel = campo.type === 'password';
                campo.type = visivel ? 'text' : 'password';
                botao.textContent = visivel ? 'Ocultar' : 'Mostrar';
                botao.setAttribute('aria-pressed', String(visivel));
            });
        });
    });
})();
