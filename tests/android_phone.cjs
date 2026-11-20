const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const escape = s => String(s).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('"', '&quot;');
class Element {
    constructor(tag) { this.tag = tag; this.textContent = ''; this.children = []; this.attrs = {}; }
    showModal() {}
    close() {}
    remove() {}
    focus() {}
    appendChild(child) { this.children.push(child); }
    setAttribute(key, value) { this.attrs[key] = value; }
    getAttribute(key) { return this.attrs[key]; }
    get innerHTML() { return escape(this.textContent) + this.children.map(c => c.outerHTML).join(''); }
    get outerHTML() { return `<${this.tag} class="${this.className || ''}" ${this.hidden ? 'hidden' : ''} ${Object.entries(this.attrs).map(([k,v]) => `${k}="${escape(v)}"`).join(' ')}>${this.innerHTML}</${this.tag}>`; }
}
function setup(android) {
    const handlers = {};
    const context = { document: {createElement: tag => new Element(tag), getElementById: () => null, body: new Element('body')}, alert: () => {} };
    context.$ = () => ({ ready: fn => fn(), each: () => {}, on: (event, selector, fn) => { handlers[selector] = fn; } });
    if (android) context.Android = android;
    vm.createContext(context);
    vm.runInContext(fs.readFileSync('appli/js/apk_phone.js', 'utf8'), context);
    return {context, handlers};
}
for (const android of [undefined, {}]) {
    assert.equal(setup(android).context.androidPhoneHtml('01 23 45 67 89'), '01 23 45 67 89');
}
let received;
const {context, handlers} = setup({dialPhone: number => received = number});
for (const input of ['01 23 45 67 89', '+33 (1) 23-45.67.89']) {
    const html = context.androidPhoneHtml(input);
    assert.match(html, /apk-phone-select/);
    assert.doesNotMatch(html, /Appeler|apk-phone-call/);


}
for (const input of ['', null, 'abc', '*123#', '123;456', '1234567890123456']) {
    assert.doesNotMatch(context.androidPhoneHtml(input), /Appeler/);
}
assert.equal(context.androidPhoneHtml('<img>'), '&lt;img>');
const select = new Element('button');
select.textContent = '01 23 45 67 89';
select.setAttribute('data-phone', '+33123456789');
select.setAttribute('data-email', 'joueur@example.test');
handlers['.apk-phone-select'].call(select);
let popup = context.document.body.children.at(-1);
assert.equal(popup.children[0].textContent, 'Mail : joueur@example.test');
assert.equal(popup.children[1].textContent, 'Appeler le 01 23 45 67 89 ?');
assert.equal(received, undefined);
popup.children[2].children[0].onclick();
assert.equal(received, undefined);
handlers['.apk-phone-select'].call(select);
popup = context.document.body.children.at(-1);
popup.children[2].children[1].onclick();
assert.equal(received, '+33123456789');
received = undefined;
handlers['.apk-phone-select'].call(select);
let prevented = false;
context.document.body.children.at(-1).oncancel({preventDefault: () => prevented = true});
assert.equal(prevented, true);
assert.equal(received, undefined);
select.setAttribute('data-email', '');
handlers['.apk-phone-select'].call(select);
assert.equal(context.document.body.children.at(-1).children[0].textContent, 'Mail : non renseigné');
assert.match(context.androidPhoneHtml('0123456789', '<img>"'), /data-email="&lt;img>&quot;"/);
console.log('OK : anciennes APK, formats, confirmation et annulation avant appel.');
