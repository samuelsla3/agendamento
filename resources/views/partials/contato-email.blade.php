@if(($contatoEmail ?? null) !== null)
    <dialog id="contato-email-modal" class="contato-email-modal" aria-labelledby="contato-email-titulo"
        data-abrir="{{ ($mostrarConfirmacaoEmail ?? false) ? '1' : '0' }}"
        data-status-url="{{ route('contato.email.status') }}">
        <div class="contato-email-cabecalho">
            <h2 id="contato-email-titulo">Confirme seu e-mail</h2>
            <button type="button" id="contato-email-fechar" aria-label="Fechar">&times;</button>
        </div>
        <p>Informe o e-mail que você mais utiliza para receber os avisos dos seus agendamentos.</p>
        <p id="contato-email-atual"></p>
        <div id="contato-email-retorno" class="contato-email-retorno" role="status" aria-live="polite" hidden></div>
        <form id="contato-email-enviar" method="POST" action="{{ route('contato.email.enviar') }}">
            @csrf
            <label for="contato-email-endereco">Digite seu e-mail</label>
            <input type="email" id="contato-email-endereco" name="email" maxlength="100" autocomplete="email" required
                value="{{ $contatoEmail['pendente'] ?? $contatoEmail['atual'] }}" placeholder="Digite o e-mail que você usa">
            <p class="contato-email-ajuda">Você pode manter o endereço sugerido ou escolher outro. Enviaremos um código para conferir se você consegue acessá-lo.</p>
            <button type="submit" id="contato-email-enviar-btn" class="btn btn-primary">Enviar código</button>
            <p id="contato-email-espera" class="contato-email-ajuda"></p>
        </form>
        <section id="contato-email-codigo-area" hidden>
            <h3>Digite o código recebido</h3>
            <p id="contato-email-destino"></p>
            <p id="contato-email-envio" role="status"></p>
            <form id="contato-email-confirmar" method="POST" action="{{ route('contato.email.confirmar') }}">
                @csrf
                <input type="hidden" name="versao" id="contato-email-versao" value="{{ $contatoEmail['versao'] }}">
                <label for="contato-email-codigo">Código de seis números</label>
                <input type="text" id="contato-email-codigo" name="codigo" inputmode="numeric" autocomplete="one-time-code"
                    minlength="6" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required>
                <button type="submit" id="contato-email-confirmar-btn" class="btn btn-success">Confirmar e-mail</button>
                <button type="button" id="contato-email-atualizar" class="contato-email-link">Atualizar situação do envio</button>
            </form>
        </section>
        <p class="contato-email-ajuda">O código vale por 15 minutos. Uma mudança de endereço só entra em vigor depois da confirmação.</p>
        <button type="button" id="contato-email-depois" class="btn btn-secondary">Agora não</button>
    </dialog>
    <script type="application/json" id="contato-email-dados">@json($contatoEmail)</script>
@endif
