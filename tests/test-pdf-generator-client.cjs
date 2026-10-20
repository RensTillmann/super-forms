// PDF Generator client flow: SUPER.process_form_data -> pdf_generator_init -> generate pages -> done callback -> save.
// Runs the real functions from common.js in node:vm with stubbed browser parts (html2canvas, jsPDF, popup, form).
// Run with: node --test tests/test-pdf-generator-client.cjs
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

const root = path.resolve(__dirname, '..');
const pluginDir = fs.existsSync(path.join(root, 'src/assets/js/common.js')) ? path.join(root, 'src') : root;
const source = fs.readFileSync(path.join(pluginDir, 'assets/js/common.js'), 'utf8');
const pluginPhp = fs.readFileSync(path.join(pluginDir, 'super-forms.php'), 'utf8');

const ERROR_TEXT = "We couldn't create the PDF, so your form has not been sent yet. Please try again.";

// Cut one top level `SUPER.name = function` statement out of common.js (up to the next top level `SUPER.` statement)
function extract(name, required) {
    const marker = '\n    SUPER.' + name + ' = function';
    const start = source.indexOf(marker);
    if (start < 0) {
        assert(!required, 'SUPER.' + name + ' not found in common.js');
        return '';
    }
    const end = source.indexOf('\n    SUPER.', start + marker.length);
    assert(end > start, 'end of SUPER.' + name + ' not found');
    return source.slice(start, end) + '\n';
}
// pdf_generator_abort and pdf_generator_start are new; when missing the cases below fail on behaviour, not on loading
const code = [
    ['process_form_data', true],
    ['pdf_generator_init', true],
    ['pdf_generator_start', false],
    ['_pdf_generator_done_callback', true],
    ['recheckCanvasImages', true],
    ['pdf_generator_generate_page', true],
    ['pdf_generator_abort', false]
].map(([name, required]) => extract(name, required)).join('');

function classList(initial) {
    const set = new Set(initial || []);
    return { add: c => { set.add(c); }, remove: c => { set.delete(c); }, contains: c => set.has(c) };
}

function deferred() {
    let resolve, reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
}

async function settle() {
    for (let i = 0; i < 30; i++) await new Promise(resolve => setImmediate(resolve));
}

