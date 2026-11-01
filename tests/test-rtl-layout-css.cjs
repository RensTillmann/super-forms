// CSS contracts only: browser geometry, text and responsive behaviour need UI tests.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const cssPath = process.env.SF_CSS_SOURCE || path.join(__dirname,
    fs.existsSync(path.join(__dirname, '../src'))
        ? '../src/assets/css/frontend/elements.css' : '../assets/css/frontend/elements.css');
const css = fs.readFileSync(cssPath, 'utf8').replace(/\/\*[\s\S]*?\*\//g, '');
function selectors(text) {
    let depth = 0, start = 0;
    const result = [];
    for (let i = 0; i < text.length; i++) {
        if (text[i] === '(') depth++;
        if (text[i] === ')') depth--;
        if (text[i] === ',' && depth === 0) {
            result.push(text.slice(start, i).trim());
            start = i + 1;
        }
    }
    result.push(text.slice(start).trim());
    return result;
}
const rules = [...css.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map(([, selector, body]) => ({
    selectors: selectors(selector),
    declarations: Object.fromEntries(body.split(';').filter(x => x.includes(':')).map(x => {
        const colon = x.indexOf(':');
        return [x.slice(0, colon).trim(), x.slice(colon + 1).trim()];
    }))
}));
const prefix = '.super-form.super-rtl ';
function last(selector, property) {
    return rules.filter(r => r.selectors.includes(selector) && property in r.declarations)
        .map(r => r.declarations[property]).pop();
}
function expect(selector, property, value) {
    assert.equal(last(prefix + selector, property), value, selector + ' ' + property);
}
const contexts = ['.super-tabs-menu', '.super-accordion-header', '.super-checkbox', '.super-radio',
    '.super-multipart-steps', '.super-multipart-progress-inner', '.super-file',
    '.super-heading-title', '.super-heading-description'];

test('only terminal RTL layout contexts own their inline direction', () => {
    const local = rules.find(r => contexts.every(s => r.selectors.includes(prefix + s)) &&
        r.declarations.direction === 'rtl');
    assert.ok(local, 'page-inherited direction must be overridden at the affected contexts');
    assert.deepEqual(local.selectors, contexts.map(s => prefix + s));
    assert.equal(last('.super-form.super-rtl', 'direction'), undefined);
    assert.equal(last('.super-form.super-rtl *', 'direction'), undefined);
});

test('tabs, accordion and multipart rows use the owned RTL axis exactly once', () => {
    for (const s of contexts.slice(0, 2).concat(contexts.slice(4, 6))) {
        expect(s, 'direction', 'rtl');
        expect(s, 'flex-direction', 'row');
    }
});

test('checkbox and radio list modes retain RTL rows and wrapper alignment', () => {
    for (const type of ['.super-checkbox', '.super-radio']) {
        expect(type + ' .super-item', 'flex-direction', 'row');
        expect(type + '.display-horizontal .super-items-list', 'flex-direction', 'row');
        expect(type + '.display-horizontal .super-field-wrapper', 'justify-content', 'flex-start');
        expect(type + '.display-grid .super-items-list', 'flex-direction', 'row');
        expect(type + '.display-grid .super-items-list', 'flex-grow', '1');
        expect('.super-field:not(.super-icon-outside, .super-icon-inside)' + type +
            ' .super-field-wrapper', 'justify-content', 'flex-start');
    }
});

test('upload icon order and physical heading alignments use the same local RTL context', () => {
    expect('.super-file .super-field-wrapper', 'align-items', 'flex-start');
    for (const child of ['.super-fileupload-button', '.super-fileupload-files']) {
        expect('.super-file .super-field-wrapper ' + child, 'flex-direction', 'row');
    }
    for (const type of ['.super-heading-title', '.super-heading-description']) {
        expect(type, 'justify-content', 'flex-start');
        expect(type + '.super-align-left', 'justify-content', 'flex-end');
        expect(type + '.super-align-center', 'justify-content', 'center');
        expect(type + '.super-align-right', 'justify-content', 'flex-start');
    }
});

test('HTML-only overrides cannot countermand the local axes on RTL pages', () => {
    for (const rule of rules) {
        if (!Object.keys(rule.declarations).some(p =>
            ['direction', 'flex-direction', 'justify-content', 'align-items'].includes(p))) continue;
        for (const selector of rule.selectors.filter(s => s.startsWith('html[dir=rtl]'))) {
            const outsideContexts = selector.includes(':not(.super-checkbox):not(.super-radio)');
            assert.ok(outsideContexts || !contexts.some(s => selector.includes(s)), selector);
        }
    }
});

test('phone flags and quantity controls are not normalised with the affected rows', () => {
    for (const s of ['.super-int-phone_flag-container .super-int-phone_selected-flag',
        '.super-int-phone_country-list .super-int-phone_country']) {
        assert.equal(last('html[dir=rtl] ' + prefix + s, 'flex-direction'), 'row');
        expect(s, 'direction', undefined);
    }
    for (const [button, radius] of [['minus', '0px 4px 4px 0px'], ['plus', '4px 0px 0px 4px']]) {
        const selector = '.super-form.super-rtl.super-default-rounded .super-quantity .super-' + button + '-button';
        assert.equal(last(selector, 'border-radius'), radius);
        assert.equal(last('html[dir=rtl] ' + selector, 'border-radius'), radius);
    }
    expect('.super-quantity', 'direction', undefined);
});
