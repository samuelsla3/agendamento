<dialog id="consulta-alunos-modal" class="consulta-alunos" data-agenda="{{ route('psicologa.index') }}" data-lista="{{ route('alunos.consultar') }}" data-detalhe="{{ route('alunos.consultar.detalhe', ['aluno' => '__ID__']) }}">
    <header class="consulta-cabecalho">
        <div><h2>Consultar alunos</h2><p>Atendimentos, agendamentos e atalhos por aluno.</p></div>
        <button type="button" class="btn btn-secondary" id="consulta-fechar">Fechar ×</button>
    </header>
    <form id="consulta-busca-form" class="consulta-busca">
        <label for="consulta-busca">Nome, matrícula ou turma</label>
        <div><input id="consulta-busca" type="search" maxlength="100" placeholder="Pesquisar aluno…" autocomplete="off"><button class="btn btn-primary" type="submit">Pesquisar</button></div>
    </form>
    <p id="consulta-aviso" role="status" aria-live="polite"></p>
    <div class="consulta-colunas">
        <section class="consulta-lista-area" aria-label="Alunos encontrados">
            <p id="consulta-total"></p><div id="consulta-lista"></div>
            <nav class="consulta-paginacao" aria-label="Páginas de alunos"><button type="button" id="consulta-anterior">Anterior</button><span id="consulta-pagina"></span><button type="button" id="consulta-proxima">Próxima</button></nav>
        </section>
        <section id="consulta-detalhe" class="consulta-detalhe" aria-label="Resumo do aluno" aria-live="polite"><p>Selecione um aluno para consultar os atendimentos.</p></section>
    </div>
</dialog>