function setup(options = {}) {
    const pages = options.pages || 1;
    const calls = {
        save: 0, reset: 0, buttonReset: 0, html2canvas: 0, addImage: 0, addPage: 0, pdfSave: 0,
        fontFetch: 0, fontUrls: [], vfs: [], fonts: [], errors: [], warnings: [], logs: [], delays: []
    };
    const canvas = { toDataURL: () => 'data:image/jpeg;base64,AAAA' };
    // One element spread over all pages, so every page reports a next page until the last one
    const belongsNodes = [{
        dataset: { belongsToPages: JSON.stringify(Array.from({ length: pages }, (v, i) => i + 1)) },
        classList: classList(), style: {}
    }];
    const form = {
        classList: classList(['super-form']),
        closest: () => form,
        querySelectorAll: selector => selector === '[data-belongs-to-pages]' ? belongsNodes : []
    };
    const flagNode = { classList: classList(), closest: () => null, dataset: {} };
    const pdfPageContainer = {
        style: {},
        querySelectorAll: () => [],
        querySelector: selector => {
            if (selector.indexOf('.super-int-phone_flag') !== -1) {
                return options.flagImage && !flagNode.classList.contains('super-canvas-initialized') ? flagNode : null;
            }
            return { clientHeight: 100, style: {} };
        }
    };
    const pageContainer = { classList: classList(['super-pdf-page-container']) };
    const innerText = { innerHTML: '' };
    const loadingOverlay = {
        classList: classList(['super-loading-overlay']),
        querySelector: selector => selector === '.super-inner-text' ? innerText : null
    };
    const pdfSettings = {
        generate: 'true', generatingText: '', orientation: 'portrait', format: 'a4', customformat: '', unit: 'mm',
        filename: 'form.pdf', renderScale: 1, imageQuality: 'FAST', textRendering: 'false', native: 'false',
        debug: options.debug ? 'true' : 'false', downloadBtn: 'false', adminEmail: 'true', confirmationEmail: 'true', emailLabel: 'PDF'
    };
    class FakePdf {
        constructor(settings) {
            this.settings = settings;
            this.internal = { pageSize: { getWidth: () => 210, getHeight: () => 297 } };
        }
        addFileToVFS(name, data) { calls.vfs.push([name, data]); }
        addFont(...fontArgs) { calls.fonts.push(fontArgs); }
        addImage() { calls.addImage++; }
        addPage() { calls.addPage++; }
        output() { return 'data:application/pdf;base64,AAAA'; }
        save() { calls.pdfSave++; return Promise.resolve(); }
    }
    const SUPER = {
        form_js: { 7: { _pdf: pdfSettings } },
        save_data: () => {
            calls.save++;
            if (options.saveThrows) throw new Error('save step failed');
        },
        pdf_generator_reset: f => {
            calls.reset++;
            if (options.resetThrows) throw new Error('reset failed');
            if (f && !f.classList.contains('super-generating-pdf')) return false;
            f.classList.remove('super-generating-pdf');
        },
        reset_submit_button_loading_state: () => { calls.buttonReset++; },
        pdf_generator_prepare: (a, callback) => {
            if (options.prepareThrows) throw new Error('prepare failed');
            a.form0.classList.add('super-generating-pdf');
            SUPER.recheckCanvasImages(a, () => { callback(a); });
        },
        pdfWrapTextNodes: () => {},
        pdfWrapTextNodesRender: () => {},
        pdf_determine_pages: (a, callback) => {
            if (options.determineThrows) throw new Error('determine pages failed');
            a.pageOrientationChanges = { 1: a.orientation };
            a.totalPages = pages;
            a.totalPercentagePerPage = (100 / pages) / 3;
            a.pdfPercentageCompleted = 0;
            callback(a);
        },
        reposition_slider_element: () => {
            if (options.pageThrows) throw new Error('page failed');
        },
        pdf_get_header_height: () => 20,
        pdf_get_footer_height: () => 20,
        round: value => value,
        pdf_generator_render_text: a => a,
        pdf_generator_draw_pdf_text: () => {},
        update_variable_fields: { replace_tags: o => o.value },
        show_pdf_download_btn: () => {},
        pdf_get_font_data_from_url: url => {
            calls.fontFetch++;
            calls.fontUrls.push(url);
            const reply = (options.fontReplies || [])[calls.fontFetch - 1];
            if (reply instanceof Error) return Promise.reject(reply);
            return Promise.resolve(reply === undefined ? null : reply);
        }
    };
    const super_common_i18n = {
        loadingOverlay: { generating_pdf: 'Generating PDF file...', completed: 'Completed!', pdf_generation_error: ERROR_TEXT }
    };
    if (options.fontReplies) super_common_i18n.fonts = { link: '/fonts/noto-sans' };
    const context = {
        SUPER,
        super_common_i18n,
        window: { jspdf: { jsPDF: FakePdf } },
        document: { body: { children: [] }, querySelector: selector => selector === '.super-pdf-page-container' ? pageContainer : null },
        $: () => ({ parents: () => ({ length: 0 }) }),
        html2canvas: node => {
            calls.html2canvas++;
            return options.render ? options.render(calls.html2canvas, node === flagNode ? 'flag' : 'page', canvas) : Promise.resolve(canvas);
        },
        // Fake timer: runs the callback right away
        setTimeout: (callback, delay) => { calls.delays.push(delay); callback(); return 0; },
        console: {
            log: (...a) => { calls.logs.push(a.map(String).join(' ')); },
            error: (...a) => { calls.errors.push(a.map(String).join(' ')); },
            warn: (...a) => { calls.warnings.push(a.map(String).join(' ')); }
        }
    };
    vm.runInNewContext(code, context);
    const args = {
        form_id: 7, form0: form, loadingOverlay, progressBar: null, data: {},
        pdfPageContainer, pdfHeader: {}, pdfFooter: {}
    };
    return { SUPER, args, calls, form, innerText, loadingOverlay, pdfSettings };
}

