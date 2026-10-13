<button type="button" class="senha-olho-login" data-senha-olho="{{ $campoSenha }}"
    aria-controls="{{ $campoSenha }}" aria-label="Mostrar senha" aria-pressed="false"
    title="Mostrar senha" onclick="alternarSenhaRestrita(this)">
    <i class="bi bi-eye-slash" aria-hidden="true"></i>
</button>
@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" referrerpolicy="no-referrer">
<style>
.senha-olho-login[data-senha-olho] {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    min-width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0;
    border: 0;
    border-radius: 4px;
    background: transparent;
    color: #6c757d;
    font-size: 1.2rem;
    cursor: pointer;
    user-select: none;
}
.senha-olho-login[data-senha-olho]:hover { color: #333; }
.senha-olho-login[data-senha-olho]:focus-visible { outline: 3px solid #0284c7; outline-offset: -3px; }
.senha-olho-login[data-senha-olho] .bi { line-height: 1; pointer-events: none; }
@media (max-width: 767px) {
    .senha-olho-login[data-senha-olho] { right: 2px; }
}
</style>
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
    const icone = botao.querySelector('.bi');
    icone.classList.remove(mostrar ? 'bi-eye-slash' : 'bi-eye');
    icone.classList.add(mostrar ? 'bi-eye' : 'bi-eye-slash');
    campo.focus();
};
</script>
@endonce
