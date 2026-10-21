@php
    // Para adicionar fotos, substitua null por 'images/desenvolvedores/nome.jpg'.
    $equipeDesenvolvimento = [
        ['nome' => 'Caio Valle Araújo', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2021 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Eduarda Chaves', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2021 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Samuel Santos de Lima Alves', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2023 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Ítalo Oliveira Silva', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2023 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Monck Charles Albuquerque', 'cargo' => 'Docente / Orientador', 'descricao' => 'Docente do Curso de Informática no IFBA - Campus Seabra e orientador do projeto.', 'foto' => 'images/desenvolvedores/charles.png'],
    ];
@endphp

<section class="desenvolvedores" aria-labelledby="desenvolvedores-titulo">
    <div class="desenvolvedores-conteudo">
        <h2 id="desenvolvedores-titulo">Desenvolvedores</h2>
        <div class="desenvolvedores-grade">
            @foreach($equipeDesenvolvimento as $integrante)
                <article class="desenvolvedores-cartao">
                    <img class="desenvolvedores-foto"
                        src="{{ asset($integrante['foto'] ?? 'images/desenvolvedor-placeholder.svg') }}"
                        alt="{{ $integrante['foto'] ? 'Foto de ' . $integrante['nome'] : '' }}"
                        width="112" height="112" loading="lazy">
                    <h3>{{ $integrante['nome'] }}</h3>
                    <p class="desenvolvedores-cargo">{{ $integrante['cargo'] }}</p>
                    <p class="desenvolvedores-descricao">{{ $integrante['descricao'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
