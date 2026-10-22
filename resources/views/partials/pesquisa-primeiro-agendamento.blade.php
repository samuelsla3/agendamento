@php($pesquisaServico = \App\Services\PesquisaPrimeiroAgendamento::class)
@if(auth()->check() && in_array(auth()->user()->tipo, ['estudante', 'aluno'], true) && $pesquisaServico::ativa())
<div id="pesquisa-modal" class="pesquisa-modal" role="dialog" aria-modal="true" aria-labelledby="pesquisa-titulo" aria-describedby="pesquisa-introducao" hidden>
    <div class="pesquisa-conteudo">
        <button type="button" class="pesquisa-fechar" data-fechar-pesquisa aria-label="Fechar pesquisa">&times;</button>
        <p class="pesquisa-opcional">Participação opcional — seu agendamento já está confirmado.</p>
        <h2 id="pesquisa-titulo">Conte como foi usar o sistema</h2>
        <p id="pesquisa-introducao">Obrigado! Você foi um dos primeiros alunos a utilizar o sistema! Como este é o seu primeiro uso, gostaríamos muito que respondesse a uma pesquisa simples, rápida e anônima, que contribuirá muito para o nosso TCC.</p>
        <p>As respostas são registradas sem nome, matrícula ou e-mail. Você pode fechar esta janela sem responder.</p>
        <form id="pesquisa-form" method="POST" action="{{ route('pesquisa.responder') }}">
            @csrf
            @foreach($pesquisaServico::PERGUNTAS as $campo => $pergunta)
                <fieldset class="pesquisa-pergunta">
                    <legend>{{ $loop->iteration }}. {{ $pergunta['texto'] }}</legend>
                    <div class="pesquisa-opcoes">
                        @foreach($pergunta['opcoes'] as $valor => $rotulo)
                            <label><input type="radio" name="{{ $campo }}" value="{{ $valor }}" required><span>{{ $rotulo }}</span></label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <fieldset class="pesquisa-pergunta">
                <legend>6. Como você avalia o site/proposta em geral?</legend>
                <div class="pesquisa-estrelas" id="pesquisa-estrelas">
                    <label class="pesquisa-zero" title="Sem estrelas"><input type="radio" name="estrelas" value="0" required><span>0</span><span class="pesquisa-somente-leitor">Sem estrelas</span></label>
                    @foreach(range(1, 5) as $estrela)
                        <label class="pesquisa-estrela" data-estrela="{{ $estrela }}" title="{{ $estrela }} {{ $estrela === 1 ? 'estrela' : 'estrelas' }}">
                            <input type="radio" name="estrelas" value="{{ $estrela }}" required>
                            <span aria-hidden="true">★</span><span class="pesquisa-somente-leitor">{{ $estrela }} {{ $estrela === 1 ? 'estrela' : 'estrelas' }}</span>
                        </label>
                    @endforeach
                </div>
                <p id="pesquisa-nota" aria-live="polite">Escolha de 0 a 5 estrelas.</p>
            </fieldset>
            <div class="pesquisa-pergunta">
                <label for="pesquisa-sugestao"><strong>7. O que poderia melhorar? <span>(opcional)</span></strong></label>
                <textarea id="pesquisa-sugestao" name="sugestao" rows="3" maxlength="2000" aria-describedby="pesquisa-privacidade"></textarea>
                <p id="pesquisa-privacidade">Até 2.000 caracteres. Para preservar seu anonimato, não informe nome, matrícula, e-mail nem informações sobre atendimentos.</p>
            </div>
            <p id="pesquisa-erro" class="pesquisa-erro" role="alert" hidden></p>
            <div class="pesquisa-acoes">
                <button type="submit" class="btn btn-primary">Enviar pesquisa</button>
                <button type="button" class="btn btn-info" data-fechar-pesquisa>Agora não</button>
            </div>
        </form>
        <div id="pesquisa-sucesso" hidden>
            <p role="status" tabindex="-1" id="pesquisa-agradecimento">{{ $pesquisaServico::AGRADECIMENTO }}</p>
            <button type="button" class="btn btn-primary" data-fechar-pesquisa>Voltar ao site</button>
        </div>
    </div>
</div>
<script>
window.PesquisaConfig = {
    csrf: @json(csrf_token()),
    agora: @json(now()->toIso8601String()),
    fim: @json(\Carbon\Carbon::parse($pesquisaServico::FIM, $pesquisaServico::FUSO)->toIso8601String()),
    rotas: {
        convite: @json(route('pesquisa.convite')),
        responder: @json(route('pesquisa.responder')),
        dispensar: @json(route('pesquisa.dispensar'))
    }
};
</script>
<script src="{{ asset('js/pesquisa-primeiro-agendamento.js') }}?v=20261022-1"></script>
@endif
