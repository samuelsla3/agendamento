const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function tela() {
    const elementos = new Map();
    function elemento(id) {
        if (!elementos.has(id)) elementos.set(id, {
            value: '', readOnly: false, dataset: {}, handlers: {}, textContent: '', validation: '',
            classList: {add() {}}, focus() {},
            setCustomValidity(v) { this.validation = v; },
            addEventListener(t, f) { this.handlers[t] = f; },
            dispatch(t) { this.handlers[t]?.({preventDefault() {}}); }
        });
        return elementos.get(id);
    }
    const campo = (id) => elemento('emergencial-' + id);
    campo('form').dataset.fuso = 'America/Bahia';
    campo('form').reportValidity = () => !campo('nome').validation;
    campo('alunos').options = [
        {value: 'Aluno Teste (00123)', dataset: {id: '23', nome: 'Aluno Teste', matricula: '00123', turma: '3.18.1I', email: 'aluno@example.test'}},
        {value: 'Aluno Teste (00456)', dataset: {id: '24', nome: 'Aluno Teste', matricula: '00456', turma: '4.18.1I', email: ''}}
    ];
    const envios = [];
    const window = {closeModal() {}};
    const context = {window, document: {getElementById: elemento}, Intl,
        Date: class extends Date { constructor() { super('2026-10-04T01:15:00Z'); } },
        sendAjaxRequest(dados) { envios.push(dados); }};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/atendimento-emergencial.js'), 'utf8'), context);
    const editar = (id, valor, evento = 'input') => { campo(id).value = valor; campo(id).dispatch(evento); };
    return {campo, window, editar, envios};
}

test('usa o dia e horário do campus e preserva a edição ao reabrir', () => {
    const t = tela(); t.window.abrirModalEmergencial();
    assert.equal(t.campo('data').value, '2026-10-03');
    assert.equal(t.campo('hora').value, '22:15');
    t.editar('data', '2026-09-30'); t.editar('hora', '08:32');
    t.window.abrirModalEmergencial();
    assert.equal(t.campo('data').value, '2026-09-30');
    assert.equal(t.campo('hora').value, '08:32');
});

test('distingue homônimos e envia matrícula textual com zeros iniciais', () => {
    const t = tela(); t.window.abrirModalEmergencial();
    t.editar('nome', 'Aluno Teste (00456)');
    assert.equal(t.campo('matricula').value, '00456');
    assert.equal(t.campo('turma').value, '4.18.1I');
    assert.equal(t.campo('email').readOnly, true);
    t.campo('form').dispatch('submit');
    assert.equal(t.envios[0].aluno_id, '24');
    assert.equal(t.envios[0].nome, 'Aluno Teste');
    assert.equal(t.envios[0].matricula, '00456');
});

test('trocar nome limpa os dados do aluno anterior, permitindo cadastro novo', () => {
    const t = tela(); t.editar('nome', 'Aluno Teste (00123)');
    t.editar('nome', 'Aluno Novo');
    for (const id of ['aluno-id', 'matricula', 'turma', 'email']) assert.equal(t.campo(id).value, '');
    assert.equal(t.campo('turma').readOnly, false);
    t.editar('matricula', '00999'); t.editar('turma', '3.18.1I');
    t.campo('form').dispatch('submit');
    assert.equal(t.envios[0].nome, 'Aluno Novo');
    assert.equal(t.envios[0].aluno_id, '');
});

test('reconhece matrícula existente mesmo sem selecionar o nome', () => {
    const t = tela(); t.editar('nome', 'Texto diferente');
    t.editar('matricula', '00123', 'change');
    assert.equal(t.campo('nome').value, 'Aluno Teste (00123)');
    assert.equal(t.campo('email').value, 'aluno@example.test');
    t.campo('form').dispatch('submit');
    assert.equal(t.envios[0].aluno_id, '23');
});

test('editar matrícula não reutiliza a identidade anterior e permite outra seleção', () => {
    const t = tela(); t.editar('nome', 'Aluno Teste (00123)');
    t.editar('matricula', '00999');
    assert.equal(t.campo('aluno-id').value, '');
    assert.equal(t.campo('nome').value, '');
    assert.equal(t.campo('email').value, '');
    t.editar('nome', 'Aluno Teste (00456)');
    assert.equal(t.campo('aluno-id').value, '24');
});

test('impede enviar nome novo maior que o campo do banco', () => {
    const t = tela(); t.editar('nome', 'A'.repeat(101)); t.editar('matricula', '00999');
    t.campo('form').dispatch('submit'); assert.equal(t.envios.length, 0);
    t.editar('nome', 'Nome correto'); t.campo('form').dispatch('submit');
    assert.equal(t.envios.length, 1);
});
