@php
    // Para adicionar fotos, substitua null por 'images/desenvolvedores/nome.jpg'.
    $equipeDesenvolvimento = [
        ['nome' => 'Caio Valle Araújo', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2021 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Eduarda Chaves', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2021 no Curso de Informática.', 'foto' => null],
        ['nome' => 'Samuel Santos de Lima Alves', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2023 no Curso de Informática.', 'foto' => 'images/desenvolvedores/samuel.jpg'],
        ['nome' => 'Ítalo Oliveira Silva', 'cargo' => 'Discente IFBA', 'descricao' => 'Discente ingressado no ano de 2023 no Curso de Informática.', 'foto' => 'images/desenvolvedores/italo.jpg'],
        ['nome' => 'Monck Charles Albuquerque', 'cargo' => 'Docente / Orientador', 'descricao' => 'Docente do Curso de Informática no IFBA - Campus Seabra e orientador do projeto.', 'foto' => 'images/desenvolvedores/charles.png'],
    ];
@endphp

<section class="desenvolvedores" aria-labelledby="desenvolvedores-titulo">
    <div class="desenvolvedores-conteudo">
        <h2 id="desenvolvedores-titulo">Desenvolvedores</h2>

        <div class="desenvolvedores-versoes">
            @foreach([
                ['titulo' => '1ª Versão', 'integrantes' => array_slice($equipeDesenvolvimento, 0, 2)],
                ['titulo' => 'Versão atual', 'integrantes' => array_slice($equipeDesenvolvimento, 2)],
            ] as $grupo)
                <div class="desenvolvedores-grupo">
                    <h3 class="desenvolvedores-versao-titulo">
                        {{ $grupo['titulo'] }}
                    </h3>

                    <div class="desenvolvedores-grade">
                        @foreach($grupo['integrantes'] as $integrante)
                            <article class="desenvolvedores-cartao">
                                <img class="desenvolvedores-foto"
                                    src="{{ asset($integrante['foto'] ?? 'images/desenvolvedor-placeholder.svg') }}"
                                    alt="{{ $integrante['foto'] ? 'Foto de ' . $integrante['nome'] : '' }}"
                                    width="112" height="112" loading="lazy">

                                <h3>{{ $integrante['nome'] }}</h3>

                                <p class="desenvolvedores-cargo">
                                    {{ $integrante['cargo'] }}
                                </p>

                                <p class="desenvolvedores-descricao">
                                    {{ $integrante['descricao'] }}
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .desenvolvedores-versoes {
        display: grid;
        grid-template-columns: minmax(0, calc(40% - 22.4px)) minmax(0, 1fr);
        column-gap: 48px;
        align-items: start;
        width: 100%;
        margin-inline: auto;
    }

    .desenvolvedores-grupo {
        min-width: 0;
    }

    .desenvolvedores .desenvolvedores-versao-titulo {
        margin: 0 0 24px;
        text-align: center;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .desenvolvedores-grupo > .desenvolvedores-grade {
        display: grid;
        gap: 16px;
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .desenvolvedores-grupo:first-child > .desenvolvedores-grade {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .desenvolvedores-grupo:last-child > .desenvolvedores-grade {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .desenvolvedores-grupo .desenvolvedores-cartao {
        width: auto;
        min-width: 0;
        max-width: none;
        margin: 0;
        text-align: center;
    }

    /* Adaptação apenas para celular. */
    @media (max-width: 600px) {
        .desenvolvedores-versoes {
            grid-template-columns: 1fr;
            gap: 32px;
        }

        .desenvolvedores-grupo:last-child > .desenvolvedores-grade {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .desenvolvedores-grupo:last-child
        > .desenvolvedores-grade
        > .desenvolvedores-cartao:last-child {
            grid-column: 1 / -1;
            width: calc((100% - 16px) / 2);
            justify-self: center;
        }
    }

    .desenvolvedores-versoes {
    align-items: stretch;
}

.desenvolvedores-grupo {
    display: flex;
    flex-direction: column;
}

.desenvolvedores-grupo > .desenvolvedores-grade {
    flex: 1;
    align-items: stretch;
}

.desenvolvedores-grupo .desenvolvedores-cartao {
    align-self: stretch;
}
</style>