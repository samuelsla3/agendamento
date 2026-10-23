@php($perguntas = \App\Services\PesquisaPrimeiroAgendamento::PERGUNTAS)
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Pesquisa de uso — Psicologia</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=20261022-pesquisa-1">
    <link rel="stylesheet" href="{{ asset('css/identidade-visual.css') }}?v=20261013-1">
    <link rel="stylesheet" href="{{ asset('css/pesquisa.css') }}?v=20261022-1">
    <script src="{{ asset('js/pesquisa-resultados.js') }}?v=20261022-1" defer></script>
</head>
<body class="ui-sistema">
<header class="header"><h1>Pesquisa de uso do sistema</h1><a class="btn btn-secondary" href="{{ route(auth()->user()->tipo === 'psicologa' ? 'psicologa.index' : 'agenda.index') }}">Voltar ao site</a></header>
<main class="container pesquisa-painel">
    <p>Primeiro agendamento realizado entre 22/10/2026 e 20/11/2026. As respostas não possuem nome, matrícula, e-mail ou vínculo com o participante.</p>
    <p>A identificação abaixo distingue as respostas e permite excluir testes; ela não identifica alunos. A exclusão não permite que a mesma pessoa responda novamente.</p>
    @if(session('sucesso'))<p role="status">{{ session('sucesso') }}</p>@endif
    <div class="pesquisa-totais"><p><strong>{{ $total }}</strong> respostas registradas</p><p>Avaliação média: <strong>{{ $media === null ? '—' : number_format($media, 2, ',', '.') }}</strong> / 5</p></div>
    <h2>Resumo das respostas</h2>
    <div class="pesquisa-resultados">
    @foreach($perguntas as $campo => $pergunta)
        <section><h3>{{ $loop->iteration }}. {{ $pergunta['texto'] }}</h3><ul>
        @foreach($pergunta['opcoes'] as $valor => $rotulo)<li>{{ $rotulo }}: <strong>{{ $distribuicoes[$campo][$valor] ?? 0 }}</strong></li>@endforeach
        </ul></section>
    @endforeach
    </div>
    <h2>Respostas individuais</h2>
    @forelse($respostas as $resposta)
        <article class="pesquisa-resposta">
            <h3>Resposta {{ strtoupper(substr($resposta->id, 0, 8)) }}</h3>
            <dl>
            @foreach($perguntas as $campo => $pergunta)
                <dt>{{ $loop->iteration }}. {{ $pergunta['texto'] }}</dt><dd>{{ $pergunta['opcoes'][$resposta->{$campo}] ?? 'Opção não reconhecida' }}</dd>
            @endforeach
                <dt>6. Como você avalia o site/proposta em geral?</dt><dd>{{ $resposta->estrelas }} / 5 {{ str_repeat('★', $resposta->estrelas) }}</dd>
                <dt>7. O que poderia melhorar?</dt><dd>{{ $resposta->sugestao ?: 'Sem sugestão.' }}</dd>
            </dl>
            <form action="{{ route('pesquisa.excluir', ['resposta' => $resposta->id]) }}" method="POST" data-excluir-resposta>
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir esta resposta</button>
            </form>
        </article>
    @empty<p>Ainda não há respostas registradas.</p>@endforelse
    @if($respostas->hasPages())
    <nav class="pesquisa-paginacao" aria-label="Páginas de respostas">
        @if($respostas->onFirstPage())<span>Anterior</span>@else<a href="{{ $respostas->previousPageUrl() }}">Anterior</a>@endif
        <span>Página {{ $respostas->currentPage() }} de {{ $respostas->lastPage() }}</span>
        @if($respostas->hasMorePages())<a href="{{ $respostas->nextPageUrl() }}">Próxima</a>@else<span>Próxima</span>@endif
    </nav>
    @endif
</main>
</body></html>
