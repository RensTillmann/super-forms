// Enter key must not submit a form through a submit button hidden by conditional logic.
//
// Extraction: the Enter handling lives inside the anonymous `$doc.keydown(function(e){...})`
// listener in elements.js. The test slices the real Enter branch from the `// 13 = enter`
// comment up to the `// 37 = left arrow` comment (asserting the slice holds the submit
// button selector), wraps it in `function(e){ var <the listener's locals>; ... }` and runs it
// in node:vm. The real `SUPER.has_hidden_parent` is sliced from common.js (from its
// assignment up to the closing `    };`). Only `SUPER.validate_form` and the DOM are faked.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const pick = file => path.join(root, fs.existsSync(path.join(root, 'src', file)) ? 'src/' + file : file);
const elementsSource = fs.readFileSync(pick('assets/js/frontend/elements.js'), 'utf8');
const commonSource = fs.readFileSync(pick('assets/js/common.js'), 'utf8');

const enterStart = elementsSource.indexOf('// 13 = enter');
const enterEnd = elementsSource.indexOf('// 37 = left arrow', enterStart);
assert(enterStart >= 0 && enterEnd > enterStart, 'Enter branch not found in elements.js');
const enterBranch = elementsSource.slice(enterStart, enterEnd);
assert(enterBranch.includes('.super-form-button .super-button-wrap .super-button-name[data-action="submit"]'), 'Enter branch does not pick a submit button');
const handlerSource = '(function(e){\nvar i, nodes, field, form, item, current, submitButton, nextSibling, keyCode = e.keyCode || e.which;\n' + enterBranch + '\n})';

const hiddenStart = commonSource.indexOf('SUPER.has_hidden_parent = function');
const hiddenEnd = commonSource.indexOf('\n    };', hiddenStart);
assert(hiddenStart >= 0 && hiddenEnd > hiddenStart, 'SUPER.has_hidden_parent not found in common.js');
const hiddenSource = commonSource.slice(hiddenStart, hiddenEnd + '\n    };'.length);

// Minimal fake DOM: classes, data-* attributes, inline style and descendant selectors.
function parseCompound(text) {
    const classes = [], attrs = [];
    const re = /\.([\w-]+)|\[([\w-]+)="([^"]*)"\]/g;
    let m;
    while ((m = re.exec(text))) {
        if (m[1]) classes.push(m[1]); else attrs.push([m[2], m[3]]);
    }
    return {classes, attrs};
}
const toDatasetKey = name => name.replace(/^data-/, '').replace(/-([a-z])/g, (_, c) => c.toUpperCase());
class El {
    constructor(classes, dataset = {}, children = []) {
        this.tagName = 'DIV';
        this.classes = new Set(classes.split(/\s+/).filter(Boolean));
        this.classList = {contains: name => this.classes.has(name)};
        this.dataset = dataset;
        this.style = {};
        this.parentNode = null;
        this.parentElement = null;
        this.children = [];
        children.forEach(child => this.append(child));
    }
    append(child) { child.parentNode = child.parentElement = this; this.children.push(child); return child; }
    matchesCompound(c) {
        return c.classes.every(name => this.classes.has(name)) && c.attrs.every(([name, value]) => this.dataset[toDatasetKey(name)] === value);
    }
    matches(selector) {
        const parts = selector.trim().split(/\s+/).map(parseCompound);
        if (!this.matchesCompound(parts[parts.length - 1])) return false;
        let node = this.parentElement;
        for (let i = parts.length - 2; i >= 0; i--) {
            while (node && !node.matchesCompound(parts[i])) node = node.parentElement;
            if (!node) return false;
            node = node.parentElement;
        }
        return true;
    }
    closest(selector) {
        for (let node = this; node; node = node.parentElement) if (node.matches(selector)) return node;
        return null;
    }
    querySelectorAll(selector) {
        const found = [];
        const walk = node => node.children.forEach(child => { if (child.matches(selector)) found.push(child); walk(child); });
        walk(this);
        return found;
    }
    querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    click() { clicks.push(this); }
}
let clicks = [];
function submitButton(wrapperClasses = '') {
    const name = new El('super-button-name', {action: 'submit'});
    const wrap = new El('super-button-wrap', {}, [name]);
    new El('super-extra-shortcode super-shortcode super-field super-button super-form-button ' + wrapperClasses, {}, [wrap]);
    return name;
}
function column(classes, dataset, button) {
    return new El('super-shortcode super-column ' + classes, dataset, [button.parentElement.parentElement]);
}
const hiddenColumn = button => column('super-conditional-hidden', {conditionalAction: 'show'}, button);
const visibleColumn = button => column('super-conditional-visible', {conditionalAction: 'show'}, button);
const invisibleColumn = button => column('super-invisible', {}, button);
// Next button of a multi-part step (auto-generated clone of the submit button, or a custom button
// with action "next"): `.super-next-multipart` instead of `.super-form-button`. Returns the wrapper.
function nextButton() {
    return new El('super-extra-shortcode super-shortcode super-field super-button super-next-multipart', {}, [
        new El('super-button-wrap', {}, [new El('super-button-name', {action: 'submit'})])
    ]);
}
const multipart = (active, children) => new El('super-shortcode super-multipart' + (active ? ' super-active' : ''), {}, children);

