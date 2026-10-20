// PDF Generator client flow: debug mode must not submit, and any PDF error must show a message instead of hanging.
// Runs the real functions from common.js inside node:vm with a fake DOM, html2canvas and jsPDF.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, fs.existsSync(path.join(root, 'src/assets/js/common.js')) ? 'src/assets/js/common.js' : 'assets/js/common.js'), 'utf8');

// Top level SUPER functions are declared at 4 spaces; the function ends where the next one starts.
function extract(name){
    const marker = '\n    SUPER.' + name + ' = function';
    const start = source.indexOf(marker);
    if(start < 0) return '';
    const end = source.indexOf('\n    SUPER.', start + marker.length);
    assert(end > start, name + ': end not found');
    return source.slice(start, end);
}
const functions = ['process_form_data', 'pdf_generator_init', '_pdf_generator_done_callback', 'pdf_generator_abort', 'pdf_generator_prepare', 'pdf_generator_generate_page'];
for(const name of functions){
    if(name !== 'pdf_generator_abort') assert(extract(name), name + ' not found in common.js');
}
const code = functions.map(extract).join('\n');

const ERROR_TEXT = 'We couldn&#039;t create the PDF, so your form has not been sent yet. Please try again.';
const GENERATING_TEXT = 'Generating PDF file...';

const unhandled = [];
process.on('unhandledRejection', (reason) => { unhandled.push(reason); });

async function flush(){
    for(let i = 0; i < 20; i++) await new Promise((resolve) => setImmediate(resolve));
}
function classList(set){
    return {
        add: (c) => { set.add(c); },
        remove: (c) => { set.delete(c); },
        contains: (c) => set.has(c),
        get length(){ return set.size; }
    };
}
function makeNode(){
    const node = {
        classList: classList(new Set()), style: {}, dataset: {}, innerHTML: '', id: 'node', className: '', clientHeight: 10, value: '',
        querySelector: () => makeNode(), querySelectorAll: () => [], cloneNode: () => makeNode(),
        appendChild(){}, insertBefore(){}, remove(){}, addEventListener(){}, closest: () => null,
        parentNode: {insertBefore(){}}
    };
    return node;
}

