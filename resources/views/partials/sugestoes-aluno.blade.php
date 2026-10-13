@if(auth()->check() && in_array(auth()->user()->tipo, ['estudante', 'aluno'], true))
    <div class="sugestoes-acesso" id="sugestoes-do-sistema">
        <button type="button" class="sugestoes-botao-discreto" id="abrir-sugestao" style="display:inline-flex;align-items:center;justify-content:center;gap:7px;"
            aria-haspopup="dialog" aria-controls="sugestoes-modal" aria-expanded="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M9 18h6M10 21h4M9 15c0-1.5-3-2.5-3-6a6 6 0 0 1 12 0c0 3.5-3 4.5-3 6"></path>
            </svg>
            <span>Sugerir melhorias</span>
        </button>
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
