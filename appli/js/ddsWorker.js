/*
 * ddsWorker.js
 * Web Worker : charge le module DDS compilé en WebAssembly et répond
 * aux demandes de calcul de table DD envoyées par le thread principal.
 *
 * A placer à côté de dds.js et dds.wasm (générés par la compilation
 * Emscripten), et servi depuis votre serveur web.
 */

/*
 * ddsWorker.js
 * Web Worker : charge le module DDS compilé en WebAssembly et répond
 * aux demandes de calcul de table DD envoyées par le thread principal.
 *
 * A placer à côté de dds.js et dds.wasm (générés avec -sMODULARIZE=1
 * -sEXPORT_NAME="createDDSModule"), et servi depuis votre serveur web.
 */

let ddsReady = false;
let Module = null;
let solveTable = null; // fonction "wrappée" (ccall) prête à l'emploi

// dds.js compilé avec -sMODULARIZE=1 expose une factory function
// (nommée ici createDDSModule) qui renvoie une Promise résolue avec
// l'instance du module, une fois le WASM chargé et initialisé.
importScripts('dds.js');

createDDSModule().then(function (mod) {
    Module = mod;
    Module.ccall('dds_init', 'number', [], []);

    // Prépare l'appel réutilisable à do_dds_solve_table(const char* pbn)
    solveTable = Module.cwrap('do_dds_solve_table', 'number', ['number']);

    ddsReady = true;
    postMessage({ type: 'ready' });
}).catch(function (err) {
    postMessage({ type: 'error', message: 'Echec de chargement DDS: ' + err.message });
});

self.onmessage = function (e) {
    if (!ddsReady) {
        postMessage({ type: 'error', message: 'DDS pas encore initialisé' });
        return;
    }

    const { donne, pbn } = e.data;
    const start = performance.now();

    // Alloue la chaîne PBN dans le tas WASM
    const pbnPtr = Module.stringToNewUTF8(pbn);

    // Appelle CalcDDtablePBN via le wrapper ; renvoie un pointeur vers
    // un tableau de 20 entiers (5 couleurs x 4 joueurs)
    const resultPtr = solveTable(pbnPtr);

    // Lit les 20 entiers depuis la mémoire WASM
    const raw = new Array(20);
    for (let i = 0; i < 20; i++) {
        raw[i] = Module.getValue(resultPtr + i * 4, 'i32');
    }

    Module._free(pbnPtr);
    // (pas besoin de libérer resultPtr : c'est un buffer statique côté C)

    // ---- Recodage identique à votre fonction clickValidiags() actuelle ----
    // raw est organisé [strain][hand] : strain 0..4 = S,H,D,C,NT ; hand 0..3 = N,E,S,W
    // Votre code veut : pour chaque joueur (N,S,E,W dans tabj),
    //                   pour chaque "couleur" dans l'ordre N,S,H,D,C (tabi, où
    //                   'N' ici désigne le contrat Sans-Atout, pas le joueur Nord)
    const strainOrder = ['NT', 'S', 'H', 'D', 'C']; // tabi = ['N','S','H','D','C'] chez vous
    const strainIndex = { S: 0, H: 1, D: 2, C: 3, NT: 4 };
    const handOrder = ['N', 'S', 'E', 'W']; // tabj chez vous
    const handIndex = { N: 0, E: 1, S: 2, W: 3 };

    let dds = '';
    for (const hand of handOrder) {
        for (const strain of strainOrder) {
            const flatIdx = strainIndex[strain] * 4 + handIndex[hand];
            const value = raw[flatIdx];
            dds += String.fromCharCode(97 + value);
        }
    }

    const delta = performance.now() - start;

    postMessage({ type: 'result', donne: donne, dds: dds, delta: delta });
};
