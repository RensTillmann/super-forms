// Multi-part forms without custom Prev/Next buttons copy the last button into every step as the automatic
// Prev/Next buttons. Those copies must not inherit the button's conditional logic, otherwise a submit button
// that is shown/hidden by conditions would also hide every automatic Prev/Next button.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets/js/common.js'), 'utf8');

const marker = 'SUPER.remove_button_conditional_logic = function';
const start = source.indexOf(marker);
assert(start >= 0, 'common.js defines SUPER.remove_button_conditional_logic');
const helper = source.slice(start, source.indexOf('\n    };', start) + 7);

function element(classes, attrs, children) {
    const el = {
        classes: new Set(classes), attrs: Object.assign({}, attrs), children: children || [], removed: false,
        classList: {
            remove(...names) { names.forEach(n => el.classes.delete(n)); },
            contains(n) { return el.classes.has(n); }
        },
        removeAttribute(n) { delete el.attrs[n]; },
        remove() { el.removed = true; },
        querySelectorAll(selector) {
            assert.equal(selector, '.super-conditional-logic');
            const found = [];
            (function walk(nodes) { nodes.forEach(n => { if(n.classes.has('super-conditional-logic')) found.push(n); walk(n.children); }); })(el.children);
            return found;
        }
    };
    return el;
}

const SUPER = {};
vm.runInNewContext(helper, {SUPER});
assert.equal(typeof SUPER.remove_button_conditional_logic, 'function');

for (const action of ['show', 'hide', 'readonly']) {
    const textarea = element(['super-conditional-logic'], {'data-fields': '{email}'});
    const wrap = element(['super-button-wrap', 'no_link'], {}, [element(['super-button-name'], {'data-action': 'submit'})]);
    const stateClass = action === 'show' ? 'super-conditional-hidden' : 'super-conditional-visible';
    const classes = ['super-extra-shortcode', 'super-shortcode', 'super-field', 'super-button', 'super-form-button', 'my-wrap', stateClass];
    if (action === 'readonly') classes.push('super-readonly');
    const button = element(classes, {'data-color': '#f26c68', 'data-conditional-action': action, 'data-conditional-trigger': 'all'}, [wrap, textarea]);
    assert.equal(SUPER.remove_button_conditional_logic(button), button);
    assert.deepEqual([...button.classes], ['super-extra-shortcode', 'super-shortcode', 'super-field', 'super-button', 'super-form-button', 'my-wrap'], action + ': conditional classes removed, others kept');
    assert.deepEqual(button.attrs, {'data-color': '#f26c68'}, action + ': conditional data attributes removed, others kept');
    assert.equal(textarea.removed, true, action + ': conditions textarea removed');
    assert.equal(wrap.removed, false, action + ': button markup kept');
    console.log('PASS copy of a button with "' + action + '" conditions loses its conditional logic');
}

// The automatic Prev/Next copies are made from a stripped clone; the button kept in the last step keeps its conditions.
const branchStart = source.indexOf('if(!hasPrevNextBtns){');
assert(branchStart >= 0);
const branch = source.slice(branchStart, source.indexOf('// Now lets loop through all the multi-parts', branchStart));
const lastStep = branch.indexOf(".appendTo($form.find('.super-multipart:last'))");
const strip = branch.indexOf('SUPER.remove_button_conditional_logic(');
const html = branch.indexOf('$button_clone = ');
const nextCopy = branch.indexOf('.addClass(\'super-next-multipart\')');
const prevCopy = branch.indexOf('.addClass(\'super-prev-multipart\')');
assert(lastStep >= 0 && strip > lastStep, 'the submit button kept in the last step is appended before stripping');
assert(strip >= 0 && nextCopy > strip && prevCopy > strip, 'Prev/Next copies are made after stripping');
assert(/\$button_clone = SUPER\.remove_button_conditional_logic\(\$submit_button\.clone\(\)\[0\]\)\.outerHTML;/.test(branch), 'copies are built from a stripped clone of the button');
assert(html >= 0);
console.log('PASS automatic Prev/Next buttons are built from the stripped copy only');
