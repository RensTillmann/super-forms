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
}
const submitButton = () => new El(['super-extra-shortcode', 'super-shortcode', 'super-field', 'super-button', 'super-form-button'], {}, [
    new El(['super-button-wrap', 'no_link'], {}, [new El(['super-button-name'], {action: 'submit'})])
]);
const column = (classes, dataset, children) => new El(['super-shortcode', 'super-column'].concat(classes), dataset, children);
const hiddenColumn = child => column(['super-conditional-hidden'], {conditionalAction: 'show'}, [child]);

function run(name, build, expectIndex, disableEnter) {
    const field = new El(['super-shortcode', 'super-field', 'super-text', 'super-focus'], {}, [new El(['super-shortcode-field'])]);
    const buttons = [];
    const add = () => { const b = submitButton(); buttons.push(b); return b; };
    const form = new El(['super-form'], disableEnter ? {disableEnter: 'true'} : {}, [field].concat(build(add)));
    const calls = [];
    const SUPER = {validate_form: args => calls.push(args)};
    const context = {SUPER, document: {querySelector: sel => (field.matches(sel) ? field : form.querySelector(sel))}};
    vm.runInNewContext(hiddenSource, context);
    const handler = vm.runInNewContext(handlerSource, context);
    let prevented = 0;
    handler({keyCode: 13, preventDefault() { prevented++; }});
    assert(prevented >= 1, name + ': default must be prevented');
    if (expectIndex === null) {
        assert.equal(calls.length, 0, name + ': form must not be validated/submitted');
    } else {
        assert.equal(calls.length, 1, name + ': validate_form must be called once');
        assert.equal(calls[0].form, form, name + ': wrong form');
        assert.equal(calls[0].doingSubmit, true, name + ': must be a submit');
        assert.equal(calls[0].submitButton, buttons[expectIndex].querySelector('.super-button-wrap'), name + ': wrong submit button');
    }
    console.log('PASS ' + name);
}

run('visible submit button submits', add => [add()], 0);
run('submit button in conditionally hidden column does not submit', add => [hiddenColumn(add())], null);
run('first hidden, second visible uses the visible button', add => [hiddenColumn(add()), add()], 1);
run('submit button in invisible (not conditionally hidden) column still submits', add => [column(['super-invisible'], {}, [add()])], 0);
run('disable-enter setting does not submit', add => [add()], null, true);
