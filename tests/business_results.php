<?php
// Real rendering with recorded database reads only; no application bootstrap.
$source = file_get_contents(__DIR__.'/../appli/bridgette_bdd.php');
$helpers = substr($source, strpos($source, 'function getSymboleCouleur('));
$helpers = substr($helpers, 0, strpos($helpers, '// routines sessions'));
eval($helpers);
$t_unknown = 0; $t_mitchell = 1; $t_howell = 2;
$st_position = [0=>'???',1=>'Nord',2=>'Est',3=>'Sud',4=>'Ouest'];
$st_vulnerable = [0=>'Personne vulnérable',1=>'Nord-Sud vulnerables',2=>'Est-Ouest vulnerables',3=>'Tous vulnérables'];
require __DIR__.'/../appli/libmouvements.php';
$contratNJ = 'N J';
$reads = []; $rows = []; $genre = 1; $diagrams = [];
function connectBDD() { global $reads; $reads[] = ['connect']; return new stdClass(); }
function securityQuery($db, $sql, $args) {
    global $reads, $rows;
    if (!preg_match('/^SELECT /', $sql)) throw new RuntimeException('Unexpected write');
    $reads[] = [$sql, $args];
    return $rows;
}
function _readTournoi($db, $id) { global $genre, $reads; $reads[] = ['tournoi', $id]; return ['genre'=>$genre]; }
function existeDiagramme($id, $board) {
    global $reads, $diagrams;
    $reads[] = ['diagramme', $id, $board];
    return $diagrams[$board] ?? [null, null];
}
require_once __DIR__.'/../appli/libresultats.php';
require_once __DIR__.'/../appli/libresultats.php';
$warnings = [];
set_error_handler(function ($severity, $message) {
    global $warnings;
    $warnings[] = $message;
    return true;
});
$samples = [
    ['ns'=>1,'eo'=>2,'etui'=>1,'contrat'=>'4 P','jouepar'=>'S','entame'=>'AT','resultat'=>'0','points'=>420,'note'=>66.666],
    ['ns'=>2,'eo'=>1,'etui'=>2,'contrat'=>'3 SA X','jouepar'=>'E','entame'=>'10C','resultat'=>'-2','points'=>-500,'note'=>33.333],
    ['ns'=>3,'eo'=>4,'etui'=>3,'contrat'=>'P G','jouepar'=>'-','entame'=>'-','resultat'=>'-','points'=>0,'note'=>50],
    ['ns'=>4,'eo'=>3,'etui'=>4,'contrat'=>'N J','jouepar'=>'-','entame'=>'-','resultat'=>'-','points'=>0,'note'=>0],
    ['ns'=>5,'eo'=>6,'etui'=>5,'contrat'=>'M G','jouepar'=>'-','entame'=>'-','resultat'=>'-','points'=>0,'note'=>60],
];
$cases = ['empty'=>[], 'single'=>[$samples[0]], 'mixed'=>$samples, 'unplayed'=>[$samples[3]], 'reversed'=>array_reverse($samples)];
$actual = [];
foreach (['tst_', 'abc_'] as $club) {
    $tab_donnes = $club.'donnes';
    foreach (['images/', '/bridgette/app1/images/'] as $relimg) {
        foreach ($cases as $name => $rows) {
            foreach ([1,2] as $genre) {
                foreach (['points','pointsIMP','ns'] as $order) {
                    $reads = []; $warnings = [];
                    $html = htmlResultatDonne(996, 1, 1, 2, $order);
                    if ($order === 'pointsIMP') {
                        if ($warnings) throw new RuntimeException('IMP rendering must not produce warnings');
                        preg_match_all('/<tr><td.*?<\/tr>/s', $html, $matches);
                        foreach ($rows as $index=>$row) {
                            $note = $row['contrat'] === 'N J' ? '-' : (string)intval($row['note']);
                            if (!preg_match('/<td class=\'xres(?: pairens)?\'>'.preg_quote($note, '/').'<\/td><\/tr>$/', $matches[0][$index])) {
                                throw new RuntimeException('Incorrect displayed IMP value');
                            }
                        }
                    }
                    $key = "$club/$relimg/$name/$genre/$order";
                    $actual[$key] = hash('sha256', serialize([$html, $reads, $warnings]));
                    $data = readResultatDonne(996, 1, $order);
                    $before = count($reads);
                    $warnings = [];
                    if (renderResultatDonne($data, 1, 1, 2, $order) !== $html || count($reads) !== $before) {
                        throw new RuntimeException('Donne renderer must match without database reads');
                    }
                }
            }
            foreach ([[], [1=>['deal','dds'],3=>['','dds'],4=>[null,'dds']]] as $diagrams) {
                $reads = []; $warnings = [];
                $html = htmlResultatPaquet(997, 6, 3);
                $key = "$club/$relimg/$name/packet/".count($diagrams);
                $actual[$key] = hash('sha256', serialize([$html, $reads, $warnings]));
                $data = readResultatPaquet(997, 6, 3);
                $before = count($reads);
                if (renderResultatPaquet($data) !== $html || count($reads) !== $before) {
                    throw new RuntimeException('Paquet renderer must match without database reads');
                }
            }
        }
    }
}
$fixture = __DIR__.'/fixtures/results_reference.json';
$expected = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);
if ($actual !== $expected) {
    foreach ($actual as $key => $value) if (($expected[$key] ?? null) !== $value) echo "Mismatch: $key\n";
    throw new RuntimeException('Original rendering, reads or warnings changed');
}
echo count($actual)." result reference cases: OK\n";
