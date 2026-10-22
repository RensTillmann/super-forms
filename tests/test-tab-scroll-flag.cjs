// Tab navigation: SUPER.disable_tab_scroll skips the auto-centre scroll; default is unchanged.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const clientSource = path.join(__dirname, '../src/assets/js/common.js');

function load() {
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
    ctx.window = ctx; ctx.innerHeight = 800; ctx.requestAnimationFrame = () => 0;
    vm.runInNewContext(fs.readFileSync(clientSource, 'utf8'), ctx);
    return ctx.SUPER;
}
function nextField(log) {
    const none = {classList:{contains:()=>false, add(){}, remove(){}}, querySelector:()=>null, querySelectorAll:()=>[], closest:()=>null};
    const el = {classList:{contains:()=>false, add(){}, remove(){}}, closest:()=>null, parentNode:none,
        querySelector:()=>null, querySelectorAll:()=>[], focus(){log.push('focus');}, getBoundingClientRect:()=>({top:-500,bottom:-400,height:20}),
        scrollIntoView(){log.push('scroll');}};
    return el;
}
function press(S, flag) {
    const log = [];
    if (flag !== undefined) S.disable_tab_scroll = flag;
    const form = {querySelectorAll:()=>[]};
    try {
        S.focusNextTabField({type:'keydown', keyCode:9, which:9}, null, form, nextField(log));
    } catch (err) {
        // Code after the scroll step may need a real DOM; only the scroll decision matters here.
    }
    return log;
}
test('Tab scrolls the next field into view by default', () => {
    assert.ok(press(load()).includes('scroll'));
});
test('Tab does not scroll when SUPER.disable_tab_scroll is true', () => {
    assert.ok(!press(load(), true).includes('scroll'));
});
test('only a strict true disables the scroll', () => {
    assert.ok(press(load(), 'yes').includes('scroll'));
});
