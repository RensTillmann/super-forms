const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, fs.existsSync(path.join(root, 'src/assets/js/frontend/elements.js')) ? 'src/assets/js/frontend/elements.js' : 'assets/js/frontend/elements.js'), 'utf8');
// Document listeners registered before the keydown handler: the pointer reset and the focusin handler.
const start = source.indexOf('        var $doc = $(document);');
const end = source.indexOf('        $doc.keydown(function(e){', start);
assert(start >= 0 && end > start);
const block = source.slice(start, end);

function setup() {
    const jq = {}, native = {}, focussed = [];
    const $doc = {on: (type, fn) => { (jq[type] = jq[type] || []).push(fn); }};
    const classes = () => { const set = new Set(); return {add: c => set.add(c), remove: c => set.delete(c), contains: c => set.has(c)}; };
    const fields = ['first', 'middle', 'last'].map(name => ({name}));
    const form = {tagName: 'FORM', tabIndex: 0, classList: classes(), blur() {},
        closest: sel => (sel === '.super-form' || sel === 'form') ? form : null,
        querySelectorAll: () => fields};
    const button = {tagName: 'DIV', classList: classes(), closest: sel => (sel === '.super-form' || sel === 'form') ? form : null};
    const SUPER = {
        focusForm: el => { if(el && el.classList) el.classList.add('super-form-focussed'); },
        focusField() {}, has_hidden_parent: () => false,
        focusNextTabField: (e, field) => { focussed.push(field.name); }
    };
    const document = {addEventListener: (type, fn, options) => {
        const capture = options === true || (options && options.capture === true);
        (native[type] = native[type] || []).push({fn, capture});
    }};
    vm.runInNewContext('(function(){' + block + '})()', {$: () => $doc, document, SUPER, super_common_i18n: {tab_index_exclusion: '.super-excluded'}});
    const dispatchNative = (type, target) => (native[type] || []).forEach(l => {
        assert(l.capture, type + ' listener must capture so it runs before focus moves');
        l.fn({type, target});
    });
    const focusin = target => (jq.focusin || []).forEach(fn => fn({type: 'focusin', target, relatedTarget: null, preventDefault() {}}));
    return {SUPER, form, button, focussed, dispatchNative, focusin};
}

// Pure keyboard: Tab into the form still focuses the first field.
{
    const t = setup();
    t.SUPER.lastTabKey = 'tab';
    t.focusin(t.form);
    assert.deepEqual(t.focussed, ['first'], 'Tab into form focuses first field');
    console.log('PASS tab into form focuses first field');
}
// Pure keyboard: Shift+Tab into the form still focuses the last field.
{
    const t = setup();
    t.SUPER.lastTabKey = 'shift+tab';
    t.focusin(t.form);
    assert.deepEqual(t.focussed, ['last'], 'Shift+Tab into form focuses last field');
    console.log('PASS shift+tab into form focuses last field');
}
// Tab out of the form, then press the submit button with mouse or touch.
for(const type of ['mousedown', 'touchstart']) {
    const t = setup();
    t.SUPER.lastTabKey = 'tab';
    t.dispatchNative(type, t.button);
    assert.equal(t.SUPER.lastTabKey, undefined, type + ' resets lastTabKey');
    t.focusin(t.form);
    assert.deepEqual(t.focussed, [], type + ' on submit button must not jump to the first field');
    assert(t.form.classList.contains('super-form-focussed'), type + ' still focuses the form');
    console.log('PASS tab then ' + type + ' on submit button does not jump to first field');
}
