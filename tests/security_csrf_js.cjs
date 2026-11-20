// Exercise the real shared browser transport without any network or application data.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(__dirname + '/../appli/js/security-csrf.js', 'utf8');
const routes = [...fs.readFileSync(__dirname + '/../appli/security_mutations.php', 'utf8').matchAll(/'([^']+\.php)'/g)].map(m => m[1]);
for (const base of ['/', '/nested/appli/']) {
    let prefilter, listener, replaced, submitted, fetched;
    const document = {
        body: {appendChild() {}},
        addEventListener(name, fn) { listener = fn; },
        createElement(tag) {
            return {tag, children: [], appendChild(child) { this.children.push(child); }, submit() { submitted = this; }};
        }
    };
    const window = {bridgetteSecurity: {base, token:'test-token', routes}, jQuery: true};
    const context = {window, document, URL, URLSearchParams,
        location: {origin:'https://club.test', href:'https://club.test'+base+'apk_howell64.php/', replace(url) {replaced = url;}},
        jQuery: {ajaxPrefilter(fn) {prefilter = fn;}},
        fetch(url, options) { fetched = {url, options}; return Promise.resolve({ok:true}); }
    };
    vm.runInNewContext(source, context);
    for (const route of routes) {
        const options = {url:base+route+'?idtournoi=5', type:'GET', data:'name=O%27Brien'};
        prefilter(options);
        assert.equal(options.type, 'POST');
        assert.equal(options.url, 'https://club.test'+base+route);
        assert.equal(options.data, 'idtournoi=5&name=O%27Brien');
        assert.equal(options.headers['X-CSRF-Token'], 'test-token');
        window.bridgetteNavigate(base+route+'?idtournoi=5');
        assert.equal(submitted.method, 'post');
        assert.equal(submitted.action, 'https://club.test'+base+route);
        assert.equal(submitted.children.find(e=>e.name==='_csrf').value, 'test-token');
    }
    for (const url of ['https://evil.test'+base+'erasetournoi.php', '/other/erasetournoi.php', base+'getfiche.php?id=7']) {
        const options = {url, type:'GET'};
        prefilter(options);
        assert.equal(options.type, 'GET');
        assert.equal(options.headers, undefined);
    }
    window.bridgetteNavigate(base+'bridge20.php');
    assert.equal(replaced, base+'bridge20.php');
    const nativeAction = {url:'/insertdonne.php?idtournoi=5', type:'GET', data:'points=420'};
    prefilter(nativeAction);
    assert.equal(nativeAction.url, 'https://club.test'+base+'insertdonne.php');
    assert.equal(nativeAction.type, 'POST');
    let prevented = false;
    listener({target: {closest() {return {href:'https://club.test'+base+'bridge49h.php?idtournoi=5', target:''};}}, preventDefault() {prevented=true;}});
    assert.equal(prevented, true);
    assert.equal(submitted.children.find(e=>e.name==='idtournoi').value, '5');
    window.bridgetteFinish(5, 2);
    assert.equal(fetched.url, 'https://club.test'+base+'setcnxfin.php');
    assert.equal(fetched.options.method, 'POST');
    assert.equal(fetched.options.body.get('_csrf'), 'test-token');
}
console.log('Browser transport: POST/header, form navigation, links, no token leak, Android PATH_INFO and nested directories OK.');
