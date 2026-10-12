(function (root) {
    'use strict';

    function criar(config = {}, relogio = {
        agora: () => Date.now(),
        decorrido: () => performance.now()
    }) {
        const referenciaServidor = Date.parse(config.agora);
        const referenciaDecorrido = relogio.decorrido();
        const ativa = config.ativa === true;
        const minutos = Number(config.minutos) || 120;
        const descricao = config.descricao || `${minutos} minutos`;

        // Usa o horário informado pelo servidor, com o tempo decorrido na página.
        // O relógio e o fuso do aparelho não definem a antecedência da reserva.
        function agora() {
            return Number.isFinite(referenciaServidor)
                ? referenciaServidor + relogio.decorrido() - referenciaDecorrido
                : relogio.agora();
        }

        function inicioEmMs(inicio) {
            return new Date(inicio).getTime();
        }

        return Object.freeze({
            ativa,
            minutos,
            mensagem: `É necessário agendar com pelo menos ${descricao} de antecedência.`,
            agora,
            estaNoPassado(inicio) {
                return inicioEmMs(inicio) < agora();
            },
            bloqueia(inicio) {
                if (!ativa) return false;
                const instante = inicioEmMs(inicio);
                return !Number.isFinite(instante) || instante < agora() + minutos * 60000;
            }
        });
    }

    root.AntecedenciaAgendamento = Object.freeze({ criar });
})(globalThis);
