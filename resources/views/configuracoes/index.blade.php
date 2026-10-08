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
    <style>
        .config-container { max-width: 720px; }
        .config-card h2 { text-align: left; margin-top: 0; }
        .config-card p { line-height: 1.6; }
        .config-card input[type="time"] { max-width: 240px; font: inherit; }
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
    </main>
</body>
</html>