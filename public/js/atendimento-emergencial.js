/* O cadastro é sempre confirmado novamente pelo servidor, usando a matrícula. */
(function () {
    'use strict';
    const campo = (sufixo) => document.getElementById('emergencial-' + sufixo);
    const form = campo('form');
    if (!form) return;
    const modal = document.getElementById('emergencialModal');
    const nome = campo('nome'), matricula = campo('matricula');
    const turma = campo('turma'), email = campo('email'), id = campo('aluno-id');
    const aviso = campo('aviso'), data = campo('data'), hora = campo('hora');
    const opcoes = Array.from(campo('alunos').options);
    const titulo = document.getElementById('emergencial-titulo');
    const enviar = form.querySelector('button[type="submit"]');
    let selecionado = null;

    function selecionar(opcao) {
        selecionado = opcao;
        id.value = opcao.dataset.id;
        nome.value = opcao.value;
        matricula.value = opcao.dataset.matricula;
        turma.value = opcao.dataset.turma;
        email.value = opcao.dataset.email;
        turma.readOnly = email.readOnly = true;
        nome.setCustomValidity('');
        aviso.textContent = 'Aluno cadastrado selecionado. Os dados serão os do cadastro existente.';
    }

    function limparSelecao(origem) {
        if (selecionado) {
            if (origem === nome) matricula.value = '';
            else nome.value = '';
            turma.value = email.value = '';
        }
        selecionado = null;
        id.value = '';
        turma.readOnly = email.readOnly = false;
        aviso.textContent = 'Para aluno novo, informe nome completo e matrícula. O primeiro acesso será pelo SUAP, com essa mesma matrícula.';
    }

    nome.addEventListener('input', function () {
        nome.setCustomValidity('');
        const opcao = opcoes.find((o) => o.value === nome.value);
        if (opcao) selecionar(opcao);
        else limparSelecao(nome);
    });
    matricula.addEventListener('input', function () {
        if (selecionado && matricula.value !== selecionado.dataset.matricula) limparSelecao(matricula);
        const opcao = opcoes.find((o) => o.dataset.matricula === matricula.value.trim());
        if (opcao) selecionar(opcao);
    });
    matricula.addEventListener('change', function () {
        const opcao = opcoes.find((o) => o.dataset.matricula === matricula.value.trim());
        if (opcao) selecionar(opcao);
    });

    window.abrirModalEmergencial = function () {
        if (form.dataset.action === 'agendar_aluno') { data.value = ''; hora.value = ''; }
        form.dataset.action = 'atendimento_emergencial';
        delete form.dataset.horarioId; delete form.dataset.versao;
        data.readOnly = hora.readOnly = false;
        titulo.textContent = 'Atendimento Emergencial';
        document.getElementById('emergencial-vaga-info').hidden = true;
        enviar.textContent = 'Registrar atendimento';
        // Usa o fuso do sistema, sem converter a data local para UTC.
        // Preserva o formulário ao fechar/reabrir, inclusive em uma tentativa de reenvio.
        const partes = new Intl.DateTimeFormat('en-CA', {
            timeZone: form.dataset.fuso, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
        }).formatToParts(new Date());
        const agora = Object.fromEntries(partes.map((p) => [p.type, p.value]));
        if (!data.value) data.value = `${agora.year}-${agora.month}-${agora.day}`;
        if (!hora.value) hora.value = `${agora.hour}:${agora.minute}`;
        modal.classList.add('is-visible');
        nome.focus();
    };
    document.getElementById('agendarAlunoBtn')?.addEventListener('click', function () {
        if (this.disabled || !this.dataset.horarioId || !this.dataset.versao) return;
        form.dataset.action = 'agendar_aluno';
        form.dataset.horarioId = this.dataset.horarioId;
        form.dataset.versao = this.dataset.versao;
        data.value = this.dataset.data; hora.value = this.dataset.hora;
        data.readOnly = hora.readOnly = true;
        titulo.textContent = 'Agendar aluno(a)';
        document.getElementById('emergencial-vaga-info').hidden = false;
        enviar.textContent = 'Confirmar agendamento';
        window.closeModal();
        modal.classList.add('is-visible');
        nome.focus();
    });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        // A matrícula digitada também reconhece um cadastro existente.
        const opcao = opcoes.find((o) => o.dataset.matricula === matricula.value.trim());
        if (opcao) selecionar(opcao);
        const nomeFinal = selecionado ? selecionado.dataset.nome : nome.value.trim();
        nome.setCustomValidity(nomeFinal.length > 100 ? 'O nome deve ter até 100 caracteres.' : '');
        if (!form.reportValidity()) return;
        const dados = {
            action: form.dataset.action || 'atendimento_emergencial', data: data.value, hora: hora.value,
            aluno_id: id.value, nome: nomeFinal, matricula: matricula.value.trim(),
            turma_codigo: turma.value.trim(), email: email.value.trim()
        };
        if (dados.action === 'agendar_aluno') {
            dados.id = form.dataset.horarioId; dados.versao = form.dataset.versao;
        }
        sendAjaxRequest(dados);
    });
    modal.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') window.closeModal();
    });
})();
