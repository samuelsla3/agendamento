<?php

return [
    // Defina os valores reais apenas no .env privado do servidor.
    // Nunca preencha uma matrícula real neste arquivo ou em migrations/seeders.
    'matriculas_suap' => array_values(array_filter(array_map('trim',
        explode(',', (string) env('PSICOLOGAS_SUAP_MATRICULAS', ''))))),
    'permitir_contas_teste' => (bool) env('PSICOLOGIA_PERMITIR_CONTAS_TESTE', false),
    'prontuario_inatividade_minutos' => 30,
];
