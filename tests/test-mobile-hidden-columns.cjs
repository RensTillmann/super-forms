// Node contract tests for the shipped client; DOM/computed styles are adapters, not browser evidence.
const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const root=path.join(__dirname,'..');
const prefix=fs.existsSync(path.join(root,'src/assets/js/common.js'))?'src/':'';
const source=fs.readFileSync(process.env.SF_CLIENT_SOURCE||path.join(root,prefix+'assets/js/common.js'),'utf8');
const css=fs.readFileSync(process.env.SF_CSS_SOURCE||path.join(root,prefix+'assets/css/frontend/elements.css'),'utf8');
function node(classes=[],parent=null,display='block') {
    const set=new Set(classes);
    return {parentNode:parent,parentElement:parent,style:{},dataset:{},display,
        classList:{contains:k=>set.has(k),add:k=>set.add(k),remove:k=>set.delete(k)},
        closest(selector){for(let n=this;n;n=n.parentElement) if(selector.split(',').some(s=>s.trim().slice(1).split('.').every(c=>n.classList.contains(c)))) return n;return null;},
        querySelector:()=>null,querySelectorAll:()=>[]};
}
function fixture({mobile='super-hide-mobile',display='none',nested=false,invisible=false,conditional=false,stale=false}={}) {
    const form=node(['super-form']);
    const column=node(['super-column',...(mobile?[mobile]:[]),...(invisible?['super-invisible']:[])],form,display);
    if(conditional){column.dataset.conditionalAction='hide';column.classList.add('super-conditional-hidden');}
    const wrapper=node(['super-shortcode','super-field',...(stale?['super-error-active']:[])],nested?node(['super-column'],column):column);
    const field=node(['super-shortcode-field'],wrapper);field.name='sample';field.value='';field.dataset.validation='empty';
    function wrap(items){
        const q={ready(){return q;},on(){return q;},length:items.length,each(fn){items.forEach((n,i)=>fn.call(n,i,n));return q;},
            parents(selector){const found=[];for(let n=items[0]?.parentElement;n;n=n.parentElement) if(selector.split(',').some(s=>n.classList.contains(s.trim().replace(/:eq\(0\)/,'').slice(1)))) found.push(n);return wrap(selector.includes(':eq')?found.slice(0,1):found);},
            filter(fn){return wrap(items.filter((n,i)=>fn.call(n,i,n)));},
            css(key){assert.equal(key,'display');return items[0]?.display;},
            hasClass(c){return !!items[0]?.classList.contains(c);},attr(k){return items[0]?.[k];},
            val(){return items[0]?.value;},data(k){return items[0]?.dataset[k];},
            find(){return wrap([field]);}};
        items.forEach((n,i)=>q[i]=n);return q;
    }
    const empty=new Proxy({}, {get:(_,k)=>k==='length'?0:()=>empty});
    function jq(arg){return typeof arg==='function'?empty:wrap(arg?[arg]:[]);}
    jq.extend=Object.assign;
    function Element(){} function CharacterData(){} function DocumentType(){}
    const ctx={SUPER:{},jQuery:jq,Element,CharacterData,DocumentType,console,Promise,
        document:{documentElement:{classList:{contains:()=>false}}},super_common_i18n:{ajaxurl:'/no-network'},setTimeout:()=>0,clearTimeout:()=>{},setInterval:()=>0,clearInterval:()=>{}};
    ctx.window=ctx;vm.runInNewContext(source,ctx);
    const S=ctx.SUPER;
    for(const name of ['resetFocussedFields','conditional_logic','before_validating_form_hook','after_validating_form_hook','remove_error_status_parent_layout_element','refresh_dynamic_submission_routes']) S[name]=()=>{};
    S.before_submit_button_click_hook=()=>true;
    S.field=()=>[field];S.get_submission_route_name=()=>field.name;
    let validated=0,scrolled=0;
    S.handle_validations=()=>{validated++;wrapper.classList.add('super-error-active');return true;};
    S.scrollToError=()=>scrolled++;
    form.querySelectorAll=s=>s==='.super-error-active'&&wrapper.classList.contains('super-error-active')?[wrapper]:[];
    const button=node();
    function validate(){S.validate_form({form,submitButton:button,validateMultipart:true});return {validated,scrolled,stale:wrapper.classList.contains('super-error-active')};}
    return {S,field,column,wrapper,validate,payload:()=>S.prepare_form_data_fields(jq(form))};
}
for(const mobile of ['super-hide-mobile','super-hide-mobile-window']) {
    test(mobile+': computed-hidden nested required fields skip validation and payload',()=>{
        const f=fixture({mobile,nested:true});assert.deepEqual(f.validate(),{validated:0,scrolled:0,stale:false});assert.equal(Object.hasOwn(f.payload(),'sample'),false);
    });
    test(mobile+': visible required fields remain validated and included',()=>{
        const f=fixture({mobile,display:'block'});assert.equal(f.validate().validated,1);assert.equal(Object.hasOwn(f.payload(),'sample'),true);
    });
    test(mobile+': resizing clears stale hidden errors and validates again when visible',()=>{
        const f=fixture({mobile,stale:true});assert.deepEqual(f.validate(),{validated:0,scrolled:0,stale:false});
        f.column.display='block';assert.equal(f.validate().validated,1);assert.equal(Object.hasOwn(f.payload(),'sample'),true);
    });
}
test('arbitrary theme-hidden required columns retain validation and submission',()=>{
    const f=fixture({mobile:null});assert.equal(f.validate().validated,1);assert.equal(Object.hasOwn(f.payload(),'sample'),true);
});
test('invisible columns still skip validation but keep their submitted value',()=>{
    const f=fixture({mobile:null,invisible:true});assert.equal(f.validate().validated,0);assert.equal(Object.hasOwn(f.payload(),'sample'),true);
});
test('conditional hiding still skips validation and payload',()=>{
    const f=fixture({mobile:null,conditional:true});assert.equal(f.validate().validated,0);assert.equal(Object.hasOwn(f.payload(),'sample'),false);
});
test('all four responsive hide selectors tie or outrank the column display rule and occur later',()=>{
    const base='.super-form:not(.super-preview-elements) * .super-column';
    const baseIndex=css.indexOf(base);assert.notEqual(baseIndex,-1);
    const selectors=[
        '.super-first-responsiveness .super-column.super-hide-mobile',
        '.super-second-responsiveness .super-column.super-hide-mobile',
        '.super-window-first-responsiveness .super-column.super-hide-mobile-window',
        '.super-window-second-responsiveness .super-column.super-hide-mobile-window'];
    for(const selector of selectors){
        const index=css.indexOf(selector);assert.ok(index>baseIndex,selector);
        const declaration=css.slice(index,css.indexOf('}',index));assert.match(declaration,/display:\s*none\s*;/);
        assert.ok((selector.match(/\.[\w-]+/g)||[]).length>=3,selector);
    }
});
