const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const pick = file => path.join(root, fs.existsSync(path.join(root, 'src', file)) ? path.join('src', file) : file);
const elements = fs.readFileSync(pick('assets/js/frontend/elements.js'), 'utf8');
const common = fs.readFileSync(pick('assets/js/common.js'), 'utf8');

// The Enter-key logic lives inside the anonymous `$doc.keydown(function(e){...})` listener.
// Extract the real `if (keyCode == 13) {...}` block between its source comments
// (`// 13 = enter` up to `// 37 = left arrow`) and run it inside a function that declares the
// same local variables as the listener.
const listener = elements.indexOf('$doc.keydown(function(e){');
assert(listener >= 0, 'keydown listener not found');
const enterStart = elements.indexOf('// 13 = enter', listener);
const enterEnd = elements.indexOf('// 37 = left arrow', enterStart);
assert(enterStart > listener && enterEnd > enterStart, 'Enter block not found');
const enterBlock = elements.slice(enterStart, enterEnd);
assert(enterBlock.includes('.super-button-name[data-action="submit"]'), 'Enter block does not pick the submit button');
const handlerSource = '(function(e){var i, nodes, field, form, item, current, submitButton, nextSibling, keyCode = e.keyCode || e.which;\n' + enterBlock + '\nreturn undefined;})';

// Real SUPER.has_hidden_parent from common.js.
const hiddenStart = common.indexOf('SUPER.has_hidden_parent = function');
assert(hiddenStart >= 0, 'has_hidden_parent not found');
const hiddenEnd = common.indexOf('\n    };', hiddenStart);
assert(hiddenEnd > hiddenStart);
const hiddenSource = common.slice(hiddenStart, hiddenEnd + '\n    };'.length);

