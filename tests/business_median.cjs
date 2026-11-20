const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const values = {};
const sandbox = {
    document: {}, console: {log() {}},
    $: selector => ({
        text(value) { values[selector] = value; },
        val(value) { values[selector] = value; },
        addClass() {}, removeClass() {}, ready() {}, on() {}
    }),
    realdonne: {setContrat() {},setDeclarant() {},setEntame() {},setResultat() {}},
    maxtables: 0
};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(require('path').join(__dirname, '../appli/js/bridge45.js'), 'utf8'), sandbox);
function select(points, hweo, index, expected) {
    Object.assign(sandbox, {
        tabpoints: points, tabhweo: hweo,
        tabns: points.map((_,i)=>i+1), tabeo: points.map((_,i)=>i+2),
        tabcontrat: points.map(()=>'4 P'), tabjouepar: points.map(()=>'S'),
        tabentame: points.map(()=>'AT'), tabresultat: points.map(()=>'0'),
        totalpts: 0, nbpts: points.length
    });
    sandbox.sel_donne('nr_'+index);
    assert.equal(values['#mediane1'], expected);
    assert.equal(values['#moyenne2'], expected);
}
// Howell: selected third visible result. Hidden inverse scores produced -100.
select([-100,100,110,-110,100,-100], [0,1,0,1,0,1], 4, 5);
select(['-100','100','110','-110','100','-100'], ['0','1','0','1','0','1'], 4, 5);
// Mitchell: all other tables remain in the sample.
select([420,-100,110], [0,0,0], 0, 5);
select([420,-100,110,50], [0,0,0,0], 0, 50);
select([420,-100,110,50,50], [0,0,0,0,0], 0, 50);
select([420,-110,-100], [0,0,0], 0, -105);
select([420,0,0], [0,0,0], 0, 0);
select([420,100], [0,0], 0, 100);
const original = ['110','-100'];
assert.equal(sandbox.numMedian(original), 5);
assert.deepEqual(original, ['110','-100']);
console.log('9 median scenarios plus input preservation: OK');
