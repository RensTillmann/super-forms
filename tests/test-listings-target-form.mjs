import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../src/includes/extensions/listings/assets/js/frontend/script.js', import.meta.url), 'utf8');

function runResponse({ target = 'super-form-202', error = false, existing = true } = {}) {
  const nodes = [];
  const node = () => {
    const classes = new Set();
    const value = {
      style: {}, dataset: {}, tagName: 'DIV', children: [],
      classList: { add: x => classes.add(x), remove: x => classes.delete(x), contains: x => classes.has(x) },
      appendChild(child) { this.children.push(child); child.parentNode = this; },
      addEventListener() {}, remove() { this.removed = true; },
      querySelector(selector) {
        return selector === '.super-form' && this.innerHTML === 'rendered-form' && target ? { id: target } : null;
      },
    };
    nodes.push(value);
    return value;
  };
  const document = { body: node(), createElement: node, querySelector: () => null };
  const host = node(); host.classList.add('super-listings');
  host.dataset = { formId: '101', listId: '0', entryNonce: 'saved-nonce' };
  const entry = node(); entry.classList.add('super-entry'); entry.dataset.id = '303'; host.appendChild(entry);
  const button = node(); entry.appendChild(button);
  let request, initialized = 0, initializedData;
  const SUPER = {
    ...(existing ? { form_js: { 101: { untouched: 'host' }, 202: { widget: 'preserved' } } } : {}),
    init_super_form_frontend() {
      initialized++;
      const id = parseInt(target.replace('super-form-', ''), 10);
      initializedData = SUPER.form_js?.[id]?._entry_data;
    },
  };
  function XMLHttpRequest() { request = this; }
  XMLHttpRequest.prototype = { open() {}, setRequestHeader() {}, send(params) { this.params = params; } };
  vm.runInNewContext(source, { SUPER, document, XMLHttpRequest,
    window: { innerWidth: 1000, innerHeight: 800, addEventListener() {} },
    jQuery: { param: params => new URLSearchParams(params).toString() },
    super_listings_i18n: { ajaxurl: '/wp-admin/admin-ajax.php' },
  });
  SUPER.frontEndListing.editEntry(button);
  const data = { customer: { value: 'Saved value' }, items: [{ value: 'Repeated row' }] };
  Object.assign(request, { readyState: 4, status: 200, responseText: JSON.stringify({ error, html: 'rendered-form', msg: 'Denied', entry_data: data }) });
  request.onreadystatechange();
  assert.equal(new URLSearchParams(request.params).get('form_id'), '101', 'request authorization stays bound to listing host');
  assert.ok(nodes.find(n => n.classList.contains('super-loading')).removed);
  return { SUPER, initialized, initializedData, data };
}

test('cross-form modal initializes the rendered form with its entry data and preserves both settings', () => {
  const r = runResponse();
  assert.equal(r.initialized, 1);
  assert.deepEqual(JSON.parse(r.initializedData), r.data);
  assert.deepEqual(r.SUPER.form_js[101], { untouched: 'host' });
  assert.equal(r.SUPER.form_js[202].widget, 'preserved');
});
test('same-form modal still populates', () => {
  const r = runResponse({ target: 'super-form-101' });
  assert.deepEqual(JSON.parse(r.initializedData), r.data);
});
test('first modal creates settings for the rendered target', () => {
  const r = runResponse({ existing: false });
  assert.deepEqual(JSON.parse(r.initializedData), r.data);
  assert.equal(r.SUPER.form_js[101], undefined);
});
for (const [name, options] of [['denied response', { error: true }], ['missing rendered form', { target: null }], ['invalid rendered form ID', { target: 'super-form-invalid' }]]) {
  test(name + ' does not initialize or overwrite form settings', () => {
    const r = runResponse(options);
    assert.equal(r.initialized, 0);
    assert.deepEqual(r.SUPER.form_js, { 101: { untouched: 'host' }, 202: { widget: 'preserved' } });
  });
}
