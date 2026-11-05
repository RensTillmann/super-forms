// Autocomplete (New) only accepts primary types (max five) or a single type collection.
// The plugin setting still offers the legacy values, so the client maps them before the request.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const clientSource = process.env.SF_CLIENT_SOURCE || (fs.existsSync(path.join(__dirname, '../src/assets/js/common.js')) ? path.join(__dirname, '../src/assets/js/common.js') : path.join(__dirname, '../assets/js/common.js'));

function fixture() {
    const chain = new Proxy({}, {get: (_, key) => key === 'length' ? 0 : () => chain});
    const jq = () => chain;
    jq.extend = Object.assign;
    jq.ajax = () => {throw Error('Unexpected network');};
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

const mapTypes = fixture().google_maps_api.primary_types;

test('the default legacy value "address" becomes a valid primary type', () => {
    assert.deepEqual([...mapTypes('address')], ['street_address']);
    assert.deepEqual([...mapTypes(' address ')], ['street_address']);
});

test('primary types that exist in the new API are kept', () => {
    assert.deepEqual([...mapTypes('geocode')], ['geocode']);
    assert.deepEqual([...mapTypes('establishment')], ['establishment']);
    assert.deepEqual([...mapTypes('address,geocode')], ['street_address', 'geocode']);
});

test('type collections are sent alone', () => {
    assert.deepEqual([...mapTypes('(regions)')], ['(regions)']);
    assert.deepEqual([...mapTypes('(cities)')], ['(cities)']);
    assert.deepEqual([...mapTypes('address,(regions)')], ['(regions)']);
});

test('values the new API does not accept are dropped instead of sent', () => {
    assert.equal(mapTypes(''), undefined);
    assert.equal(mapTypes('   '), undefined);
    assert.equal(mapTypes('locality,foo'), undefined);
    assert.deepEqual([...mapTypes('foo,establishment')], ['establishment']);
});

test('at most five primary types are sent and repeats collapse', () => {
    const types = [...mapTypes('address,geocode,establishment,address,geocode,establishment')];
    assert.ok(types.length <= 5, `sent ${types.length} types`);
    assert.deepEqual(types, ['street_address', 'geocode', 'establishment']);
});