// `layout(field)` (optional) returns the form's children and places the focused field itself.
function pressEnter({buttonWrappers, disableEnter, layout}) {
    const field = new El('super-shortcode super-field super-text super-focus', {}, [new El('super-shortcode-field')]);
    const form = new El('super-form', disableEnter ? {disableEnter: 'true'} : {}, layout ? layout(field) : [field, ...buttonWrappers]);
    const body = new El('body', {}, [form]);
    const calls = [];
    clicks = [];
    const SUPER = {validate_form: args => calls.push(args)};
    const context = vm.createContext({SUPER, document: body});
    vm.runInContext(hiddenSource, context);
    const handler = vm.runInContext(handlerSource, context);
    let prevented = 0;
    const result = handler({keyCode: 13, preventDefault: () => { prevented++; }});
    return {calls, prevented, result, clicks};
}

const cases = [];
{
    const button = submitButton();
    cases.push({name: 'visible submit button submits', run: () => pressEnter({buttonWrappers: [visibleColumn(button)]}), expect: button});
}
{
    const button = submitButton();
    cases.push({name: 'only submit button inside conditionally hidden column does not submit', run: () => pressEnter({buttonWrappers: [hiddenColumn(button)]}), expect: null});
}
{
    const hidden = submitButton(), visible = submitButton();
    cases.push({name: 'first submit button hidden, second visible uses the visible one', run: () => pressEnter({buttonWrappers: [hiddenColumn(hidden), visibleColumn(visible)]}), expect: visible});
}
{
    const button = submitButton();
    cases.push({name: 'submit button inside invisible (not conditionally hidden) column still submits', run: () => pressEnter({buttonWrappers: [invisibleColumn(button)]}), expect: button});
}
{
    const button = submitButton();
    cases.push({name: 'disable-enter setting does not submit', run: () => pressEnter({buttonWrappers: [visibleColumn(button)], disableEnter: true}), expect: null});
}
{
    const button = submitButton();
    const loading = button.parentElement.parentElement;
    loading.classes.add('super-loading');
    cases.push({name: 'submit already in progress does not submit again', run: () => pressEnter({buttonWrappers: [visibleColumn(button)]}), expect: null});
}
// Multi-part forms: on a step that is not the last one, Enter takes the exact path of that step's
// Next button (a click, handled by the `.super-next-multipart` listener -> SUPER.switchMultipart,
// which runs the step validation and skip logic). Only the last step submits.
{
    const next = nextButton(), button = submitButton();
    cases.push({name: 'multi-part: Enter on step 1 of 2 clicks Next, no submit', run: () => pressEnter({layout: field => [
        multipart(true, [field, next]),
        multipart(false, [button.parentElement.parentElement])
    ]}), expectNext: next});
}
{
    const first = nextButton(), second = nextButton(), button = submitButton();
    cases.push({name: 'multi-part: Enter on step 2 of 3 clicks that step\'s Next', run: () => pressEnter({layout: field => [
        multipart(false, [first]),
        multipart(true, [field, second]),
        multipart(false, [button.parentElement.parentElement])
    ]}), expectNext: second});
}
{
    const next = nextButton(), hidden = submitButton(), visible = submitButton();
    cases.push({name: 'multi-part: Enter on the last step submits via the visible button', run: () => pressEnter({layout: field => [
        multipart(false, [next]),
        multipart(true, [field, hiddenColumn(hidden), visibleColumn(visible)])
    ]}), expect: visible});
}
{
    const next = nextButton(), button = submitButton();
    cases.push({name: 'multi-part: Next button of the active step hidden by conditions does nothing', run: () => pressEnter({layout: field => [
        multipart(true, [field, new El('super-shortcode super-column super-conditional-hidden', {conditionalAction: 'show'}, [next])]),
        multipart(false, [button.parentElement.parentElement])
    ]}), expect: null});
}
{
    const button = submitButton();
    cases.push({name: 'multi-part: active step without a Next button does nothing (never skips steps)', run: () => pressEnter({layout: field => [
        multipart(true, [field]),
        multipart(false, [button.parentElement.parentElement])
    ]}), expect: null});
}
{
    const next = nextButton(), button = submitButton();
    cases.push({name: 'multi-part: disable-enter setting still wins', run: () => pressEnter({disableEnter: true, layout: field => [
        multipart(true, [field, next]),
        multipart(false, [button.parentElement.parentElement])
    ]}), expect: null});
}

let failed = 0;
for (const scenario of cases) {
    try {
        const {calls, prevented, clicks} = scenario.run();
        assert.equal(prevented, 1, scenario.name + ': default must be prevented');
        if (scenario.expectNext) {
            assert.equal(calls.length, 0, scenario.name + ': validate_form must not be called');
            assert.equal(clicks.length, 1, scenario.name + ': the Next button must be clicked once');
            assert.equal(clicks[0], scenario.expectNext, scenario.name + ': wrong Next button clicked');
        } else if (scenario.expect) {
            assert.equal(clicks.length, 0, scenario.name + ': nothing must be clicked');
            assert.equal(calls.length, 1, scenario.name + ': validate_form calls');
            assert.equal(calls[0].submitButton, scenario.expect.parentElement, scenario.name + ': wrong submit button');
            assert.equal(calls[0].doingSubmit, true, scenario.name + ': doingSubmit');
        } else {
            assert.equal(calls.length, 0, scenario.name + ': validate_form must not be called');
            assert.equal(clicks.length, 0, scenario.name + ': nothing must be clicked');
        }
        console.log('PASS ' + scenario.name);
    } catch (error) {
        failed++;
        console.log('FAIL ' + scenario.name + ': ' + error.message);
    }
}
if (failed) {
    console.log(failed + ' of ' + cases.length + ' Enter-key cases failed');
    process.exit(1);
}
