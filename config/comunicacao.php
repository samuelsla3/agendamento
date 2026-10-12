<?php

return [
    'texto_maximo' => 10000,
    'arquivos_por_mensagem' => 3,
    'arquivo_maximo_bytes' => 10 * 1024 * 1024,
    'anexos_diarios_bytes' => 100 * 1024 * 1024,
    'armazenamento_maximo_bytes' => 2 * 1024 * 1024 * 1024,
    'mensagens_por_hora_aluno' => 40,
    'mensagens_por_hora_psicologa' => 200,
    'consultas_intervalo_segundos' => 30,
    // Opcional: caminho privado para clamscan/clamdscan. Nunca usa argumentos do aluno.
    'antivirus_binario' => env('COMUNICACAO_ANTIVIRUS_BIN', ''),
];
