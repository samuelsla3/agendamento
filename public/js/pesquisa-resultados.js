document.addEventListener('submit', evento => {
    const form = evento.target;
    if (!form.matches('[data-excluir-resposta]')) return;
    if (!confirm('Excluir esta resposta da pesquisa? Essa ação não pode ser desfeita.')) { evento.preventDefault(); return; }
    const botao = form.querySelector('button[type="submit"]'); botao.disabled = true; botao.textContent = 'Excluindo…';
});
