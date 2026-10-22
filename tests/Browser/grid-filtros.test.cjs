const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname, '../../public/js/grid-filtros.js'), 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));

function harness() {
    const listeners = {};
    const events = [];
    const calls = [];
    const location = {href: 'http://localhost/listado?page=3', origin: 'http://localhost'};
    const status = {setAttribute() {}};
    const input = {name: 'buscar', type: 'text', tagName: 'INPUT', value: 'familia'};
    const form = {
        dataset: {gridFilter: 'demo'}, action: 'http://localhost/listado', elements: [input],
        after() {}, closest: selector => selector === 'form[data-grid-filter]' ? form : null,
    };
    let current;
    function region(label) {
        return {
            label, dataset: {gridResults: 'demo'}, attributes: {},
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this.attributes[name]; },
            querySelectorAll() { return []; },
            replaceWith(next) { current = next; },
        };
    }
    current = region('original');
    const document = {
        querySelectorAll: selector => selector === 'form[data-grid-filter]' ? [form] : selector === '[data-grid-results]' ? [current] : [],
        createElement: () => status,
        addEventListener(name, callback) { listeners[name] = callback; },
        dispatchEvent(event) { events.push(event.type); },
        importNode: node => node,
    };
    const windowListeners = {};
    const context = {
        document, location, URL, URLSearchParams, AbortController,
        history: {pushState(state, title, url) { location.href = String(url); }},
        window: {addEventListener(name, callback) { windowListeners[name] = callback; }},
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        FormData: class { constructor() { return new Map([['buscar', input.value]]); } },
        DOMParser: class {
            parseFromString(text) {
                const data = JSON.parse(text);
                return {querySelectorAll: selector => selector === '[data-grid-results]' ? [region(data.label)] : [{dataset: form.dataset, elements: [{...input, value: data.search}]}]};
            }
        },
        fetch(url, options) {
            return new Promise((resolve, reject) => {
                calls.push({url: String(url), options, resolve, reject});
                options.signal.addEventListener('abort', () => reject(Object.assign(new Error('aborted'), {name: 'AbortError'})));
            });
        },
    };
    vm.runInNewContext(code, context);
    function submit() {
        const event = {target: form, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }};
        listeners.submit(event);
        assert.equal(event.defaultPrevented, true);
    }
    function respond(index, label, search) {
        calls[index].resolve({ok: true, headers: {get: () => 'text/html'}, text: async () => JSON.stringify({label, search})});
    }
    return {submit, respond, calls, input, status, events, location, form, windowListeners, listeners, current: () => current};
}

test('search replaces results, resets pagination and keeps filter controls', async () => {
    const h = harness();
    h.submit();
    assert.equal(h.calls[0].url, 'http://localhost/listado?buscar=familia');
    assert.equal(h.current().label, 'original');
    h.respond(0, 'filtered', 'familia');
    await tick();
    assert.equal(h.current().label, 'filtered');
    assert.equal(h.form.elements[0], h.input);
    assert.equal(h.location.href, 'http://localhost/listado?buscar=familia');
    assert.deepEqual(h.events, ['grid:beforeupdate', 'grid:updated']);
});

test('new searches cancel obsolete requests and errors preserve visible results', async () => {
    const h = harness();
    h.submit();
    h.input.value = 'nuevo';
    h.submit();
    assert.equal(h.calls[0].options.signal.aborted, true);
    h.respond(1, 'new-results', 'nuevo');
    await tick();
    assert.equal(h.current().label, 'new-results');
    h.submit();
    h.calls[2].resolve({ok: false, json: async () => ({errors: {desde: ['Fecha inválida']}})});
    await tick();
    assert.equal(h.current().label, 'new-results');
    assert.equal(h.status.textContent, 'Fecha inválida');
});

test('pagination and back navigation update only results', async () => {
    const h = harness();
    const link = {
        href: 'http://localhost/listado?buscar=familia&page=2', classList: {contains: () => true},
        hasAttribute: () => false, closest: selector => selector === '[data-grid-results]' ? h.current() : null,
    };
    let prevented = false;
    h.listeners.click({target: {closest: () => link}, button: 0, preventDefault() { prevented = true; }});
    assert.equal(prevented, true);
    h.respond(0, 'page-2', 'familia');
    await tick();
    assert.equal(h.current().label, 'page-2');
    h.location.href = 'http://localhost/listado';
    h.windowListeners.popstate();
    h.respond(1, 'initial', '');
    await tick();
    assert.equal(h.current().label, 'initial');
    assert.equal(h.input.value, '');
    assert.equal(h.location.href, 'http://localhost/listado');
});
