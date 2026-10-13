<section class="apresentacao-publica" aria-labelledby="acolhimento-titulo">
    <div class="apresentacao-sobre" data-revelar>
        <div class="apresentacao-texto">
            <p class="apresentacao-etiqueta">IFBA · Campus Seabra</p>
            <h2 id="acolhimento-titulo">Um espaço de escuta e acolhimento</h2>
            <p>O atendimento psicológico é um espaço para conversar sobre o que você está vivendo, suas dificuldades e seus sentimentos.</p>
            <p>Este site aproxima os estudantes do serviço de Psicologia do campus e facilita a organização dos atendimentos.</p>
            <div class="apresentacao-destaque">
                <h3>Como começar?</h3>
                <p>Confira os horários disponíveis no calendário. Para agendar, entre com sua matrícula e senha do SUAP e escolha um horário.</p>
            </div>
            <a class="apresentacao-entrar" href="{{ route('login') }}">Entrar com SUAP <span aria-hidden="true">→</span></a>
        </div>
        <figure class="apresentacao-ilustracao">
            <img src="{{ asset('images/acolhimento-psicologico.jpg') }}" alt="Ilustração de uma conversa em um atendimento psicológico." width="1000" height="556" loading="lazy">
            <figcaption><a href="https://www.magnific.com/br/vetores-gratis/psicologo-visitante-da-mulher_7416587.htm" target="_blank" rel="noopener noreferrer"></a></figcaption>
        </figure>
    </div>
    <div class="apresentacao-funcoes" aria-labelledby="funcionalidades-titulo">
        <div data-revelar>
            <p class="apresentacao-etiqueta">Tudo em um só lugar</p>
            <h2 id="funcionalidades-titulo">O que você pode fazer por aqui</h2>
        </div>
        <div class="apresentacao-grade">
            <article class="apresentacao-cartao" data-revelar>
                <h3>Agendar seu atendimento</h3>
                <p>Veja os horários disponíveis e faça sua reserva. Se não puder comparecer, cancele pelo site e informe o motivo.</p>
            </article>
            <article class="apresentacao-cartao" data-revelar>
                <h3>Acompanhar seus horários</h3>
                <p>Consulte seus agendamentos e acompanhe o histórico de atendimentos, faltas e cancelamentos.</p>
            </article>
            <article class="apresentacao-cartao" data-revelar>
                <h3>Falar com a psicóloga</h3>
                <p>Depois de entrar, use a área de mensagens para conversar com a psicóloga e esclarecer dúvidas sobre o atendimento.</p>
            </article>
        </div>
    </div>
</section>
