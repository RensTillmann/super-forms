// Connected datepickers: the calendar opens when the two connected fields use different date
// formats, and a mutual connection no longer recurses without end.
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const elements = fs.existsSync(path.join(root, 'src/assets/js/frontend/elements.js'))
    ? path.join(root, 'src/assets/js/frontend/elements.js')
    : path.join(root, 'assets/js/frontend/elements.js');
const source = fs.readFileSync(elements, 'utf8');
const dateFormat = fs.readFileSync(path.join(path.dirname(elements), 'date-format.js'), 'utf8');

function slice(from, to) {
    const start = source.indexOf(from);
    assert.ok(start >= 0, 'not found: ' + from);
    const end = source.indexOf(to, start);
    assert.ok(end > start, 'not closed: ' + from);
    return source.slice(start, end + to.length);
}

// A context with the plugin's own date.js (Date.parseExact / .add / toString(format)).
function context(globals) {
    const ctx = vm.createContext(Object.assign({console}, globals));
    vm.runInContext(dateFormat, ctx);
    return ctx;
}

function camel(name) {
    return name.replace(/-([a-z])/g, (_, c) => c.toUpperCase());
}

function field(name, dataset, value) {
    return {
        name,
        value: value || '',
        dataset,
        options: {},
        widget: {classList: {add() {}, remove() {}}}
    };
}

function makeDollar(fields, applied) {
    function wrap(el) {
        return {
            0: el,
            length: 1,
            attr: name => el[name],
            val: () => el.value,
            // jQuery's data() turns a numeric data attribute into a number
            data: key => {
                const value = el.dataset[camel(key)];
                return typeof value === 'string' && value !== '' && String(Number(value)) === value ? Number(value) : value;
            },
            datepicker(command, option, value) {
                if (command === 'widget') return [el.widget || {classList: {add() {}, remove() {}}}];
                applied.push({field: el.name, option, value});
                el.options[option] = value;
                return this;
            }
        };
    }
    return function $(arg) {
        if (typeof arg === 'string') {
            const match = /data-connected-(min|max)="([^"]+)"/.exec(arg);
            assert.ok(match, 'unexpected selector: ' + arg);
            const key = match[1] === 'min' ? 'connectedMin' : 'connectedMax';
            const hits = fields.filter(el => el.dataset[key] === match[2]);
            return {length: hits.length, each(fn) {hits.forEach(el => fn.call(el));}};
        }
        return wrap(arg);
    };
}

function opener(fields, applied) {
    const block = slice('                beforeShow: function(input, inst) {', '\n                },');
    const code = '(function(){\n'
        + 'var widget, minDate, maxDate, connectedMinDays, connectedMaxDays,'
        + ' multiDatesClassName = "multi", singleDatesClassName = "single",'
        // the loop-scope state of the last initialised picker, the state beforeShow used to read
        + ' el = {name: "last-initialised-picker"}, jsformat = "dd-MM-yyyy", parseFormat = ["dd-MM-yyyy"];\n'
        + 'return {' + block + '\n};\n})()';
    return vm.runInContext(code, context({$: makeDollar(fields, applied)}));
}

function show(input, datepickerOptions) {
    datepickerOptions.beforeShow.call(input, input, {settings: {maxPicks: 1}});
}

test('a departure calendar opens when arrival and departure use different date formats', () => {
    const arrival = field('arrival', {jsformat: 'dd.MM.yyyy', format: 'dd.mm.yy'}, '03.11.2026');
    const departure = field('departure', {
        jsformat: 'dd-MM-yyyy', format: 'dd-mm-yy', connectedMin: '', connectedMax: ''
    });
    // Arrival connects to departure: departure starts 1 day after arrival.
    arrival.dataset.connectedMin = 'departure';
    arrival.dataset.connectedMinDays = '1';
    const applied = [];
    show(departure, opener([arrival, departure], applied));
    assert.deepEqual(applied, [{field: 'departure', option: 'minDate', value: '04-11-2026'}]);
});

test('the departure maximum is applied to the arrival calendar in its own format', () => {
    const arrival = field('arrival', {
        jsformat: 'dd.MM.yyyy', format: 'dd.mm.yy', connectedMin: '', connectedMax: ''
    });
    const departure = field('departure', {
        jsformat: 'dd-MM-yyyy', format: 'dd-mm-yy', connectedMin: '', connectedMax: ''
    });
    departure.dataset.connectedMax = 'arrival';
    departure.dataset.connectedMaxDays = '7';
    departure.value = '04-11-2026';
    const applied = [];
    show(arrival, opener([arrival, departure], applied));
    assert.deepEqual(applied, [{field: 'arrival', option: 'maxDate', value: '11.11.2026'}]);
});

test('a connected value that cannot be parsed leaves the calendar options alone', () => {
    const arrival = field('arrival', {jsformat: 'dd.MM.yyyy', format: 'dd.mm.yy'}, 'not a date');
    arrival.dataset.connectedMin = 'departure';
    arrival.dataset.connectedMinDays = '1';
    const departure = field('departure', {
        jsformat: 'dd-MM-yyyy', format: 'dd-mm-yy', connectedMin: '', connectedMax: ''
    });
    const applied = [];
    show(departure, opener([arrival, departure], applied));
    assert.deepEqual(applied, []);
});

test('two date fields that connect to each other do not recurse without end', () => {
    const arrival = field('arrival', {jsformat: 'dd-MM-yyyy', format: 'dd-mm-yy'}, '03-11-2026');
    const departure = field('departure', {jsformat: 'dd-MM-yyyy', format: 'dd-mm-yy'}, '');
    arrival.dataset.connectedMin = 'departure';
    arrival.dataset.connectedMinDays = '1';
    departure.dataset.connectedMin = 'arrival';
    departure.dataset.connectedMinDays = '1';
    const fields = [arrival, departure];
    const empty = {classList: {add() {}, remove() {}}};
    const applied = [];
    const ctx = context({
        $: makeDollar(fields, applied),
        document: {querySelector: selector => {
            const match = /name="([^"]+)"/.exec(selector);
            return match ? fields.find(el => el.name === match[1]) : null;
        }},
        SUPER: {init_datepicker_get_age: () => 1, after_field_change_blur_hook() {}},
        super_elements_i18n: {
            dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
        },
        parseInt
    });
    vm.runInContext(slice('    SUPER.init_connected_datepicker = function(', '\n    };'), ctx);
    fields.forEach(el => {
        el.closest = () => empty;
    });
    const real = ctx.SUPER.init_connected_datepicker;
    let calls = 0;
    ctx.SUPER.init_connected_datepicker = function () {
        calls++;
        assert.ok(calls < 10, 'recursion guard missing: ' + calls + ' calls');
        return real.apply(null, arguments);
    };
    // the format list is built inside the plugin's realm; date.js only accepts an array of that realm
    ctx.arrival = arrival;
    vm.runInContext("SUPER.init_connected_datepicker(arrival, '03-11-2026', ['dd-MM-yyyy'], 86400000)", ctx);
    assert.equal(arrival.value, '03-11-2026');
    assert.equal(departure.value, '04-11-2026');
    // arrival, then departure, then one refused re-entry into arrival
    assert.equal(calls, 3);
});