function setup(opts = {}){
    const calls = {saveData: 0, reset: 0, buttonReset: 0, pdfSave: 0, addPage: 0, renders: 0, consoleError: 0, output: 0, errors: []};
    const overlayClasses = new Set();
    const innerText = {innerHTML: ''};
    const loadingOverlay = {
        classList: classList(overlayClasses),
        querySelector: (selector) => (selector === '.super-inner-text' ? innerText : makeNode())
    };
    const progressBar = {style: {}};
    // Like the real form: only prepared for the PDF once the prepare step ran
    const formClasses = new Set();
    const innerForm = {style: {}, clientHeight: opts.formHeight || 50};
    const form0 = {
        classList: classList(formClasses), style: {}, className: '', clientHeight: opts.formHeight || 50, id: 'super-form-7',
        closest: () => form0,
        querySelector: (selector) => {
            if(selector === 'form') return innerForm;
            if(selector === 'input[name="hidden_form_id"]') return {value: '7'};
            return null;
        },
        querySelectorAll: () => [],
        cloneNode: () => makeNode(),
        parentNode: {insertBefore(){}},
        nextSibling: null
    };
    const pdfSettings = Object.assign({
        generate: 'true', debug: 'false', generatingText: '', orientation: 'portrait', format: 'a4', customformat: '', unit: 'mm',
        filename: 'document.pdf', renderScale: 1, textRendering: 'false', adminEmail: 'true', confirmationEmail: 'true',
        emailLabel: 'PDF', downloadBtn: 'false',
        margins: {
            header: {top: 0, right: 0, bottom: 0, left: 0},
            body: {top: 0, right: 0, bottom: 0, left: 0},
            footer: {top: 0, right: 0, bottom: 0, left: 0}
        }
    }, opts.pdfSettings || {});
    let renderIndex = 0;
    function html2canvas(){
        calls.renders++;
        renderIndex++;
        if(opts.render) return opts.render(renderIndex);
        return Promise.resolve({toDataURL: () => 'data:image/jpeg;base64,'});
    }
    class FakePdf {
        constructor(){
            if(opts.jsPdfThrows) throw new Error('jsPDF failed');
            this.internal = {pageSize: {getWidth: () => 210, getHeight: () => 297}};
        }
        addImage(){}
        addPage(){ calls.addPage++; }
        addFileToVFS(){}
        addFont(){}
        output(){ calls.output++; return 'data:application/pdf;base64,'; }
        save(){ calls.pdfSave++; return Promise.resolve(); }
    }
    let responsiveCallback = null;
    const SUPER = {
        form_js: {7: {_pdf: pdfSettings}},
        save_data: () => {
            calls.saveData++;
            if(opts.saveThrows) throw new Error('save failed');
        },
        reset_submit_button_loading_state: () => { calls.buttonReset++; },
        update_variable_fields: {replace_tags: (args) => args.value},
        after_field_change_blur_hook: () => { if(opts.blurHookThrows) throw new Error('blur hook failed'); },
        pdf_generator_render_text: () => {},
        show_pdf_download_btn: () => {},
        // Like the real reset: returns false when the form is not (or no longer) prepared for the PDF
        pdf_generator_reset: () => {
            calls.reset++;
            if(!formClasses.has('super-generating-pdf')) return false;
            formClasses.delete('super-generating-pdf');
        },
        // The real function calls args.callback later (after a 500 ms timer); the test calls it by hand
        init_super_responsive_form_fields: (args) => { responsiveCallback = args.callback; },
        google_maps_api: {allMaps: {}}
    };
    const document = {
        documentElement: makeNode(),
        head: makeNode(),
        body: makeNode(),
        createElement: () => makeNode(),
        createTextNode: () => ({}),
        querySelector: () => makeNode(),
        getElementsByTagName: () => [makeNode()]
    };
    const context = vm.createContext({
        SUPER, document, html2canvas, jsPDF: FakePdf,
        window: {addEventListener(){}, getComputedStyle: () => ({height: '10px', getPropertyValue: () => '12px'})},
        super_common_i18n: {loadingOverlay: {generating_pdf: GENERATING_TEXT, completed: 'Completed!', pdf_generation_error: ERROR_TEXT}},
        setTimeout: (fn) => { fn(); return 0; },
        console: {log(){}, error(...values){ calls.consoleError++; calls.errors.push(values.map(String).join(' ')); }}
    });
    vm.runInContext(code, context);
    if(!opts.realPrepare){
        if(opts.prepareThrows){
            SUPER.pdf_generator_prepare = () => { throw new Error('prepare failed'); };
        }else{
            // Page determination as done by the real prepare step, without the DOM work
            SUPER.pdf_generator_prepare = (args, callback) => {
                formClasses.add('super-generating-pdf');
                args.pdfPageContainer = makeNode();
                args.pageOrientationChanges = {};
                args.scrollAmount = 100;
                args.totalPages = Math.ceil(innerForm.clientHeight / args.scrollAmount);
                args.totalPercentagePerPage = (100 / args.totalPages) / 3;
                args.pdfPercentageCompleted = 0;
                callback(args);
            };
        }
    }
    const args = {form_id: 7, form0, loadingOverlay, progressBar, data: {}};
    function run(){
        try {
            SUPER.process_form_data(args);
        } catch(error) {
            return error;
        }
        return null;
    }
    return {SUPER, args, calls, run, innerText, overlayClasses, progressBar, getResponsiveCallback: () => responsiveCallback};
}

function assertAborted(env, label){
    assert(env.overlayClasses.has('super-error'), label + ': popup shows the Close button');
    assert.equal(env.innerText.innerHTML, '<span>' + ERROR_TEXT + '</span>', label + ': translated error text');
    assert.equal(env.calls.reset, 1, label + ': form restored once');
    assert.equal(env.calls.saveData, 0, label + ': nothing submitted');
    assert.equal(env.args._pdfAborted, true, label + ': marked aborted');
}

test.beforeEach(() => { unhandled.length = 0; });
test.afterEach(() => { assert.deepEqual(unhandled, [], 'no unhandled promise rejections'); });

test('(a) debug mode downloads the PDF and does not submit', async () => {
    const env = setup({pdfSettings: {debug: 'true'}});
    assert.equal(env.run(), null);
    await flush();
    assert.equal(env.calls.pdfSave, 1, 'PDF downloaded');
    assert.equal(env.calls.saveData, 0, 'save_data not called in debug mode');
    assert(env.overlayClasses.has('super-success'));
    assert(!env.overlayClasses.has('super-error'));
});

