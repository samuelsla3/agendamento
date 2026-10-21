@if(auth()->check() && in_array(auth()->user()->tipo, ['estudante', 'aluno', 'psicologa'], true))
@php
$mensagensPsicologa = auth()->user()->tipo === 'psicologa';
$mensagensDados = [
    'psicologa' => $mensagensPsicologa, 'csrf' => csrf_token(),
    'contexto' => \App\Services\ComunicacaoPrivada::contexto(auth()->user()),
    'consulta' => route('psicologa.index'), 'resumo' => route('mensagens.resumo'), 'desbloquear' => route('mensagens.desbloquear'),
    'abrir' => route('mensagens.abrir'), 'contatos' => route('mensagens.contatos'), 'alunos' => route('mensagens.alunos'),
    'historico' => route('mensagens.historico', ['conversa' => '__ID__']),
    'enviar' => route('mensagens.enviar', ['conversa' => '__ID__']),
    'emailEnviar' => route('mensagens.email.enviar', ['conversa' => '__ID__']),
    'emailStatus' => route('mensagens.email.status', ['conversa' => '__ID__', 'chave' => '__CHAVE__']),
    'emailAnexosMaximo' => config('comunicacao.email_anexos_maximo_bytes', 15728640),
    'lidas' => route('mensagens.lidas', ['conversa' => '__ID__']),
    'renovar' => route('prontuarios.acesso.renovar'),
    'intervalo' => config('comunicacao.consultas_intervalo_segundos', 30),
    'arquivoMaximo' => config('comunicacao.arquivo_maximo_bytes', 10485760),
    'arquivosMaximo' => config('comunicacao.arquivos_por_mensagem', 3),
];
@endphp
<script type="application/json" id="mensagens-dados">@json($mensagensDados)</script>
<dialog class="mensagens-modal {{ $mensagensPsicologa ? 'mensagens-modal--psicologa' : 'mensagens-modal--aluno' }}"
    id="mensagens-modal" aria-labelledby="mensagens-titulo">
    <header class="mensagens-cabecalho-modal">
        <div><h2 id="mensagens-titulo">{{ $mensagensPsicologa ? 'Mensagens dos alunos' : 'Contato com a Psicóloga' }}</h2>
            <p>Mensagens e documentos relacionados ao atendimento.</p></div>
        <button type="button" id="mensagens-fechar" class="mensagens-botao-leve" aria-label="Fechar mensagens">Fechar <span aria-hidden="true">&times;</span></button>
    </header>
    <p id="mensagens-aviso" class="mensagens-aviso" role="status" hidden></p>
    @if($mensagensPsicologa)
    <section id="mensagens-bloqueio" class="mensagens-bloqueio" hidden>
        <h3>Abra suas conversas com segurança</h3>
        <p>Mensagens e documentos usam a proteção do prontuário. Se o acesso já estiver desbloqueado, você pode continuar sem digitar a senha novamente.</p>
        <form id="mensagens-senha-form">
            <label for="mensagens-senha">Senha do prontuário</label>
            <div style="position: relative;">
                <input type="password" id="mensagens-senha" name="senha" autocomplete="current-password" maxlength="512" style="padding-right: 52px;" required>
                @include('partials.senha-olho', ['campoSenha' => 'mensagens-senha'])
            </div>
            <button type="submit" class="btn btn-primary" id="mensagens-senha-enviar">Desbloquear Conversas</button>
        </form>
    </section>
    @endif
    <div id="mensagens-area" class="mensagens-area" hidden>
        @if($mensagensPsicologa)
        <aside class="mensagens-contatos">
            <div class="mensagens-modos" aria-label="Lista de contatos">
                <button type="button" class="mensagens-botao-leve ativo" id="mensagens-modo-conversas" aria-pressed="true">Conversas</button>
                <button type="button" class="mensagens-botao-leve" id="mensagens-modo-novo" aria-pressed="false">Novo contato</button>
            </div>
            <div class="mensagens-filtros">
                <label for="mensagens-nome">Nome do aluno</label>
                <input type="text" id="mensagens-nome" maxlength="100" list="mensagens-nomes" autocomplete="off" placeholder="Digite ou selecione um aluno">
                <datalist id="mensagens-nomes"></datalist>
                <div class="mensagens-filtros-linha">
                    <div><label for="mensagens-turma">Turma</label><input type="text" id="mensagens-turma" maxlength="50" list="mensagens-turmas" autocomplete="off" placeholder="Ex.: 3 ou 3.18.1I"></div>
                    <div><label for="mensagens-matricula">Matrícula</label><input type="text" id="mensagens-matricula" maxlength="20" autocomplete="off" inputmode="numeric" placeholder="Parte ou completa"></div>
                </div>
                <datalist id="mensagens-turmas"></datalist>
            </div>
            <p id="mensagens-lista-aviso" class="mensagens-lista-aviso" role="status" hidden></p>
            <div id="mensagens-lista-contatos" class="mensagens-lista-contatos" aria-label="Alunos"></div>
            <div class="mensagens-contatos-paginas" id="mensagens-contatos-paginas" hidden>
                <button type="button" id="mensagens-contatos-anterior" class="mensagens-botao-leve">Anterior</button>
                <span id="mensagens-contatos-pagina"></span>
                <button type="button" id="mensagens-contatos-proxima" class="mensagens-botao-leve">Próxima</button>
            </div>
        </aside>
        @endif
        <section class="mensagens-conversa" id="mensagens-conversa">
            <div id="mensagens-vazio" class="mensagens-vazio">
                <p>{{ $mensagensPsicologa ? 'Selecione uma conversa ou procure um aluno em Novo contato.' : 'Abrindo seu contato com o setor…' }}</p>
            </div>
            <div id="mensagens-conversa-area" class="mensagens-conversa-area" hidden>
                <header class="mensagens-destinatario">
                    @if($mensagensPsicologa)<button type="button" class="mensagens-botao-leve mensagens-voltar-contatos" id="mensagens-voltar-contatos" aria-label="Voltar aos contatos">&larr;</button>@endif
                    <div><h3 id="mensagens-destinatario-nome"></h3><p id="mensagens-destinatario-info"></p></div>
                </header>
                <div class="mensagens-abas">
                    <button type="button" class="mensagens-botao-leve ativo" id="mensagens-aba-interno" aria-pressed="true">Mensagens no site</button>
                    <button type="button" class="mensagens-botao-leve" id="mensagens-aba-email" aria-pressed="false" hidden>E-mail</button>
                </div>
                <div id="mensagens-interno-area" class="mensagens-interno-area">
                    <div id="mensagens-historico" class="mensagens-historico" role="log" aria-label="Mensagens da conversa" aria-live="polite" aria-relevant="additions">
                        <button type="button" class="mensagens-botao-leve mensagens-anteriores" id="mensagens-anteriores" hidden>Ver mensagens anteriores</button>
                        <div id="mensagens-itens"></div>
                    </div>
                    <form id="mensagens-envio-form" class="mensagens-envio-form">
                        <label class="mensagens-sr-only" for="mensagens-texto">Escreva uma mensagem</label>
                        <textarea id="mensagens-texto" rows="3" maxlength="10000" placeholder="Escreva uma mensagem ou anexe um documento"></textarea>
                        <input type="file" id="mensagens-arquivos" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple hidden>
                        <div id="mensagens-selecionados" class="mensagens-selecionados"></div>
                        <p id="mensagens-envio-aviso" class="mensagens-aviso" role="status" hidden></p>
                        <div class="mensagens-envio-acoes">
                            <button type="button" class="mensagens-botao-leve" id="mensagens-anexar">Anexar arquivo</button>
                            <span id="mensagens-texto-contador">0 / 10.000</span>
                            <button type="submit" class="btn btn-primary" id="mensagens-enviar">Enviar</button>
                        </div>
                        <p class="mensagens-ajuda">PDF, JPG, PNG ou WEBP. Até 3 arquivos de 10 MB por mensagem. O rascunho fica guardado enquanto você permanecer nesta página.</p>
                    </form>
                </div>
                <form id="mensagens-email-form" class="mensagens-email-area" hidden>
                    <p>Para: <strong id="mensagens-email-destino"></strong></p>
                    <p id="mensagens-email-confirmacao" class="mensagens-ajuda" hidden>O aluno precisa confirmar o e-mail de contato no sistema antes do envio.</p>
                    <label for="mensagens-email-assunto">Assunto</label>
                    <input type="text" id="mensagens-email-assunto" maxlength="150" required value="Contato - Setor de Psicologia IFBA">
                    <label for="mensagens-email-texto">Texto do e-mail</label>
                    <textarea id="mensagens-email-texto" rows="8" maxlength="10000" placeholder="Escreva o texto do e-mail."></textarea>
                    <div id="mensagens-email-selecionados" class="mensagens-arquivos-selecionados" aria-live="polite"></div>
                    <input type="file" id="mensagens-email-arquivos" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple hidden>
                    <p class="mensagens-ajuda" id="mensagens-email-ajuda">O e-mail será enviado pelo sistema. PDF, JPG, PNG ou WEBP: até 3 arquivos, 10 MB por arquivo e 15 MB no total. Selecione aqui os anexos deste e-mail.</p>
                    <p id="mensagens-email-aviso" class="mensagens-aviso" role="status" hidden></p>
                    <div class="mensagens-email-acoes">
                        <button type="button" class="mensagens-botao-leve" id="mensagens-email-anexar">Anexar arquivos</button>
                        <button type="submit" class="btn btn-primary" id="mensagens-email-enviar">Enviar e-mail</button>
                        <button type="button" class="mensagens-botao-leve" id="mensagens-email-novo" hidden>Preparar nova tentativa</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</dialog>
@endif
