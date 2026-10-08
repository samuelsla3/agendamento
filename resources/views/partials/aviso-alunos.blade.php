@if(($avisoPublico ?? null) !== null)
    <aside class="aviso-alunos" aria-label="Aviso do setor de Psicologia">
        <h2 class="aviso-alunos__titulo">Aviso do setor de Psicologia</h2>
        <p class="aviso-alunos__texto">{{ $avisoPublico }}</p>
    </aside>
@endif