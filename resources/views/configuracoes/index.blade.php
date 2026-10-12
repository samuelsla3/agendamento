<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Configurações — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/aviso-alunos.css') }}?v=20261008-1">
    <script src="{{ asset('js/operacoes.js') }}?v=20260924-1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/sugestoes.css') }}?v=20261012-1">
    <script src="{{ asset('js/sugestoes.js') }}?v=20261012-2" defer></script>
    <style>
        .config-container { max-width: 720px; }
        .config-card h2 { text-align: left; margin-top: 0; }
        .config-card p { line-height: 1.6; }
        .config-card input[type="time"] { max-width: 240px; font: inherit; }
        .config-card select {
            display: block; width: 100%; min-height: 44px; padding: 10px;
            box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font: inherit;
        }
        .config-card input[type="number"] {
            width: 100%; max-width: 240px; min-height: 44px; padding: 10px;
            box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font: inherit;
        }
        .config-card input[type="email"] {
            display: block; width: 100%; min-height: 44px; padding: 10px;
            box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font: inherit;
        }
        .config-card textarea {
            display: block; width: 100%; min-height: 160px; padding: 12px;
            box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px;
            font: inherit; line-height: 1.5; resize: vertical;
        }
        .config-ativar {
            display: flex; align-items: center; gap: 12px; padding: 12px 0;
            min-height: 44px; cursor: pointer;
        }
        .config-ativar input { width: 20px; height: 20px; flex: 0 0 20px; margin: 0; }
        .config-orientacao { background: #fff8e8; color: #664b10; border: 1px solid #ead8aa; }
        .config-estado { font-weight: 600; }
        .config-acoes { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        .config-acoes a, .header a { text-decoration: none; }
        .config-aviso { padding: 14px; border-radius: 6px; margin: 16px 0; }
        .config-sucesso { background: #e8f5ec; color: #176536; }
        .config-erro { background: #fff1f1; color: #9d2020; }
        @media (max-width: 767px) {
            .config-acoes > .btn { width: 100%; box-sizing: border-box; text-align: center; }
            .config-card input[type="time"] { max-width: none; }
            .config-card input[type="number"] { max-width: none; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Configurações</h1>
        <div class="user-info">
            <span>{{ auth()->user()->nome }}</span>
            <a href="{{ route('psicologa.index') }}" class="btn btn-secondary">Voltar à agenda</a>
        </div>
    </header>

    <main class="container config-container">
        @if(session('sucesso'))
            <div class="config-aviso config-sucesso" role="status">{{ session('sucesso') }}</div>
        @endif
        @if($errors->any())
            <div class="config-aviso config-erro" role="alert">
                @foreach($errors->all() as $erro)
                    <p>{{ $erro }}</p>
                @endforeach
            </div>
        @endif

        <section class="content-section config-card" id="seguranca-da-conta">
            <h2>Segurança da conta</h2>
            <p>Altere sua senha local ou a senha específica do prontuário.</p>
            <div class="config-acoes"><a href="{{ route('seguranca.index') }}" class="btn btn-primary">Gerenciar senhas</a></div>
        </section>

        <section class="content-section config-card" id="preferencias-calendario">
            <h2>Visualização do calendário</h2>
            <p>Escolha como a sua agenda abre no computador e no celular. Você pode trocar a visualização pelos botões do calendário a qualquer momento.</p>
            <form action="{{ route('configuracoes.calendario.atualizar') }}" method="POST" data-texto-envio="Salvando preferências…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <div class="form-group">
                    <label for="calendario_visualizacao">Visualização inicial</label>
                    <select id="calendario_visualizacao" name="calendario_visualizacao" required>
                        <option value="padrao" @selected(old('calendario_visualizacao', $configuracao->calendario_visualizacao ?? 'padrao') === 'padrao')>Padrão atual — mês no computador e lista no celular</option>
                        <option value="semana" @selected(old('calendario_visualizacao', $configuracao->calendario_visualizacao ?? 'padrao') === 'semana')>Semana</option>
                        <option value="mes" @selected(old('calendario_visualizacao', $configuracao->calendario_visualizacao ?? 'padrao') === 'mes')>Mês</option>
                        <option value="lista" @selected(old('calendario_visualizacao', $configuracao->calendario_visualizacao ?? 'padrao') === 'lista')>Lista da semana</option>
                    </select>
                </div>
                <input type="hidden" name="calendario_faixa_ativa" value="0">
                <label class="config-ativar" for="calendario_faixa_ativa">
                    <input type="checkbox" id="calendario_faixa_ativa" name="calendario_faixa_ativa" value="1"
                        @checked(old('calendario_faixa_ativa', $configuracao->calendario_faixa_ativa ?? false))>
                    <span>Usar uma faixa inicial de horas nas visualizações Semana e Dia</span>
                </label>
                <div class="form-group">
                    <label for="calendario_hora_inicio">Exibir a partir de</label>
                    <input type="time" id="calendario_hora_inicio" name="calendario_hora_inicio" step="60" required
                        value="{{ old('calendario_hora_inicio', $configuracao->calendario_hora_inicio ?? '08:00') }}">
                </div>
                <div class="form-group">
                    <label for="calendario_hora_fim">Exibir até</label>
                    <input type="time" id="calendario_hora_fim" name="calendario_hora_fim" step="60" required
                        value="{{ old('calendario_hora_fim', $configuracao->calendario_hora_fim ?? '18:00') }}">
                </div>
                <p>Exemplo: das 08h às 18h. Se houver horários fora dessa faixa no período aberto, a tela será ampliada automaticamente para mostrá-los. O botão <strong>Mostrar dia completo</strong> também permite consultar todas as horas.</p>
                <p>Desmarque a faixa para mostrar o dia inteiro. No mês e na lista, todos os horários continuam aparecendo. Esta preferência organiza a sua tela e não limita os agendamentos.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar calendário</button>
                </div>
            </form>
        </section>

        <section class="content-section config-card" id="informacoes-atendimento">
            <h2>Informações sobre o atendimento</h2>
            <p>Estas informações aparecem no botão <strong>Sobre o atendimento</strong>, na página inicial (calendário), para visitantes e alunos logados. Preencha apenas os campos que deseja divulgar.</p>
            <form action="{{ route('configuracoes.informacoes.atualizar') }}" method="POST" data-texto-envio="Salvando informações…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <div class="form-group">
                    <label for="atendimento_resumo">Resumo sobre o atendimento</label>
                    <textarea id="atendimento_resumo" name="atendimento_resumo" rows="4" maxlength="2000"
                        placeholder="Explique brevemente como funciona o serviço e quem pode utilizá-lo.">{{ old('atendimento_resumo', $configuracao->atendimento_resumo) }}</textarea>
                </div>
                <div class="form-group">
                    <label for="atendimento_local">Local de atendimento</label>
                    <input type="text" id="atendimento_local" name="atendimento_local" maxlength="255"
                        placeholder="Ex.: Sala do setor de Psicologia, bloco administrativo."
                        value="{{ old('atendimento_local', $configuracao->atendimento_local) }}">
                </div>
                <div class="form-group">
                    <label for="atendimento_orientacoes">Orientações de chegada</label>
                    <textarea id="atendimento_orientacoes" name="atendimento_orientacoes" rows="3" maxlength="2000"
                        placeholder="Ex.: Aguarde próximo à sala e chegue alguns minutos antes.">{{ old('atendimento_orientacoes', $configuracao->atendimento_orientacoes) }}</textarea>
                </div>
                <div class="form-group">
                    <label for="atendimento_email">E-mail de contato do setor</label>
                    <input type="email" id="atendimento_email" name="atendimento_email" maxlength="100"
                        value="{{ old('atendimento_email', $configuracao->atendimento_email) }}">
                </div>
                <div class="form-group">
                    <label for="atendimento_telefone">Telefone ou ramal</label>
                    <input type="text" id="atendimento_telefone" name="atendimento_telefone" maxlength="50"
                        value="{{ old('atendimento_telefone', $configuracao->atendimento_telefone) }}">
                </div>
                <div class="form-group">
                    <label for="atendimento_outros_contatos">Outros contatos</label>
                    <textarea id="atendimento_outros_contatos" name="atendimento_outros_contatos" rows="3" maxlength="1000"
                        placeholder="Outros e-mails ou formas de contato que possam ser divulgados.">{{ old('atendimento_outros_contatos', $configuracao->atendimento_outros_contatos) }}</textarea>
                </div>
                <p>Todos os campos são opcionais. Campos vazios são ocultados; se limpar todos e salvar, o botão também desaparece.</p>
                <p>Local, orientações e contatos serão incluídos nos próximos e-mails enviados pelo sistema. O e-mail informado aqui é um contato público do setor.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar informações</button>
                </div>
            </form>
        </section>

        <section class="content-section config-card" id="antecedencia-agendamento">
            <h2>Antecedência para agendamento</h2>
            <p>Escolha quanto tempo antes do atendimento o aluno precisa fazer a reserva.</p>
            <form action="{{ route('configuracoes.antecedencia.atualizar') }}" method="POST" data-texto-envio="Salvando antecedência…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <input type="hidden" name="antecedencia_agendamento_ativa" value="0">
                <label class="config-ativar" for="antecedencia_agendamento_ativa">
                    <input type="checkbox" id="antecedencia_agendamento_ativa" name="antecedencia_agendamento_ativa" value="1"
                        @checked(old('antecedencia_agendamento_ativa', $configuracao->antecedencia_agendamento_ativa))>
                    <span>Exigir antecedência mínima para agendar</span>
                </label>
                <div class="form-group">
                    <label for="antecedencia_agendamento_minutos">Tempo mínimo em minutos</label>
                    <input type="number" id="antecedencia_agendamento_minutos" name="antecedencia_agendamento_minutos"
                        min="1" max="10080" step="1" required inputmode="numeric" aria-describedby="orientacao-antecedencia"
                        value="{{ old('antecedencia_agendamento_minutos', $configuracao->antecedencia_agendamento_minutos ?? 120) }}">
                </div>
                <p id="orientacao-antecedencia">Exemplos: 30 minutos = meia hora; 120 minutos = duas horas. Limite: 10.080 minutos (7 dias).</p>
                <p>Com 120 minutos ativados, um atendimento às 14h pode ser reservado até as 12h. Desmarque a opção e salve para desativar; o tempo escolhido fica guardado.</p>
                <p>Esta regra vale para novas reservas dos alunos. Os agendamentos existentes são preservados.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar antecedência</button>
                </div>
            </form>
        </section>

        <section class="content-section config-card" id="aviso-aos-alunos">
            <h2>Aviso aos alunos</h2>
            <p>O recado ativo aparece no login e na agenda dos alunos, inclusive para quem ainda não entrou no sistema.</p>
            <p class="config-estado">Exibição atual: {{ $configuracao->aviso_ativo ? 'Ativada' : 'Desativada' }}.</p>

            <form action="{{ route('configuracoes.aviso.atualizar') }}" method="POST" data-texto-envio="Salvando aviso…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <div class="form-group">
                    <label for="aviso_texto">Recado</label>
                    <textarea id="aviso_texto" name="aviso_texto" rows="6" maxlength="2000"
                        aria-describedby="aviso-orientacao aviso-privacidade"
                        placeholder="Ex.: Não haverá atendimento entre os dias 13 e 16 de outubro. As demais datas continuam disponíveis.">{{ old('aviso_texto', $configuracao->aviso_texto) }}</textarea>
                    <p id="aviso-privacidade">Até 2.000 caracteres. Este recado é público: escreva apenas orientações gerais, sem nomes de alunos ou informações de atendimentos.</p>
                </div>

                <input type="hidden" name="aviso_ativo" value="0">
                <label class="config-ativar" for="aviso_ativo">
                    <input type="checkbox" id="aviso_ativo" name="aviso_ativo" value="1"
                        @checked(old('aviso_ativo', $configuracao->aviso_ativo))>
                    <span>Exibir aviso aos alunos</span>
                </label>

                <div class="config-aviso config-orientacao" id="aviso-orientacao">
                    <p><strong>Confira a agenda antes de publicar:</strong> se já houver horários no período de ausência, use <strong>Cancelar Horários</strong> na agenda para remover as vagas livres e cancelar as reservas. Os alunos com reservas serão avisados por e-mail com a justificativa informada no cancelamento.</p>
                    <p>Publicar o aviso não remove horários nem cancela reservas. Ao retornar, lembre-se de atualizar ou desativar o recado.</p>
                </div>

                <p>Para desativar, desmarque a opção acima e salve. O texto fica guardado para reutilização. Salvar o aviso não envia e-mails.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar aviso</button>
                    <a href="{{ route('psicologa.index') }}" class="btn btn-secondary">Ir à agenda</a>
                </div>
            </form>
        </section>

        <section class="content-section config-card">
            <h2>Lembretes por e-mail</h2>
            <p>Os lembretes são enviados <strong>no dia anterior ao atendimento</strong>, no horário escolhido abaixo.</p>
            <form action="{{ route('configuracoes.atualizar') }}" method="POST" data-texto-envio="Salvando…">
                @csrf
                <input type="hidden" name="versao" value="{{ $configuracao->versao }}">
                <div class="form-group">
                    <label for="horario_lembretes">Horário de envio</label>
                    <input type="time" id="horario_lembretes" name="horario_lembretes" step="60" required
                        aria-describedby="orientacao-horario"
                        value="{{ old('horario_lembretes', $configuracao->horario_lembretes) }}">
                </div>
                <p id="orientacao-horario">Horário de Brasília. Exemplo: um atendimento na terça-feira recebe o lembrete na segunda-feira, no horário definido.</p>
                <p>Salvar não envia e-mails imediatamente. Se o horário escolhido já passou hoje, o próximo envio automático será amanhã.</p>
                <div class="config-acoes">
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    <a href="{{ route('psicologa.index') }}" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </section>
        <section class="content-section config-card" id="acompanhamento-emails">
            <h2>Acompanhamento de e-mails</h2>
            <p>Consulte os envios aos alunos e acompanhe falhas ou mensagens sem confirmação.</p>
            @if($problemasEmail > 0)
                <div class="config-aviso config-orientacao" role="status">
                    <p><strong>{{ $problemasEmail }} {{ $problemasEmail === 1 ? 'aviso precisa de atenção' : 'avisos precisam de atenção' }}.</strong></p>
                    <p>Há problemas no envio ou mensagens sem confirmação. Os agendamentos e cancelamentos registrados continuam válidos.</p>
                </div>
            @else
                <p>Nenhum aviso precisa de atenção no momento.</p>
            @endif
            <div class="config-acoes">
                <a href="{{ route('emails.index') }}" class="btn btn-primary">Acompanhar os envios</a>
            </div>
        </section>
        @include('partials.sugestoes-psicologa')
    </main>
</body>
</html>
