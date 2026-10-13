<button type="button" data-senha-olho="{{ $campoSenha }}" aria-controls="{{ $campoSenha }}"
    aria-label="Mostrar senha" aria-pressed="false" title="Mostrar senha"
    onclick="alternarSenhaRestrita(this)"
    style="position: absolute; right: 3px; top: 50%; transform: translateY(-50%); width: 42px; height: 40px; min-width: 42px; display: flex; align-items: center; justify-content: center; margin: 0; padding: 0; border: 0; border-radius: 5px; background: transparent; color: #52665b; cursor: pointer;">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true" focusable="false">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path>
        <circle cx="12" cy="12" r="3"></circle>
        <path data-senha-risco d="M3 3l18 18"></path>
    </svg>
</button>
@once
<script>
window.alternarSenhaRestrita = function (botao) {
    const campo = document.getElementById(botao.dataset.senhaOlho);
    if (!campo || campo.disabled) return;
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    const rotulo = mostrar ? 'Ocultar senha' : 'Mostrar senha';
    botao.setAttribute('aria-label', rotulo);
    botao.setAttribute('title', rotulo);
    botao.setAttribute('aria-pressed', String(mostrar));
    botao.querySelector('[data-senha-risco]').style.display = mostrar ? 'none' : '';
    campo.focus();
};
</script>
@endonce
