document.addEventListener('DOMContentLoaded', function () {
    const botao = document.getElementById('abrir-informacoes-atendimento');
    const modal = document.getElementById('informacoes-atendimento-modal');
    if (!botao || !modal) return;

    botao.addEventListener('click', function () {
        if (modal.open) return;
        modal.showModal();
        botao.setAttribute('aria-expanded', 'true');
    });

    modal.querySelector('[data-fechar-informacoes]').addEventListener('click', function () {
        modal.close();
    });

    modal.addEventListener('click', function (event) {
        if (event.target !== modal) return;
        const area = modal.getBoundingClientRect();
        if (event.clientX < area.left || event.clientX > area.right
            || event.clientY < area.top || event.clientY > area.bottom) {
            modal.close();
        }
    });

    // Inclui o fechamento por Escape, tratado pelo dialog nativo.
    modal.addEventListener('close', function () {
        botao.setAttribute('aria-expanded', 'false');
        botao.focus();
    });
});
