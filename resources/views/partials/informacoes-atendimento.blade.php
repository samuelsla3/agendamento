@if(!empty($informacoesAtendimento))
    <div class="setor-info-acesso">
        <button type="button" class="setor-info-botao" id="abrir-informacoes-atendimento"
            aria-haspopup="dialog" aria-controls="informacoes-atendimento-modal" aria-expanded="false">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="9"></circle><path d="M12 11v6M12 7v1"></path>
            </svg>
            <span>Sobre o atendimento</span>
        </button>
    </div>
    <dialog id="informacoes-atendimento-modal" class="setor-info-modal" aria-labelledby="informacoes-atendimento-titulo">
        <div class="setor-info-cabecalho">
            <h2 id="informacoes-atendimento-titulo">Informações sobre o atendimento</h2>
            <button type="button" class="setor-info-fechar" data-fechar-informacoes aria-label="Fechar informações" autofocus>&times;</button>
        </div>
        <div class="setor-info-conteudo">
            @foreach(['resumo' => 'Sobre o atendimento', 'local' => 'Local', 'orientacoes' => 'Orientações de chegada', 'email' => 'E-mail de contato', 'telefone' => 'Telefone ou ramal', 'contatos' => 'Outros contatos'] as $chave => $rotulo)
                @if(isset($informacoesAtendimento[$chave]))
                    <details class="setor-info-secao" open>
                        <summary>{{ $rotulo }}</summary>
                        @if($chave === 'email')
    <p class="setor-info-texto setor-info-contato" style="white-space: normal;">
        <a href="mailto:{{ $informacoesAtendimento[$chave] }}">
            {{ $informacoesAtendimento[$chave] }}
        </a>
    </p>
@elseif($chave === 'telefone')
    @php
        $telefone = trim($informacoesAtendimento[$chave]);
        $numeroWhatsapp = preg_replace('/[^0-9]/', '', $telefone);

        // Número brasileiro com DDD, informado sem código do país.
        if (in_array(strlen($numeroWhatsapp), [10, 11], true)) {
            $numeroWhatsapp = '55' . $numeroWhatsapp;
        }

        $telefoneValido = preg_match('/\A\+?[0-9()\s.-]+\z/u', $telefone)
            && preg_match('/\A55[1-9]{2}[2-9][0-9]{7,8}\z/', $numeroWhatsapp);
    @endphp

    <p class="setor-info-texto setor-info-contato" style="white-space: normal;">
        @if($telefoneValido)
            <a href="https://wa.me/{{ $numeroWhatsapp }}"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="Conversar pelo WhatsApp: {{ $telefone }}">
                {{ $telefone }}
            </a>
        @else
            {{ $telefone }}
        @endif
    </p>
@else
    <p class="setor-info-texto">{{ $informacoesAtendimento[$chave] }}</p>
@endif
                    </details>
                @endif
            @endforeach
        </div>
    </dialog>
@endif
