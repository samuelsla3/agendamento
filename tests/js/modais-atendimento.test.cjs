const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function tela() {
    const elementos = new Map();
    const get = (id) => {
        if (!elementos.has(id)) elementos.set(id, {visivel: false, attrs: {}, dados: {}, texto: '', valor: '', handlers: {}});
        return elementos.get(id);
    };
    function $(seletor) {
        const ids = typeof seletor === 'string' ? seletor.split(',').map(s => s.trim()) : ['document'];
        const nodes = ids.map(get);
        const chain = {
            hide() { nodes.forEach(n => n.visivel = false); return chain; },
            show() { nodes.forEach(n => n.visivel = true); return chain; },
            val(v) { if (v === undefined) return nodes[0].valor; nodes.forEach(n => n.valor = v); return chain; },
            text(v) { if (v === undefined) return nodes[0].texto; nodes.forEach(n => n.texto = v); return chain; },
            data(k, v) { if (v === undefined) return nodes[0].dados[k]; nodes.forEach(n => n.dados[k] = v); return chain; },
            attr(k, v) { if (v === undefined) return nodes[0].attrs[k]; nodes.forEach(n => n.attrs[k] = v); return chain; },
            css(k, v) { if (k === 'display') nodes.forEach(n => n.visivel = v !== 'none'); return chain; },
            click(fn) { nodes.forEach(n => n.handlers.click = fn); return chain; },
            on() { return chain; }, submit() { return chain; }, prop() { return chain; },
            addClass() { return chain; }, removeClass() { return chain; }
        };
        return chain;
    }
    const pedidos = [];
    const context = { $, Date, alert() {}, location: {reload() {}},
        document: {addEventListener() {}, getElementById() { return null; }},
        window: {matchMedia() { return {matches: false, addEventListener() {}}; }},
        Operacoes: {ajax(dados) { pedidos.push(dados); }},
        LaravelConfig: {rotas: {acao: '/agenda/acao'}}
    };
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../../public/js/psicologa.js'), 'utf8'), context);
    function abrir(origem, {passado = false, confirmado = false, disponivel = false} = {}) {
        const props = {versao: 'v'.repeat(64), disponivel: +disponivel, confirmado: +confirmado,
            nome: 'Aluno Teste', turma_formatada: '3.18.1I', matricula: '00123', aluno_id: 23};
        if (origem === 'calendario') context.openModal({id: '1', extendedProps: props,
            start: new Date(passado ? '2000-01-01T09:00:00' : '2099-01-01T09:00:00')});
        else {
            const attrs = {'data-id': '1', 'data-alunoid': '23', 'data-disponivel': String(+disponivel),
                'data-confirmado': String(+confirmado), 'data-nome': props.nome, 'data-matricula': props.matricula,
                'data-turma': props.turma_formatada, 'data-ispast': String(+passado), 'data-versao': props.versao};
            context.abrirAcoesHoje({getAttribute(k) { return attrs[k] ?? null; }});
        }
    }
    return {get, abrir, pedidos};
}
for (const origem of ['calendario', 'operar']) {
    test(`${origem}: permite confirmar e cancelar antes do horário`, () => {
        const t = tela(); t.abrir(origem);
        assert.equal(t.get('#confirmBtn').visivel, true);
        assert.equal(t.get('#cancelByPsicologaBtn').visivel, true);
        assert.equal(t.get('#prontuarioBtn').visivel, true);
        t.get('#confirmBtn').handlers.click();
        assert.equal(t.pedidos[0].data.action, 'confirmar');
        assert.equal(t.pedidos[0].data.id, '1');
        assert.equal(t.pedidos[0].data.versao, 'v'.repeat(64));
    });
    test(`${origem}: mantém confirmação e cancelamento após o horário`, () => {
        const t = tela(); t.abrir(origem, {passado: true});
        assert.equal(t.get('#confirmBtn').visivel, true);
        assert.equal(t.get('#cancelByPsicologaBtn').visivel, true);
        assert.equal(t.get('#deleteBtn').visivel, false);
    });
    test(`${origem}: concluído não oferece nova confirmação ou cancelamento`, () => {
        const t = tela(); t.abrir(origem); t.abrir(origem, {confirmado: true});
        for (const id of ['#confirmBtn', '#cancelByPsicologaBtn', '#deleteBtn']) assert.equal(t.get(id).visivel, false);
        assert.equal(t.get('#prontuarioBtn').visivel, true);
        assert.equal(t.get('#agendadoStatus').texto, 'Realizado / Concluído');
    });
}
test('calendário: uma vaga livre não pode ser confirmada como atendimento', () => {
    const t = tela(); t.abrir('calendario', {disponivel: true});
    assert.equal(t.get('#confirmBtn').visivel, false);
    assert.equal(t.get('#cancelByPsicologaBtn').visivel, false);
});
