@if(auth()->check() && in_array(auth()->user()->tipo, ['estudante', 'aluno', 'psicologa'], true))
    <button type="button" class="mensagens-acesso" data-abrir-mensagens aria-haspopup="dialog"
        aria-controls="mensagens-modal" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false">
            <path d="M4 4h16v12H8l-4 4V4Z"></path><path d="M8 8h8M8 12h5"></path>
        </svg>
        <span>{{ auth()->user()->tipo === 'psicologa' ? 'Mensagens' : 'Falar com a psicóloga' }}</span>
        <span class="mensagens-badge" data-mensagens-badge hidden aria-live="polite"></span>
    </button>
@endif
