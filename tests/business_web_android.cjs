// Compare les parcours à leurs fonctions originales, avant extraction.
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert/strict');
const crypto = require('crypto');
const root = path.resolve(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const reference = JSON.parse(read('tests/fixtures/web_android_reference.json'));
let checks = 0;
function run(source, outcome, contract) {
    const trace = [];
    const timers = [];
    const context = {
        idtournoi: 995, numNS: 3, numEO: 8, token: 'jeton-test',
        realdonne: {etui: 12, contrat: contract, declarant: 'N', entame: 'A', res: '=', points: 420},
        validok: 'element-validok', console: {log: (...args) => trace.push(['log', ...args])},
        goto64: () => trace.push(['goto64']), gotoindex: () => trace.push(['gotoindex']),
        setTimeout: (callback, delay) => { trace.push(['timer', delay]); timers.push(callback); }
    };
    context.$ = selector => Object.fromEntries(['addClass', 'removeClass', 'html'].map(method =>
        [method, value => trace.push([selector, method, value])]));
    context.$.get = (url, data, callback, type) => {
        trace.push(['get', url, data, type]);
        if (outcome !== 'failure') callback({ok: outcome, display: 'Résultat'});
        return {done() { return this; }, fail(callback) {
            if (outcome === 'failure') callback({}, '', 'réseau');
            return this;
        }};
    };
    vm.createContext(context);
    vm.runInContext(source, context);
    context.clickValidation();
    trace.push(['okkk', context.okkk ?? null]);
    timers.forEach(callback => callback());
    return trace;
}
for (const name of ['howell63', 'mitch63', 'apk_howell63', 'apk_mitch63']) {
    const page = read('appli/' + name + '.php');
    const adapter = page.slice(page.indexOf('function clickValidation()'), page.indexOf('function gotoindex()'));
    assert(page.includes('src="' + (name.startsWith('apk_') ? '/' : '') + 'js/bridge63.js"'));
    assert(!page.includes('js/validation63.js'));
    const bridge = read('appli/js/bridge63.js');
    const validation = bridge.slice(bridge.indexOf('function validationDonne(options)'));
    for (const outcome of [1, 0, -1, 'failure']) {
        for (const contract of ['4S', '3 SA', 'P', '2♥']) {
            assert.deepEqual(run(validation + '\n' + adapter, outcome, contract),
                run(reference[name], outcome, contract), name + '/' + outcome + '/' + contract);
            checks++;
        }
    }
}
const bridge25 = read('appli/js/bridge25.js');
const shared = bridge25.slice(bridge25.indexOf('function isNumeric(str)'));
assert.equal(crypto.createHash('sha256').update(shared).digest('hex'), reference.preinscription_sha256);
for (const name of ['bridge25', 'apk_bridge25']) {
    const page = read('appli/' + name + '.php');
    assert(!page.includes('class LimitedWeeksDatePicker'));
    assert(page.includes('src="<?php echo $relpgm; ?>js/bridge25.js"'));
    assert(!page.includes('js/preinscription25.js'));
}
const context = {document: {addEventListener() {}}, Date};
vm.createContext(context);
vm.runInContext(shared, context);
for (const [phone, expected] of [['01 23-45', true], ['abc', false], ['', false]]) {
    assert.equal(context.isNumeric(phone), expected); checks++;
}
for (const [mail, expected] of [['a@club.fr', true], ['a@', false], ['a b@club.fr', false]]) {
    assert.equal(context.isEmail(mail), expected); checks++;
}
assert.equal(context.formatISO(new Date(2026, 9, 8)), '2026-10-08'); checks++;
// Le calendrier garde ses 35 cases, les jours fermés et le choix de date.
let listeners = [];
const container = {innerHTML: '', querySelectorAll() {
    return [{dataset: {date: new Date(2026, 9, 8).toISOString()},
        addEventListener(event, callback) { listeners.push(callback); }}];
}};
context.container = container;
context.selected = null;
vm.runInContext("var picker = new LimitedWeeksDatePicker(container, {weeks: 5, dayStatus: () => ({selectable: false, title: 'Fermé'}), onSelect: date => selected = formatISO(date)});", context);
assert.equal((container.innerHTML.match(/data-date=/g) || []).length, 35); checks++;
assert(container.innerHTML.includes('disabled')); checks++;
listeners[0]();
assert.equal(context.selected, '2026-10-08'); checks++;
function resultTrace(source, initial, delta, ns, eo, failure) {
    const trace = [];
    const context = {actuel: initial, ndonnes: 24, selns: ns, seleo: eo, idtournoi: 995, relpgm: '/club/',
        console: {log: message => trace.push(['log', message])},
        showresultat: result => trace.push(['result', result]),
        $: selector => ({text: value => trace.push([selector, value])})};
    context.$.get = (url, data, callback, type) => {
        trace.push(['get', url, JSON.stringify(data), type]);
        if (!failure) callback('HTML donne');
        return {fail(callback) { if (failure) callback({}, '', 'réseau'); }};
    };
    vm.createContext(context);
    vm.runInContext(source, context);
    context.key_select(delta);
    return trace;
}
for (const name of ['bridge21', 'apk_bridge21']) {
    const page = read('appli/' + name + '.php');
    assert(page.includes('src="' + (name.startsWith('apk_') ? '/' : '') + 'js/bridge65.js"'));
    assert(!page.includes('js/resultats21.js'));
    const bridge = read('appli/js/bridge65.js');
    const results = bridge.slice(bridge.indexOf('function requestResultat21()'));
    assert(page.includes('requestResultat21();'));
    assert(!page.includes('f64getresultatdonne.php?'));
    for (const [initial, delta] of [[1, -10], [24, 10], [10, '1'], [10, -1], [12, 0]]) {
        for (const [ns, eo] of [[0, 0], [3, 0], [0, 8]]) {
            for (const failure of [false, true]) {
                assert.deepEqual(resultTrace(results, initial, delta, ns, eo, failure),
                    resultTrace(reference[name], initial, delta, ns, eo, failure));
                checks++;
            }
        }
    }
}
console.log(`${checks} contrôles web/Android: OK (requêtes, délais, navigation, erreurs, pré-inscription, résultats)`);
