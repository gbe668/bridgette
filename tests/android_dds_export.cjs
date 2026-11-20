const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('appli/js/bridge65.js', 'utf8');
const code = source.slice(source.indexOf('var pbnfile = false;'), source.indexOf('// Lecture d\'une donne commune'));
function setup(android) {
    const state = { disabled: false, hidden: false, downloads: 0, alerts: [], urls: [] };
    const context = {
        console, Blob, club: 'Club test', datef: '16/10/2026',
        diagrammes: [{ etui: 1, deal: 'N:AKQ.J.T9.876 2.AKQ.J.T9 JT9.876.543.2 876.5432.AKQ.A' }],
        etuis: [null, [0, 1, 0, 0]],
        $: selector => ({
            prop: (key, value) => { state.disabled = value; },
            show: () => {}, hide: () => { if (selector === '#btnexpdds') state.hidden = true; }
        }),
        URL: { createObjectURL: () => 'blob:test' },
        document: { createElement: () => ({ click: () => state.downloads++, remove: () => {} }) },
        alert: message => state.alerts.push(message),
        open: url => state.urls.push(url)
    };
    if (android) context.Android = android;
    vm.createContext(context);
    vm.runInContext(code, context);
    return { context, state };
}
let received;
const native = setup({ savePbn: text => { received = text; }, openDDSolver: () => {} });
native.context.exportDDSolver();
assert.match(received, /\[Board "1"\]\r\n\[Dealer "N"\]/);
assert.equal(native.state.disabled, true);
assert.equal(native.context.pbnfile, false);
assert.equal(native.state.downloads, 0);
received = null;
native.context.exportDDSolver();
assert.equal(received, null, 'double click must not start another export');
native.context.onPbnExportResult(false);
assert.equal(native.state.disabled, false);
assert.equal(native.state.hidden, false);
native.context.exportDDSolver();
assert.ok(received, 'cancellation/error allows retry');
native.context.onPbnExportResult(true);
assert.equal(native.context.pbnfile, true);
assert.equal(native.state.hidden, true);
for (const android of [undefined, { processNext() {} }]) {
    const web = setup(android);
    web.context.exportDDSolver();
    assert.equal(web.state.downloads, 1, 'web and older APK keep existing download path');
    assert.equal(web.context.pbnfile, true);
    web.context.DDSolver();
    assert.equal(web.state.urls.length, 1);
}
const failure = setup({ savePbn() { throw Error('bridge failed'); } });
failure.context.exportDDSolver();
assert.equal(failure.state.disabled, false);
assert.equal(failure.context.pbnExportPending, false);
assert.equal(failure.context.pbnfile, false);
assert.equal(failure.state.alerts.length, 1);
let opened = false;
const solver = setup({ openDDSolver() { opened = true; } });
solver.context.DDSolver();
assert.equal(opened, true);
assert.equal(solver.state.urls.length, 0);
console.log('OK: export PBN, confirmation, annulation/erreur, double clic, compatibilité web/anciennes APK et ouverture DDSolver.');
