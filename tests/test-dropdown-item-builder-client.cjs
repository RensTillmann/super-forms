// The front-end dropdown item click handler also matches the builder's translation
// dropdowns, which are not inside a .super-field-wrapper. Clicking such an item must
// not throw; a regular front-end dropdown keeps working as before.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const file = fs.existsSync(path.join(root, 'src/assets/js/frontend/elements.js')) ? 'src/assets/js/frontend/elements.js' : 'assets/js/frontend/elements.js';
const source = fs.readFileSync(path.join(root, file), 'utf8').replace(/\r\n/g, '\n');
const selector = '.super-dropdown .super-dropdown-list .super-item:not(.super-placeholder)';
const start = source.indexOf("        $doc.on('click', '" + selector + "'");
assert(start >= 0, 'dropdown item click handler not found');
const end = source.indexOf('\n        });\n', start);
assert(end > start);
const block = source.slice(start, end + '\n        });\n'.length);

function classList(initial) {
    const set = new Set(initial || []);
    return { add: c => set.add(c), remove: c => set.delete(c), contains: c => set.has(c), set };
}
function loadHandler() {
    let handler = null;
    vm.runInNewContext(block, {
        $doc: { on: (event, sel, fn) => { if (event === 'click' && sel === selector) handler = fn; } },
        SUPER: { focusForm() {}, focusField() {}, get_frontend_or_backend_form: () => null, handle_validations() {}, after_field_change_blur_hook() {} }
    });
    assert.equal(typeof handler, 'function');
    return handler;
}

// Builder translation dropdown: <div class="super-dropdown"><ul class="super-dropdown-list"><li class="super-item">
{
    const handler = loadHandler();
    const list = { querySelector: () => null, querySelectorAll: () => [], classList: classList() };
    const item = {
        dataset: { value: 'nl_NL' }, innerHTML: 'Nederlands', classList: classList(),
        closest: sel => (sel === '.super-dropdown-list' ? list : null)
    };
    assert.doesNotThrow(() => handler.call(item, { stopPropagation() {} }), 'builder translation dropdown item click threw');
    console.log('PASS builder translation dropdown item click does not throw');
}

// Regular single-value front-end dropdown keeps selecting the clicked item.
{
    const handler = loadHandler();
    const input = { value: '', dataset: {} };
    const wrapper = { querySelector: sel => (sel === '.super-shortcode-field' ? input : null) };
    const placeholder = { innerHTML: '', dataset: {}, classList: classList() };
    const other = { classList: classList(['super-active']) };
    const field = { classList: classList(['super-open']), closest: () => null };
    let item;
    const list = {
        classList: classList(),
        querySelector: sel => (sel === '.super-placeholder' ? placeholder : null),
        querySelectorAll: () => [other, item]
    };
    item = {
        dataset: { value: 'b' }, innerHTML: 'Option B', classList: classList(),
        closest: sel => ({ '.super-field': field, '.super-field-wrapper': wrapper, '.super-dropdown-list': list })[sel] || null
    };
    let stopped = false;
    handler.call(item, { stopPropagation() { stopped = true; } });
    assert.equal(stopped, true);
    assert.equal(input.value, 'b');
    assert.equal(placeholder.innerHTML, 'Option B');
    assert.equal(placeholder.dataset.value, 'b');
    assert.equal(placeholder.classList.contains('super-active'), true);
    assert.equal(other.classList.contains('super-active'), false);
    assert.equal(item.classList.contains('super-active'), true);
    assert.equal(field.classList.contains('super-open'), false);
    console.log('PASS front-end dropdown item click still selects the item');
}
