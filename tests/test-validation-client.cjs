// Executes the complete shipped client; DOM adapters stop at the UI boundary.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function fixture() {
    const chain = new Proxy({}, {get: (_, key) => key === 'length' ? 0 : () => chain});
    const jq = () => chain;
    jq.extend = Object.assign;
    jq.ajax = () => {throw Error('Unexpected network');};
    function Element() {}
    function CharacterData() {}
    function DocumentType() {}
    const ctx = {SUPER:{}, jQuery:jq, Element, CharacterData, DocumentType, console, Promise,
        document:{documentElement:{classList:{contains:()=>false}}},
        super_common_i18n:{ajaxurl:'/no-network'}, setTimeout:()=>0, clearTimeout:()=>{}};
    ctx.window = ctx;
    vm.runInNewContext(fs.readFileSync(process.env.SF_CLIENT_SOURCE || path.join(__dirname,'../assets/js/common.js'),'utf8'),ctx);
    return ctx.SUPER;
}
function field(value, kind='text') {
    const set = new Set(['super-field','super-shortcode',`super-${kind}`]);
    const parent = {classList:{contains:k=>set.has(k), add:k=>set.add(k), remove:k=>set.delete(k)},
        querySelector:()=>null, querySelectorAll:()=>[], closest:()=>null};
    const el = {value, dataset:{mayBeEmpty:'false'}, tagName:'INPUT',parentNode:parent,
        classList:{contains:()=>false},closest:s=>s==='.super-field'||s==='.super-shortcode'?parent:null};
    return el;
}
function validate(S,value,validation,kind) {
    const el=field(value,kind);
    return S.handle_validations({el,validation,form:{querySelectorAll:()=>[]}});
}
test('email UI rejects underscores and edge hyphens in domain labels',()=>{
    const S=fixture();
    for(const email of ['ops@backup_mx1.corp.example.com','ops@-backup.example.com','ops@backup-.example.com']) {
        assert.equal(validate(S,email,'email'),true,email);
    }
});
test('email UI retains valid local-part characters, hyphens and long domain suffixes',()=>{
    const S=fixture();
    for(const email of ['ops@backup-mx1.corp.example.com','ops_test+tag@example.technology','a@example.com',"o'neil@example.com"]) {
        assert.equal(validate(S,email,'email'),false,email);
    }
});
test('currency numeric/float validate formatted amounts without changing the display',()=>{
    const S=fixture();
    for(const [value,validation,dataset,expected] of [
        ['$1,234.00','float',{},false],
        ['€1.234,56','float',{currency:'€',thousandSeparator:'.',decimalSeparator:','},false],
        ['1.234,56','float',{currency:'',thousandSeparator:'.',decimalSeparator:','},false],
        ['$1,234.00','numeric',{},false],
        ['$1,234.50','numeric',{},true],
        ['$1,,234.00','float',{},true],
        ['$1,234.00oops','float',{},true],
        ['1234.56','float',{},false]
    ]) {
        const el=field(value,'currency');
        Object.assign(el.dataset,{currency:'$',format:'',decimals:'2',thousandSeparator:',',decimalSeparator:'.'},dataset);
        assert.equal(S.handle_validations({el,validation,form:{}}),expected,value+' '+validation);
        assert.equal(el.value,value,'validation must preserve display/submission bytes');
    }
    assert.equal(validate(S,'$1,234.00','float','text'),true);
});

test('optional empty email still accepted; malformed email still rejected',()=>{
    const S=fixture();
    const el=field('');el.dataset.mayBeEmpty='true';
    assert.equal(S.handle_validations({el,validation:'email',form:{}}),false);
    for(const value of ['no-at-sign','a@foo..com','a@.com','a@example.com\n']) assert.equal(validate(S,value,'email'),true,value);
});
