const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname+'/../appli/bridge42.php','utf8');
const fn = source.slice(source.indexOf('function changepaquet('), source.indexOf('</script>',source.indexOf('function changepaquet(')));
let checks=0;
for (const [pairs,packets,positions] of [[4,6,6],[5,4,4],[6,5,5],[7,7,7],[8,7,7],[9,9,9],[10,9,9],[11,11,9],[12,11,9]]) {
  const fields={};
  const ctx={paquet:3,maxpaquet:Math.floor(36/packets),ntables:pairs,npaquets:packets,npositions:positions,genre:2,t_howell:2,dureedonne:420,dureediagrammes:0,dureeinitiale:120,setTimeout(){},$:(id)=>({text(v){fields[id]=v},html(){}})};
  vm.createContext(ctx);vm.runInContext(fn,ctx);
  vm.runInContext('changepaquet(0)',ctx);
  assert.equal(fields['#ndonnes'],packets*3);
  assert.equal(fields['#njouees'],positions*3);checks+=2;
  if(pairs>=11){vm.runInContext('changepaquet(1)',ctx);assert.equal(ctx.paquet,3);checks++;}
}
// Le calcul Mitchell reste fondé sur le nombre de tables.
const fields={};const ctx={paquet:3,maxpaquet:6,ntables:6,npositions:6,genre:1,t_howell:2,dureedonne:420,dureediagrammes:0,dureeinitiale:120,$:id=>({text(v){fields[id]=v}})};
vm.createContext(ctx);vm.runInContext(fn+';changepaquet(0)',ctx);
assert.equal(fields['#ndonnes'],18);checks++;
console.log(`${checks} contrôles préparation paquets : OK`);
