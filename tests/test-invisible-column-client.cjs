// A required/validated field inside an invisible column can never show its error to the visitor,
// so browser validation must skip it (like 6.4), while the payload keeps submitting it.
// Executes the shipped common.js validate_form/has_hidden_parent against a minimal DOM.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const pick = rel => fs.existsSync(path.join(root, 'src', rel)) ? path.join(root, 'src', rel) : path.join(root, rel);
const clientSource = pick('assets/js/common.js');

// Minimal element: compound selectors of .class, :not(.class) and [attr="value"], comma lists.
function matchesCompound(el, sel) {
    const re = /:not\(\.([\w-]+)\)|\.([\w-]+)|\[([\w-]+)="([^"]*)"\]/g;
    let m, ok = true;
    while ((m = re.exec(sel)) !== null) {
        if (m[1]) ok = ok && !el.classList.contains(m[1]);
        else if (m[2]) ok = ok && el.classList.contains(m[2]);
        else {
            const v = m[3].startsWith('data-') ? el.dataset[m[3].slice(5).replace(/-([a-z])/g, (_, l) => l.toUpperCase())] : el[m[3]];
            ok = ok && v === m[4];
        }
    }
    return ok;
}
const matches = (el, selector) => selector.split(',').some(s => matchesCompound(el, s.trim()));
class El {
    constructor(classes, props = {}) {
        this.classList = new Set(classes.split(' ').filter(Boolean));
        this.classList.contains = this.classList.has;
        this.classList.add = c => Set.prototype.add.call(this.classList, c);
        this.classList.remove = c => Set.prototype.delete.call(this.classList, c);
        this.dataset = {}; this.style = {}; this.children = []; this.parentElement = null;
        Object.assign(this, props);
    }
    get parentNode() { return this.parentElement; }
    append(...kids) { for (const k of kids) { k.parentElement = this; this.children.push(k); } return this; }
    closest(sel) { for (let n = this; n; n = n.parentElement) if (matches(n, sel)) return n; return null; }
    querySelectorAll(sel) {
        const out = [];
        const walk = n => { for (const c of n.children) { if (matches(c, sel)) out.push(c); walk(c); } };
        walk(this);
        return out;
    }
    querySelector(sel) { return this.querySelectorAll(sel)[0] || null; }
}

function client() {
    const chain = new Proxy({}, {get: (_, key) => key === 'length' ? 0 : () => chain});
    const jq = () => chain;
    jq.extend = Object.assign;
    jq.ajax = () => { throw Error('Unexpected network'); };
    function Element() {}
    function CharacterData() {}
    function DocumentType() {}
    const ctx = {SUPER: {}, jQuery: jq, Element, CharacterData, DocumentType, console, Promise,
        document: {documentElement: {classList: {contains: () => false}}},
        super_common_i18n: {ajaxurl: '/no-network'}, setTimeout: () => 0, clearTimeout: () => {}};
    ctx.window = ctx;
    vm.runInNewContext(fs.readFileSync(clientSource, 'utf8'), ctx);
    return ctx.SUPER;
}

// form > column[.super-invisible?] > shortcode wrapper > .super-field > input (empty, required)
function form(invisible, nested = false) {
    const input = new El('super-shortcode-field', {name: 'amount', value: '', type: 'text'});
    input.dataset.validation = 'empty';
    const wrapper = new El('super-shortcode super-field super-text').append(new El('super-field-wrapper').append(input));
    let column = new El('super-shortcode super-column' + (invisible ? ' super-invisible' : '')).append(wrapper);
    if (nested) column = new El('super-shortcode super-column super-invisible').append(column);
    const f = new El('super-form').append(column);
    return {form: f, input};
}

function validate(S, f) {
    const validated = [];
    let scrolledToError = false;
    Object.assign(S, {
        resetFocussedFields() {}, conditional_logic() {}, before_validating_form_hook() {},
        before_submit_button_click_hook: () => false, after_validating_form_hook() {},
        handle_validations: args => { validated.push(args.el.name); return args.el.value === ''; },
        scrollToError: () => { scrolledToError = true; }
    });
    const blocked = S.validate_form({form: f, submitButton: new El('super-form-button'), validateMultipart: true}) !== true;
    return {blocked, validated, scrolledToError};
}

test('empty validated field in a visible column still blocks validation', () => {
    const r = validate(client(), form(false).form);
    assert.deepEqual(r.validated, ['amount']);
    assert.equal(r.blocked, true);
    assert.equal(r.scrolledToError, true);
});

test('empty validated field in an invisible column no longer blocks validation', () => {
    for (const nested of [false, true]) {
        const r = validate(client(), form(!nested, nested).form);
        assert.deepEqual(r.validated, [], 'nested=' + nested);
        assert.equal(r.blocked, false, 'nested=' + nested);
        assert.equal(r.scrolledToError, false, 'nested=' + nested);
    }
});

test('has_hidden_parent: invisible column counts as hidden only when asked (default yes)', () => {
    const S = client();
    const {input} = form(true);
    assert.equal(S.has_hidden_parent(input), true);
    assert.equal(S.has_hidden_parent(input, false), true);
    assert.equal(S.has_hidden_parent(input, false, true), true);
    assert.equal(S.has_hidden_parent(input, false, false), false, 'payload/conditions keep invisible-column fields');
    assert.equal(S.has_hidden_parent(form(false).input), false);
    // Conditional hiding is unaffected by the new argument.
    const hidden = form(false);
    const col = hidden.input.closest('.super-column');
    col.dataset.conditionalAction = 'show';
    assert.equal(S.has_hidden_parent(hidden.input, false, false), true);
});
