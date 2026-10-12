<section class="content-section config-card" id="sugestoes-alunos">
    <details class="sugestoes-painel" id="sugestoes-painel" data-listar-url="{{ route('sugestoes.listar') }}">
        <summary>Sugestões dos alunos</summary>
        <p>Sugestões anônimas para melhorar o sistema. Os registros não identificam o aluno que enviou.</p>
        <p id="sugestoes-total"></p>
        <p class="sugestoes-retorno" id="sugestoes-lista-retorno" role="status" hidden></p>
        <div id="sugestoes-lista"></div>
        <nav class="sugestoes-paginacao" aria-label="Páginas de sugestões" id="sugestoes-paginacao" hidden>
            <button type="button" class="sugestoes-botao-discreto" id="sugestoes-anterior">Anterior</button>
            <span id="sugestoes-pagina"></span>
            <button type="button" class="sugestoes-botao-discreto" id="sugestoes-proxima">Próxima</button>
        </nav>
    </details>
</section>
