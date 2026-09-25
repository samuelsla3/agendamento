const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const {webcrypto} = require('node:crypto');

// Testa o contrato da interface com uma rede controlada, sem dependências npm.
function ambiente() {
    class Elemento {
        constructor(tag='BUTTON') { this.tagName=tag; this.disabled=false; this.innerHTML='Salvar'; this.value=''; this.style={cursor:''}; this.attrs={}; this.dataset={}; }
        set textContent(v) { this.innerHTML=v; }
        get textContent() { return this.innerHTML; }
        setAttribute(k,v) { this.attrs[k]=v; }
        getAttribute(k) { return this.attrs[k] ?? null; }
        removeAttribute(k) { delete this.attrs[k]; }
        remove() { if(this.parent) this.parent.aviso=null; }
    }
    const botao=new Elemento();
    class Formulario extends Elemento {
        constructor() { super('FORM'); this.method='post'; this.aviso=null; }
        querySelectorAll() { return [botao]; }
        querySelector() { return this.aviso; }
        append(el) { this.aviso=el; el.parent=this; }
    }
    const callbacks={};const requests=[];const errors=[];
    const window={crypto:webcrypto,alert:m=>errors.push(m),addEventListener:(n,fn)=>callbacks[n]=fn,
        jQuery:{ajax:opts=>{requests.push(opts);return opts;}}};
    const context=vm.createContext({window,HTMLFormElement:Formulario,
        document:{querySelectorAll:()=>[botao],createElement:tag=>new Elemento(tag.toUpperCase())},
        LaravelConfig:{csrfToken:'teste'}});
    vm.runInContext(fs.readFileSync(path.join(__dirname,'../../public/js/operacoes.js'),'utf8'),context);
    const enviar=(valor=1,navegar=false)=>window.Operacoes.ajax({url:'/acao',data:{valor},json:true,navegar,erro:m=>errors.push(m)});
    const concluir=(req,status=200)=>{
        if(status===200)req.success({status:'success',message:'Concluído'});
        else req.error({status,responseJSON:{message:'Teste'}});
        req.complete();
    };
    return {window,Formulario,botao,callbacks,requests,errors,enviar,concluir};
}
test('vários cliques geram um POST; sucesso restaura o botão',()=>{
    const a=ambiente();a.enviar();a.enviar();a.enviar();
    assert.equal(a.requests.length,1);assert.equal(a.botao.disabled,true);
    a.concluir(a.requests[0]);assert.equal(a.botao.disabled,false);assert.equal(a.botao.innerHTML,'Salvar');
});
test('erro incerto conserva a chave e impede envio de dados diferentes',()=>{
    const a=ambiente();a.enviar();a.concluir(a.requests[0],500);
    const chave=JSON.parse(a.requests[0].data)._operation_id;
    a.enviar(2);assert.equal(a.requests.length,1);
    a.enviar();assert.equal(a.requests.length,2);
    assert.equal(JSON.parse(a.requests[1].data)._operation_id,chave);
    a.concluir(a.requests[1]);assert.equal(a.botao.disabled,false);
});
test('sucesso com navegação mantém bloqueio; erro de validação libera correção',()=>{
    const a=ambiente();a.enviar(1,true);a.concluir(a.requests[0]);
    assert.equal(a.botao.disabled,true);a.enviar();assert.equal(a.requests.length,1);
    const b=ambiente();b.enviar();b.concluir(b.requests[0],422);b.enviar(2);
    assert.equal(b.requests.length,2);
    assert.notEqual(JSON.parse(b.requests[0].data)._operation_id,JSON.parse(b.requests[1].data)._operation_id);
});
test('submit nativo é bloqueado uma vez, respeita cancelamento e restaura no pageshow',()=>{
    const a=ambiente();const form=new a.Formulario();
    const evento=(cancelado=false)=>({target:form,defaultPrevented:cancelado,preventDefault(){this.defaultPrevented=true;}});
    a.callbacks.submit(evento(true));assert.equal(a.botao.disabled,false);
    a.callbacks.submit(evento());assert.equal(a.botao.disabled,true);
    const segundo=evento();a.callbacks.submit(segundo);assert.equal(segundo.defaultPrevented,true);
    a.callbacks.pageshow();assert.equal(a.botao.disabled,false);assert.equal(form.aviso,null);
});
