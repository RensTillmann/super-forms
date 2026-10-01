const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, fs.existsSync(path.join(root, 'src/assets/js/common.js')) ? 'src/assets/js/common.js' : 'assets/js/common.js'), 'utf8');
const start = source.indexOf('        if( $print_file');
assert(start >= 0);
const branch = source.slice(start, source.indexOf('    };', start));
const oldToken = 'a'.repeat(64), freshToken = 'b'.repeat(64), nextToken = 'c'.repeat(64);
const cases = [
    {name:'expired refreshes and retries', token:oldToken, replies:['reject','refresh','html'], actions:['super_print_custom_html','super_create_nonce','super_print_custom_html'], printed:1},
    {name:'missing refreshes before print', token:'', replies:['refresh','html'], actions:['super_create_nonce','super_print_custom_html'], printed:1},
    {name:'success needs no refresh', token:oldToken, replies:['html'], actions:['super_print_custom_html'], printed:1},
    {name:'denied refresh falls back', token:oldToken, replies:['reject','denied'], actions:['super_print_custom_html','super_create_nonce'], printed:0},
    {name:'second rejection stops', token:oldToken, replies:['reject','refresh','reject'], actions:['super_print_custom_html','super_create_nonce','super_print_custom_html'], printed:0},
    {name:'refresh network failure falls back', token:'', replies:['network'], actions:['super_create_nonce'], printed:0},
    {name:'print network failure falls back', token:oldToken, replies:['network'], actions:['super_print_custom_html'], printed:0}
];
for(const scenario of cases) {
    const actions = [], input = {value:scenario.token}; let printed=0, fallback=0, prepared=0;
    const decode = value => {try{return JSON.parse(value);}catch{return false;}};
    const $ = value => value;
    $.ajax = options => {
        actions.push(options.data.action);
        assert(actions.length <= scenario.replies.length, scenario.name + ': unbounded request');
        assert.equal(options.data.nonce, 'form-nonce');
        if(options.data.action==='super_create_nonce') {
            assert.equal(options.data.form_id,42);assert.equal(options.data.print_file_id,'73');
        } else {
            assert.equal(options.data.file_id,'73');
            assert.equal(options.data.capability, actions.includes('super_create_nonce') ? freshToken : oldToken);
            assert.equal(options.data.data.field,'value');
        }
        const reply=scenario.replies[actions.length-1];
        if(reply==='network') return options.error();
        const body=reply==='reject' ? '{"error":true}' : reply==='refresh' ? JSON.stringify({print_capability:freshToken}) : reply==='denied' ? '{"print_capability":""}' : '<html>custom print</html>';
        return options.success(body,'success',{getResponseHeader:()=>nextToken});
    };
    vm.runInNewContext('(function(){'+branch+'})()', {
        $, $print_file:{value:'73'}, $print_capability:input, $file_id:null,
        args:{form:{id:'super-form-42',querySelector:()=>({value:'form-nonce'})},form0:{}},
        super_common_i18n:{ajaxurl:'/ajax'}, decode_json_response:decode,
        SUPER:{decode_json_response:decode,prepare_form_data:(form,callback)=>{prepared++;callback({data:{field:'value'}});},after_form_data_collected_hook:data=>data},
        print_default_form:()=>{fallback++;},print_window:html=>{assert.equal(html,'<html>custom print</html>');printed++;}
    });
    assert.deepEqual(actions,scenario.actions,scenario.name);
    assert.equal(printed,scenario.printed,scenario.name);
    assert.equal(fallback,scenario.printed ? 0 : 1,scenario.name);
    assert.equal(prepared,1,scenario.name);
    if(printed) assert.equal(input.value,nextToken,'success rotates capability');
    console.log('PASS '+scenario.name);
}
