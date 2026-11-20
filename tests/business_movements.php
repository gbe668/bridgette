<?php
// Standalone characterization of the movement catalogue; no application DB.
$t_unknown = 0; $t_mitchell = 1; $t_howell = 2;
$st_position = [0=>'???',1=>'Nord',2=>'Est',3=>'Sud',4=>'Ouest'];
$st_vulnerable = [0=>'Personne vulnérable',1=>'Nord-Sud vulnerables',2=>'Est-Ouest vulnerables',3=>'Tous vulnérables'];
require_once __DIR__.'/../appli/libmouvements.php';
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function movementSame($actual, $expected, $label) {
    global $checks;
    $checks++;
    if ($actual !== $expected) throw new RuntimeException($label.': '.var_export($actual, true));
}
$snapshots = [];
$howellPackets = [4=>6, 5=>4, 6=>5, 7=>7, 8=>7, 32=>9, 33=>9, 34=>11, 35=>11];
$types = array_merge(range(4,30), [32,33,34,35,47,48]);
foreach ($types as $id) {
    $tt = gettypetournoi($id);
    movementSame(getnpaires($id), $tt['npaires'], 'pair count '.$id);
    $isHowell = $tt['genre'] === $t_howell;
    if ($isHowell) {
        movementSame(computetype($tt['npaires'], 0), $id, 'Howell type '.$id);
        movementSame($tt['npaquets'], $howellPackets[$id], 'Howell packet stock '.$id);
        movementSame($tt['maxpaquet'], intdiv(36, $howellPackets[$id]), 'Howell packet limit '.$id);
        if (in_array($id, [34,35], true)) {
            movementSame($tt['npositions'], 9, 'default rounds '.$id);
            movementSame($tt['maxpos'], 11, 'maximum rounds '.$id);
        }
    } else {
        $pns = $tt['ntables']; $peo = $tt['npaires'] - $pns;
        movementSame(computetype($pns, $peo), $id, 'Mitchell type '.$id);
    }
    for ($packet = 1; $packet <= $tt['maxpaquet']; $packet++) {
        // The new stock field is checked independently above; keep historical
        // fingerprints comparable for otherwise unchanged catalogue entries.
        $catalogueSnapshot = $tt;
        unset($catalogueSnapshot['npaquets']);
        $values = [$catalogueSnapshot, getmaxpositions($id, $packet)];
        if ($isHowell) {
            // Exercise the complete schedule, including optional rounds 10/11.
            $rounds = $tt['maxpos'];
            $stock = $howellPackets[$id] * $packet;
            for ($round = 1; $round <= $rounds; $round++) {
                for ($pair = 1; $pair <= $tt['npaires']; $pair++) {
                    $p = getposhowell($id, $pair, $round, $packet);
                    movementSame($p['last'] >= 0 && $p['last'] + $packet <= $stock, true, 'boards within stock');
                    $values[] = $p;
                    if ((int)$p['adversaire'] > 0) {
                        $other = getposhowell($id, (int)$p['adversaire'], $round, $packet);
                        movementSame((int)$other['adversaire'], $pair, 'reciprocal opponent');
                        movementSame($other['table'], $p['table'], 'same table');
                        movementSame($other['last'], $p['last'], 'same boards');
                        movementSame($other['NS'], 3 - $p['NS'], 'opposite orientation');
                    }
                }
                $values[] = getrelaishowell($id, $round, $packet);
                $html = htmlPositionHowell($id, $tt['npaires'], $round, $packet, $stock);
                // Fixed regression: eleven pairs must keep packet 11 (31–33).
                if ($id === 34 && $round === 4 && $packet === 3) {
                    movementSame(str_contains($html, ">6</td><td class='xNum3'>4</td><td class='xNum3'>EO</td><td class='xNum3'>31-33</td>"), true, 'Howell 11 rendered packet 11');
                }
                $values[] = $html;
            }
            for ($first = 1; $first <= 36; $first += $packet) {
                $values[] = getfeuillesuivihowell($id, $packet, $first);
            }
        }
        $snapshots["$id/$packet"] = hash('sha256', serialize($values));
    }
}
$values = [htmlTableTypeTournois(), getnpaires(999)];
for ($board = 1; $board <= 36; $board++) {
    $etui = getetui($board);
    movementSame($etui['donneur'], ($board - 1) % 4 + 1, 'dealer cycle');
    movementSame(getinfoetui($board), $etui['info'], 'board information');
    $values[] = $etui;
    foreach ([1,2,3,4,5,6] as $packet) $values[] = liste_etuis($board, $packet);
}
$snapshots['boards/html/unknown'] = hash('sha256', serialize($values));
$fixture = __DIR__.'/fixtures/movements_reference.json';
$expected = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);
movementSame(array_keys($snapshots), array_keys($expected), 'reference coverage');
foreach ($snapshots as $key => $hash) movementSame($hash, $expected[$key], 'original output '.$key);
echo "$checks movement checks; ".count($snapshots)." reference cases: OK\n";
