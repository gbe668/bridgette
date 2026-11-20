<?php
// Exécute start_howell avec une base simulée : aucune connexion MySQL.
require __DIR__.'/../appli/libmouvements.php';
require __DIR__.'/../appli/lib63.php';
$source = file_get_contents(__DIR__.'/../appli/bridgette_bdd.php');
$start = strpos($source, 'function start_howell(');
$end = strpos($source, "\nfunction ", $start+1);
eval(substr($source, $start, $end-$start));
$start = strpos($source, 'function initTournoi(');
$end = strpos($source, "\nfunction ", $start+1);
eval(substr($source, $start, $end-$start));
$writes = []; $tournoi = [];
function readTournoi($id) { global $tournoi; return $tournoi; }
function connectBDD() { return new stdClass(); }
function securityQuery($db, $sql, $args) { global $writes; $writes[]=[$sql,$args]; }
$tab_tournois='tst_tournois'; $tab_connexions='tst_connexions';
$st_phase_jeu=33; $cnx_ko=1; $max_tables=24;
$parametres=['dureedonne'=>7,'dureediagrammes'=>0,'dureeinitiale'=>2];
$checks=0;
function verifyPackets($ok,$message) { global $checks; $checks++; if(!$ok) throw new RuntimeException($message); }
foreach ([4=>6,5=>4,6=>5,7=>7,8=>7,32=>9,33=>9,34=>11,35=>11] as $type=>$count) {
    $tt=gettypetournoi($type);
    verifyPackets($tt['npaquets']===$count,'catalogue '.$type);
    verifyPackets($tt['maxpaquet']===intdiv(36,$count),'limite '.$type);
    $writes=[]; initTournoi(7,$tt['npaires'],0);
    verifyPackets($writes[0][1][5]===$count*$tt['paquet'],'stock en préparation '.$type);
    $maxPacket=0;
    for($round=1;$round<=$tt['maxpos'];$round++) {
        for($pair=1;$pair<=$tt['npaires'];$pair++) {
            $p=getposhowell($type,$pair,$round,1);
            $maxPacket=max($maxPacket,$p['last']+1);
        }
    }
    verifyPackets($maxPacket===$count,'stock du mouvement '.$type);
    foreach(range(1,$tt['maxpaquet']) as $size) {
        $tournoi=$tt; $tournoi['idtype']=$type; $tournoi['pairesNS']=$tt['npaires'];
        $writes=[]; start_howell(7,(string)$size);
        $values=$writes[0][1];
        verifyPackets($values[3]===$count*$size,'stock au démarrage '.$type);
        verifyPackets($values[4]===$tt['npositions']*$size,'compteur '.$type);
        verifyPackets($values[3]<=36,'36 étuis maximum');
        verifyPackets(count($writes)===1+$max_tables,'initialisation compteurs');
    }
    foreach([0,1.5,$tt['maxpaquet']+1] as $invalid) {
        $writes=[];
        try { start_howell(7,$invalid); throw new RuntimeException('taille invalide acceptée'); }
        catch(InvalidArgumentException $e) { verifyPackets($writes===[],'refus avant écriture'); }
    }
}
// Régression tour 4 : paire 6 EO, paire 5 NS, étuis 31–33.
$p=getposhowell(34,6,4,3);
verifyPackets($p['last']+1===31 && $p['adversaire']==5 && $p['NS']===2,'paire 6 tour 4');
$Paquet2Play=[0,0,0]; $Donnes2Play=[31,32,33];
verifyPackets(findFirst2PlayAfter(31,6)===31,'prochaine donne 31');
echo "$checks contrôles Howell paquets : OK\n";
