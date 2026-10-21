<footer class="aluno-rodape" aria-label="Informações institucionais">
    <div class="aluno-rodape-conteudo">
        <div class="aluno-rodape-principal">
            <div class="aluno-rodape-marca">
                <img class="aluno-rodape-logo"
                    src="{{ asset('images/ifba-logo-branca.png') }}"
                    alt="IFBA" width="44" height="44" loading="lazy">
                <div class="aluno-rodape-identificacao">
                    <p class="aluno-rodape-titulo">Agendamento Psicológico</p>
                    <p class="aluno-rodape-descricao">IFBA - Instituto Federal de Educação, Ciência e Tecnologia da Bahia</p>
                </div>
            </div>

            @auth
            @else
            @endauth
        </div>
        <div class="aluno-rodape-base">
            <p>Agendamento Psicológico - IFBA Campus Seabra &copy; {{ date('Y') }}</p>
        </div>
    </div>
</footer>