test('(b) debug mode off submits once', async () => {
    const env = setup();
    assert.equal(env.run(), null);
    await flush();
    assert.equal(env.calls.saveData, 1);
    assert.equal(env.calls.pdfSave, 0);
    assert.equal(env.calls.reset, 1);
    assert(!env.overlayClasses.has('super-error'));
});

test('(c) html2canvas rejection shows the error and does not submit', async () => {
    const env = setup({render: () => Promise.reject(new Error('Out of memory'))});
    env.run();
    await flush();
    assertAborted(env, 'html2canvas rejects');
    assert(!env.innerText.innerHTML.includes('Out of memory'), 'raw error not shown');
});

test('(d) synchronous throw inside generate_page shows the error', async () => {
    const env = setup({blurHookThrows: true});
    env.run();
    await flush();
    assertAborted(env, 'throw before render');
    const env2 = setup({render: () => { throw new Error('html2canvas threw'); }});
    env2.run();
    await flush();
    assertAborted(env2, 'html2canvas throws synchronously');
});

test('(e) throw in the init/prepare stage shows the error', async () => {
    const prepare = setup({prepareThrows: true});
    prepare.run();
    await flush();
    assertAborted(prepare, 'prepare throws');
    assert.equal(prepare.calls.buttonReset, 1, 'prepare throws: submit button restored');

    const init = setup({jsPdfThrows: true});
    init.run();
    await flush();
    assert(init.overlayClasses.has('super-error'), 'init throws: popup shows the Close button');
    assert.equal(init.innerText.innerHTML, '<span>' + ERROR_TEXT + '</span>');
    assert.equal(init.calls.saveData, 0);
    // The form was never prepared, so reset returns early and the submit button is restored
    assert.equal(init.calls.reset, 1);
    assert.equal(init.calls.buttonReset, 1, 'init throws: submit button restored');

    // Page determination runs later (after the responsive classes update); no Google Maps data makes it throw
    const pages = setup({realPrepare: true});
    const thrown = pages.run();
    const responsiveCallback = pages.getResponsiveCallback();
    assert.equal(typeof responsiveCallback, 'function', 'real prepare reached page determination: ' + (thrown || pages.calls.errors.join('; ')));
    try { responsiveCallback(); } catch(error) { /* old code lets this escape */ }
    await flush();
    assertAborted(pages, 'page determination throws');
});

test('(f) multi-page run submits once without abort', async () => {
    const env = setup({formHeight: 250});
    assert.equal(env.run(), null);
    await flush();
    assert.equal(env.calls.renders, 3, 'three pages rendered');
    assert.equal(env.calls.addPage, 2, 'two extra pages added');
    assert.equal(env.calls.saveData, 1, 'submitted once');
    assert.equal(env.calls.reset, 1);
    assert.equal(env.calls.output, 1);
    assert.equal(env.progressBar.style.width, '100%');
    assert.equal(env.args.data._generated_pdf_file.files[0].name, 'document.pdf');
    assert(!env.overlayClasses.has('super-error'));
    assert.notEqual(env.args._pdfAborted, true);
    assert.equal(env.calls.consoleError, 0);
});

test('(g) a render that finishes after an abort does not submit', async () => {
    let resolveRender;
    const env = setup({render: () => new Promise((resolve) => { resolveRender = resolve; })});
    env.run();
    await flush();
    assert.equal(typeof resolveRender, 'function', 'render started');
    assert.equal(typeof env.SUPER.pdf_generator_abort, 'function', 'SUPER.pdf_generator_abort exists');
    env.SUPER.pdf_generator_abort(env.args, new Error('stopped'));
    resolveRender({toDataURL: () => 'data:image/jpeg;base64,'});
    await flush();
    assertAborted(env, 'late render');
    assert.equal(env.calls.output, 0, 'no PDF attached after abort');
});

test('(h) an error from the submission after the PDF is done is not a PDF error', async () => {
    const env = setup({saveThrows: true});
    env.run();
    await flush();
    assert.equal(env.calls.saveData, 1);
    assert(!env.overlayClasses.has('super-error'), 'no PDF error shown');
    assert.notEqual(env.innerText.innerHTML, '<span>' + ERROR_TEXT + '</span>');
    assert.equal(env.calls.reset, 1, 'only the normal reset after the PDF was done');
    assert.equal(env.calls.buttonReset, 0);
    assert.notEqual(env.args._pdfAborted, true);
    assert(env.calls.consoleError >= 1, 'submission error is logged');
});
