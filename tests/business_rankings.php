<?php
// Original HTML and read order, with a simulated database and real formatters.
$source = file_get_contents(__DIR__.'/../appli/bridgette_bdd.php');
$start = strpos($source, 'function strdatet(');
eval(substr($source, $start, strpos($source, 'function getSymboleCouleur(', $start) - $start));
require_once __DIR__.'/../appli/libcalculs.php';
$strmois = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$t_mitchell = 1; $t_howell = 2; $parametres = ['maxw'=>700];
$reads = []; $nsRows = []; $eoRows = []; $generalRows = []; $tournoi = [];
function connectBDD() {global $reads; $reads[] = ['connect']; return new stdClass();}
function _readTournoi($db, $idt) {global $reads, $tournoi; $reads[] = ['tournoi',$idt]; return $tournoi;}
function _getJoueur($db, $id) {
    global $reads;
    $reads[] = ['joueur',$id];
    return ['joueur'=>'J'.$id, 'nomcomplet'=>"Élodie O'Neil & Joueur ".$id];
}
function securityQuery($db, $sql, $args) {
    global $reads, $nsRows, $eoRows, $generalRows;
    if (!str_starts_with($sql, 'SELECT ')) throw new RuntimeException('Unexpected write');
    $reads[] = [$sql,$args];
    if (str_contains($sql, 'UNION')) return $generalRows;
    return str_contains($sql, 'pairesEO') ? $eoRows : $nsRows;
}
require_once __DIR__.'/../appli/libresultats.php';
require_once __DIR__.'/../appli/libresultats.php';
set_error_handler(function ($severity, $message, $file, $line) {throw new ErrorException($message, 0, $severity, $file, $line);});
$cases = ['empty'=>[], 'one'=>[50], 'distinct'=>[70.25,60.5,40], 'ties'=>[60,60,40], 'allties'=>['50',50,'50'], 'negative'=>[12.9,0,-12.9]];
$actual = []; $checks = 0;
foreach (['tst_','abc_'] as $club) {
    $tab_pairesNS = $club.'pairesNS'; $tab_pairesEO = $club.'pairesEO'; $tab_tournois = $club.'tournois';
    foreach ($cases as $name=>$scores) {
        foreach ([1,2] as $genre) {
            $nsRows = []; $eoRows = []; $generalRows = [];
            foreach ($scores as $i=>$score) {
                $nsRows[] = ['num'=>$i+1,'idj1'=>$i*2+1,'idj3'=>$i*2+2,'noteg'=>$score];
                if ($genre === 1) $eoRows[] = ['num'=>$i+1,'idj2'=>$i*2+11,'idj4'=>$i*2+12,'noteg'=>$score];
                $generalRows[] = ['num'=>$i+1,'j1'=>$i*2+1,'j2'=>$i*2+2,'noteg'=>$score,'sens'=>'NS'];
                if ($genre === 1) $generalRows[] = ['num'=>$i+1,'j1'=>$i*2+11,'j2'=>$i*2+12,'noteg'=>$score,'sens'=>'EO'];
            }
            $tournoi = ['genre'=>$genre,'pairesNS'=>count($nsRows),'pairesEO'=>count($eoRows),'njouees'=>15,'ndonnes'=>30,'tournoi'=>'2026-10-08'];
            foreach ([0,700,701,1200] as $width) {
                foreach ([false,true] as $general) {
                    $reads = [];
                    $html = htmlDisplayTournoi(997, $width, $general);
                    $actual["$club/$name/$genre/$width/".(int)$general] = hash('sha256', serialize([$html,$reads]));
                    if (function_exists('readDisplayTournoi')) {
                        $data = readDisplayTournoi(997, $general);
                        $before = count($reads);
                        if (renderDisplayTournoi($data,$width,$general) !== $html || count($reads) !== $before) throw new RuntimeException('Percentage renderer read DB or changed HTML');
                        $checks++;
                    }
                }
            }
            $reads = [];
            $html = htmlDisplayTournoiIMP(997);
            $actual["$club/$name/$genre/IMP"] = hash('sha256', serialize([$html,$reads]));
            if (function_exists('readDisplayTournoiIMP')) {
                $data = readDisplayTournoiIMP(997);
                $before = count($reads);
                if (renderDisplayTournoiIMP($data) !== $html || count($reads) !== $before) throw new RuntimeException('IMP renderer read DB or changed HTML');
                $checks++;
            }
        }
    }
}
$fixture = __DIR__.'/fixtures/rankings_reference.json';
$expected = json_decode(file_get_contents($fixture), true, 512, JSON_THROW_ON_ERROR);
if ($actual !== $expected) {
    foreach ($actual as $key=>$value) if (($expected[$key] ?? null) !== $value) echo "Mismatch: $key\n";
    throw new RuntimeException('Rankings changed');
}
echo count($actual)." ranking reference cases; $checks renderings without DB: OK\n";