// Minimal fake DOM: class/attribute compound selectors with descendant combinators.
function parseCompound(compound) {
    const classes = [], attrs = [];
    const re = /\.([\w-]+)|\[([\w-]+)="([^"]*)"\]/g; let m;
    while ((m = re.exec(compound))) { if (m[1]) classes.push(m[1]); else attrs.push([m[2], m[3]]); }
    return {classes, attrs};
}
const toDataKey = attr => attr.replace(/^data-/, '').replace(/-(\w)/g, (_, c) => c.toUpperCase());
class El {
    constructor(classes, dataset, children) {
        this.classList = {set: new Set(classes), contains(c) { return this.set.has(c); }, add(c) { this.set.add(c); }, remove(c) { this.set.delete(c); }};
        this.dataset = Object.assign({}, dataset);
        this.style = {};
        this.parentElement = null;
        this.children = children || [];
        for (const child of this.children) child.parentElement = this;
    }
    get parentNode() { return this.parentElement; }
    matchesCompound(compound) {
        const {classes, attrs} = parseCompound(compound);
        return classes.every(c => this.classList.contains(c)) && attrs.every(([k, v]) => this.dataset[toDataKey(k)] === v);
    }
    matches(selector) {
        const parts = selector.trim().split(/\s+/);
        if (!this.matchesCompound(parts[parts.length - 1])) return false;
        let idx = parts.length - 2, node = this.parentElement;
        while (idx >= 0 && node) { if (node.matchesCompound(parts[idx])) idx--; node = node.parentElement; }
        return idx < 0;
    }
    closest(selector) { for (let n = this; n; n = n.parentElement) if (n.matches(selector)) return n; return null; }
    querySelectorAll(selector) {
        const out = [];
        const walk = n => { for (const c of n.children) { if (c.matches(selector)) out.push(c); walk(c); } };
        walk(this);
        return out;
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    click() { clicks.push(this); }
}
let clicks = [];
const submitButton = () => new El(['super-extra-shortcode', 'super-shortcode', 'super-field', 'super-button', 'super-form-button'], {}, [
    new El(['super-button-wrap', 'no_link'], {}, [new El(['super-button-name'], {action: 'submit'})])
]);
const column = (classes, dataset, children) => new El(['super-shortcode', 'super-column'].concat(classes), dataset, children);
const hiddenColumn = child => column(['super-conditional-hidden'], {conditionalAction: 'show'}, [child]);
// Next button as rendered for a multi-part step (auto-generated clone of the submit button, or a
// custom button with action "next"): `.super-next-multipart` instead of `.super-form-button`.
const nextButton = () => new El(['super-extra-shortcode', 'super-shortcode', 'super-field', 'super-button', 'super-next-multipart'], {}, [
    new El(['super-button-wrap', 'no_link'], {}, [new El(['super-button-name'], {action: 'submit'})])
]);
const multipart = (active, children) => new El(['super-shortcode', 'super-multipart'].concat(active ? ['super-active'] : []), {}, children);

// expect: index of the submit button that must be used, null for "nothing happens",
// or {next: index} for "the Next button with this index is clicked".
function run(name, build, expect, disableEnter) {
    const field = new El(['super-shortcode', 'super-field', 'super-text', 'super-focus'], {}, [new El(['super-shortcode-field'])]);
    const buttons = [], nextButtons = [];
    const add = () => { const b = submitButton(); buttons.push(b); return b; };
    const addNext = () => { const b = nextButton(); nextButtons.push(b); return b; };
    let children = build(add, field, addNext);
    if (!field.parentElement) children = [field].concat(children);
    const form = new El(['super-form'], disableEnter ? {disableEnter: 'true'} : {}, children);
    const calls = [];
    clicks = [];
    const SUPER = {validate_form: args => calls.push(args)};
    const context = {SUPER, document: {querySelector: sel => (field.matches(sel) ? field : form.querySelector(sel))}};
    vm.runInNewContext(hiddenSource, context);
    const handler = vm.runInNewContext(handlerSource, context);
    let prevented = 0;
    handler({keyCode: 13, preventDefault() { prevented++; }});
    assert(prevented >= 1, name + ': default must be prevented');
    if (expect !== null && typeof expect === 'object') {
        assert.equal(calls.length, 0, name + ': form must not be validated/submitted');
        assert.equal(clicks.length, 1, name + ': the Next button must be clicked once');
        assert.equal(clicks[0], nextButtons[expect.next], name + ': wrong Next button clicked');
    } else if (expect === null) {
        assert.equal(calls.length, 0, name + ': form must not be validated/submitted');
        assert.equal(clicks.length, 0, name + ': nothing must be clicked');
    } else {
        assert.equal(clicks.length, 0, name + ': nothing must be clicked');
        assert.equal(calls.length, 1, name + ': validate_form must be called once');
        assert.equal(calls[0].form, form, name + ': wrong form');
        assert.equal(calls[0].doingSubmit, true, name + ': must be a submit');
        assert.equal(calls[0].submitButton, buttons[expect].querySelector('.super-button-wrap'), name + ': wrong submit button');
    }
    console.log('PASS ' + name);
}

run('visible submit button submits', add => [add()], 0);
run('submit button in conditionally hidden column does not submit', add => [hiddenColumn(add())], null);
run('first hidden, second visible uses the visible button', add => [hiddenColumn(add()), add()], 1);
run('submit button in invisible (not conditionally hidden) column still submits', add => [column(['super-invisible'], {}, [add()])], 0);
run('disable-enter setting does not submit', add => [add()], null, true);
// A submit button made read-only by conditional logic (column or the button itself) is not usable by Enter either.
run('read-only submit button does not submit', add => [column(['super-readonly'], {conditionalAction: 'readonly'}, [add()])], null);
run('read-only first, visible second uses the visible button', add => [column(['super-readonly'], {conditionalAction: 'readonly'}, [add()]), add()], 1);

// Multi-part forms: on a step that is not the last one, Enter takes the exact path of that step's
// Next button (a click, handled by the `.super-next-multipart` listener -> SUPER.switchMultipart,
// which runs the step validation and skip logic). Only the last step submits.
run('multi-part: Enter on step 1 of 2 clicks Next, no submit', (add, field, addNext) => [
    multipart(true, [field, addNext()]),
    multipart(false, [add()])
], {next: 0});
run('multi-part: Enter on step 2 of 3 clicks that step\'s Next', (add, field, addNext) => [
    multipart(false, [addNext()]),
    multipart(true, [field, addNext()]),
    multipart(false, [add()])
], {next: 1});
run('multi-part: Enter on the last step submits via the visible button', (add, field, addNext) => [
    multipart(false, [addNext()]),
    multipart(true, [field, hiddenColumn(add()), add()])
], 1);
run('multi-part: Next button of the active step hidden by conditions does nothing', (add, field, addNext) => [
    multipart(true, [field, hiddenColumn(addNext())]),
    multipart(false, [add()])
], null);
run('multi-part: active step without a Next button does nothing (never skips steps)', (add, field) => [
    multipart(true, [field]),
    multipart(false, [add()])
], null);
run('multi-part: disable-enter setting still wins', (add, field, addNext) => [
    multipart(true, [field, addNext()]),
    multipart(false, [add()])
], null, true);
