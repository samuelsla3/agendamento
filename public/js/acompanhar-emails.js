(() => {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const retorno = document.getElementById('emails-retorno');
        const mostrarErro = mensagem => {
            retorno.hidden = false;
            retorno.classList.add('emails-mensagem--erro');
            retorno.textContent = mensagem;
            retorno.scrollIntoView({block: 'nearest', behavior: 'smooth'});
        };
        document.querySelectorAll('.emails-reenvio').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                const incerto = form.dataset.estado === 'revisar';
                const confirmado = form.querySelector('[name="confirmou_incerto"]')?.checked === true;
                if (incerto && !confirmado) {
                    mostrarErro('Confira o envio anterior e marque a confirmação antes de tentar novamente.');
                    return;
                }
                const texto = incerto
                    ? 'Tentar enviar este aviso novamente após conferir o envio anterior? Se já tiver sido recebido, o aluno pode receber outra cópia.'
                    : 'Tentar enviar este aviso novamente? O agendamento ou cancelamento não será alterado.';
                if (!window.confirm(texto)) return;
                Operacoes.ajax({
                    grupo: 'emails', url: form.action,
                    data: {versao: form.querySelector('[name="versao"]').value, confirmou_incerto: confirmado ? 1 : 0},
                    botoes: '.emails-reenvio button[type="submit"]', texto: 'Tentando enviar…', navegar: true,
                    sucesso(response) {
                        retorno.hidden = false;
                        retorno.classList.remove('emails-mensagem--erro');
                        retorno.textContent = response.message;
                        window.location.reload();
                    },
                    erro: mostrarErro,
                });
            });
        });
    });
})();