function assertAborted(env, message) {
    assert.equal(env.calls.save, 0, message + ': nothing is submitted');
    assert(env.loadingOverlay.classList.contains('super-error'), message + ': popup gets super-error (Close button)');
    assert.equal(env.innerText.innerHTML, '<span>' + ERROR_TEXT + '</span>', message + ': translated error text');
    assert.equal(env.calls.reset, 1, message + ': form is reset once');
    assert(env.calls.errors.length >= 1, message + ': error is logged with console.error');
    assert.equal(env.args._pdfDone === true, false, message + ': PDF is not marked done');
}

test('loadingOverlay has the pdf_generation_error string', () => {
    assert(pluginPhp.includes("'pdf_generation_error' => esc_html__( 'We couldn\\'t create the PDF, so your form has not been sent yet. Please try again.', 'super-forms' ),"));
});

test('(a) html2canvas rejects on a page: error popup, reset once, no save', async () => {
    const env = setup({ pages: 2, render: (n, kind, canvas) => n === 2 ? Promise.reject(new Error('out of memory')) : Promise.resolve(canvas) });
    env.SUPER.process_form_data(env.args);
    await settle();
    assertAborted(env, 'page render rejected');
    assert.equal(env.calls.html2canvas, 2);
    assert(!env.innerText.innerHTML.includes('out of memory'), 'raw error message is never shown');
    assert.equal(env.calls.buttonReset, 0, 'pdf_generator_reset already restored the submit button');
});

test('(a) html2canvas rejects on a flag image while preparing: error popup, reset once, no save', async () => {
    const env = setup({ flagImage: true, render: (n, kind, canvas) => kind === 'flag' ? Promise.reject(new Error('broken image')) : Promise.resolve(canvas) });
    env.SUPER.process_form_data(env.args);
    await settle();
    assertAborted(env, 'flag render rejected');
    assert.equal(env.calls.html2canvas, 1, 'no page is rendered');
});

test('(b) synchronous error inside generate_page: error popup, reset once, no save', async () => {
    const env = setup({ pageThrows: true });
    env.SUPER.process_form_data(env.args);
    await settle();
    assertAborted(env, 'generate_page threw');
    assert.equal(env.calls.html2canvas, 0);
});

test('(c) error in prepare: error popup, submit button restored, no save', async () => {
    const env = setup({ prepareThrows: true });
    env.SUPER.process_form_data(env.args);
    await settle();
    assertAborted(env, 'prepare threw');
    assert.equal(env.calls.buttonReset, 1, 'form was not in PDF mode yet, so the submit button is reset directly');
});

test('(c) error while determining pages: error popup, reset once, no save', async () => {
    const env = setup({ determineThrows: true });
    env.SUPER.process_form_data(env.args);
    await settle();
    assertAborted(env, 'pdf_determine_pages threw');
    assert.equal(env.calls.html2canvas, 0);
});

test('(d) normal multi-page PDF: saved once, no abort, no extra timer', async () => {
    const env = setup({ pages: 3 });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.save, 1);
    assert.equal(env.calls.html2canvas, 3);
    assert.equal(env.calls.addImage, 3);
    assert.equal(env.calls.addPage, 2);
    assert.equal(env.calls.reset, 1);
    assert.equal(env.calls.errors.length, 0);
    assert(!env.loadingOverlay.classList.contains('super-error'));
    assert.equal(env.innerText.innerHTML, '<span>Generating PDF file...</span>');
    assert.equal(env.args.data._generated_pdf_file.files[0].datauristring, 'data:application/pdf;base64,AAAA');
    assert.equal(env.args.data._generated_pdf_file.exclude, 0);
    assert.deepEqual(env.calls.delays, [5000, 200, 0, 0], 'only the existing timers are used');
});

