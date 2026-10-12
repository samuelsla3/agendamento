@if(auth()->check() && in_array(auth()->user()->tipo, ['estudante', 'aluno'], true))
    <div class="sugestoes-acesso" id="sugestoes-do-sistema">
        <button type="button" class="sugestoes-botao-discreto" id="abrir-sugestao"
            aria-haspopup="dialog" aria-controls="sugestoes-modal" aria-expanded="false">Sugerir uma melhoria</button>
        <span>Sua sugestão é anônima.</span>
        @if(session('sugestao_sucesso'))
            <p class="sugestoes-retorno" role="status">{{ session('sugestao_sucesso') }}</p>
        @endif
    </div>
    <dialog class="sugestoes-modal" id="sugestoes-modal" aria-labelledby="sugestoes-titulo"
        data-abrir="{{ $errors->getBag('sugestoes')->any() ? '1' : '0' }}">
        <div class="sugestoes-modal-cabecalho">
            <h2 id="sugestoes-titulo">Sugestões para melhorar o sistema</h2>
            <button type="button" class="sugestoes-fechar" id="fechar-sugestao" aria-label="Fechar sugestões">&times;</button>
        </div>
        <form id="sugestoes-form" action="{{ route('sugestoes.enviar') }}" method="POST">
            @csrf
            <input type="hidden" name="chave_envio" id="sugestoes-chave" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <p id="sugestoes-privacidade">O envio não registra seu nome, matrícula ou e-mail. Escreva sobre o funcionamento do site, sem nomes de pessoas ou detalhes de atendimentos.</p>
            <label for="sugestoes-texto">O que podemos melhorar?</label>
            <textarea name="sugestao" id="sugestoes-texto" rows="9" minlength="3" maxlength="10000" required
                aria-describedby="sugestoes-privacidade sugestoes-contador"
                placeholder="Conte o que poderia facilitar o uso do sistema, uma dificuldade que encontrou ou uma ideia de melhoria."></textarea>
            <p class="sugestoes-contador" id="sugestoes-contador">0 / 10.000 caracteres</p>
            <p class="sugestoes-retorno" id="sugestoes-retorno" role="status" @if(!$errors->getBag('sugestoes')->any()) hidden @endif>{{ $errors->getBag('sugestoes')->first() }}</p>
            <div class="sugestoes-acoes">
                <button type="submit" class="btn btn-primary" id="enviar-sugestao">Enviar sugestão anônima</button>
                <button type="button" class="btn btn-secondary" id="cancelar-sugestao">Fechar</button>
            </div>
        </form>
    </dialog>
@endif