test('(e) render that finishes after an abort does not submit', async () => {
    // reset throws, so the form keeps its super-generating-pdf class and only the abort flag stops the late render
    const pending = deferred();
    const env = setup({ resetThrows: true, render: () => pending.promise });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.html2canvas, 1);
    env.SUPER.pdf_generator_abort(env.args, new Error('stopped while rendering'));
    pending.resolve({ toDataURL: () => 'data:image/jpeg;base64,AAAA' });
    await settle();
    assertAborted(env, 'abort while rendering');
    assert.equal(env.calls.buttonReset, 1, 'reset failed, so the submit button is reset directly');
    assert.equal(env.calls.addImage, 0, 'late render is ignored');
});

test('(f) error thrown by the save step after the PDF is done is not shown as a PDF error', async () => {
    const env = setup({ pages: 2, saveThrows: true });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.save, 1);
    assert.equal(env.args._pdfDone, true);
    assert(!env.loadingOverlay.classList.contains('super-error'), 'no PDF error popup');
    assert.notEqual(env.innerText.innerHTML, '<span>' + ERROR_TEXT + '</span>');
    assert.equal(env.calls.reset, 1, 'only the done callback reset the form');
    assert(env.calls.errors.some(line => line.includes('save step failed')), 'save error is logged with console.error');
});

test('(g) font file null twice: fetched twice, default font, PDF still generated and saved', async () => {
    const env = setup({ fontReplies: [null, null] });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.fontFetch, 2);
    assert.deepEqual(env.calls.fontUrls, ['/fonts/noto-sans.json', '/fonts/noto-sans.json']);
    assert.equal(env.calls.vfs.length, 0, 'no font added');
    assert.equal(env.calls.warnings.length, 1, 'missing font is reported with console.warn');
    assert.equal(env.calls.html2canvas, 1);
    assert.equal(env.calls.save, 1);
    assert(!env.loadingOverlay.classList.contains('super-error'));
});

test('(g) font fetch rejects then null: default font, PDF still generated and saved', async () => {
    const env = setup({ fontReplies: [new Error('network down'), null] });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.fontFetch, 2);
    assert.equal(env.calls.vfs.length, 0);
    assert.equal(env.calls.save, 1);
    assert(!env.loadingOverlay.classList.contains('super-error'));
});

test('(h) font file null then data: font used, PDF generated and saved', async () => {
    const env = setup({ fontReplies: [null, { regular: 'REGULAR', bold: 'BOLD' }] });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.fontFetch, 2);
    assert.deepEqual(env.calls.vfs, [['NotoSans-Regular-normal.ttf', 'REGULAR'], ['NotoSans-Bold-bold.ttf', 'BOLD']]);
    assert.deepEqual(env.calls.fonts, [['NotoSans-Regular-normal.ttf', 'NotoSans-Regular', 'normal'], ['NotoSans-Bold-bold.ttf', 'NotoSans-Bold', 'bold']]);
    assert.equal(env.calls.warnings.length, 0);
    assert.equal(env.calls.save, 1);
});

test('font file loads on the first try: fetched once, unchanged behaviour', async () => {
    const env = setup({ fontReplies: [{ regular: 'REGULAR' }] });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.fontFetch, 1);
    assert.deepEqual(env.calls.vfs, [['NotoSans-Regular-normal.ttf', 'REGULAR'], ['NotoSans-Bold-bold.ttf', 'REGULAR']]);
    assert.equal(env.calls.save, 1);
    assert.equal(env.calls.delays.includes(5000), false, 'font path starts without the 5 second wait');
});

test('(i) debug mode downloads the PDF and does not submit', async () => {
    const env = setup({ debug: true, pages: 2 });
    env.SUPER.process_form_data(env.args);
    await settle();
    assert.equal(env.calls.save, 0, 'save_data is not called in debug mode');
    assert.equal(env.calls.pdfSave, 1, 'PDF is downloaded');
    assert(env.loadingOverlay.classList.contains('super-success'));
    assert(!env.loadingOverlay.classList.contains('super-error'));
});
