<?php
// Execute the real insert workflow on a stateful simulated database, never MySQL.
$st_phase_jeu = 33; $st_phase_fini = 34; $cnx_ok = 2; $t_howell = 2; $min_type_affimp = 5;
$parametres = ['dureedonne'=>7, 'affimp'=>0];
$db = null; $trace = []; $tournoi = [];
class InsertStatement {
    function __construct(private $value) {}
    function fetchColumn() {return $this->value;}
    function fetch($mode) {return $this->value;}
}
class InsertDatabase {
    public $donnes = []; public $connexions = []; public $locked = false;
    function query($sql) {
        global $trace;
        $trace[] = [preg_replace('/\s+/', ' ', trim($sql))];
        if (str_starts_with($sql,'LOCK TABLES')) $this->locked = true;
        elseif ($sql === 'UNLOCK TABLES;') $this->locked = false;
        else throw new RuntimeException('Unexpected raw query');
    }
}
function readTournoi($id) {global $tournoi,$trace; $trace[]=['readTournoi',$id]; return $tournoi;}
function connectBDD() {global $db,$trace; $trace[]=['connect']; return $db;}
function securityQuery($connection, $sql, $args) {
    global $db,$trace,$tournoi;
    if ($connection !== $db || !$db->locked) throw new RuntimeException('Every write/read must use the locked connection');
    if (substr_count($sql,'?') !== count($args)) throw new RuntimeException('Binding count');
    $normalized = preg_replace('/\s+/', ' ', trim($sql));
    $logged = $args;
    if (str_contains($sql,'endofseq')) {
        $duration = $tournoi['paquet'] * 7 * 60;
        if (abs($args[1] - time() - $duration) > 3) throw new RuntimeException('Round duration changed');
        $logged[1] = 'clock+'.$duration;
    }
    $trace[] = [$normalized,$logged];
    if (str_starts_with($sql,'SELECT count(*)')) {
        $count = 0;
        foreach ($db->donnes as $row) if (array_slice($row,0,4) === $args) $count++;
        return new InsertStatement($count);
    }
    if (str_starts_with($sql,'INSERT into')) {$db->donnes[]=$args; return new InsertStatement(null);}
    if (str_starts_with($sql,'SELECT *')) return new InsertStatement($db->connexions[$args[0]]);
    if (str_starts_with($sql,'SELECT MIN')) {
        $positions = [];
        foreach ($db->connexions as $id=>$row) if ($id <= $args[0]) $positions[]=$row['rdy'];
        return new InsertStatement(min($positions));
    }
    if (str_contains($sql,'SET stconnexion=')) {
        $id = $args[5];
        foreach (['stconnexion','numEO','numdonne','cpt','rdy'] as $i=>$key) $db->connexions[$id][$key]=$args[$i];
    } elseif (str_contains($sql,'SET eonumNS=')) {
        foreach (['eonumNS','eodonne','eocpt'] as $i=>$key) $db->connexions[$args[3]][$key]=$args[$i];
    } elseif (str_contains($sql,'SET eocpt =')) $db->connexions[$args[1]]['eocpt']=$args[0];
    elseif (str_contains($sql,'SET etat =')) $tournoi['etat']=$args[0];
    elseif (str_contains($sql,'SET notour =')) {$tournoi['notour']=$args[0]; $tournoi['endofseq']=$logged[1];}
    else throw new RuntimeException('Unhandled SQL: '.$sql);
    return new InsertStatement(null);
}
function _setRang($idt,$dd,$connection,$hweo,$table) {
    global $db,$trace;
    if ($connection !== $db || !$db->locked) throw new RuntimeException('Ranking outside lock');
    $trace[]=['rang',$idt,$dd,$hweo,$table];
}
function setTournoi($id) {recordFinal('percent',$id);}
function setTournoiIMP($id) {recordFinal('IMP',$id);}
function recordFinal($kind,$id) {
    global $trace,$db;
    if ($db->locked) throw new RuntimeException('Final ranking before unlocking');
    $trace[]=['final',$kind,$id];
}
require_once __DIR__.'/../appli/libdonnes.php';
require_once __DIR__.'/../appli/libdonnes.php';
set_error_handler(function ($severity,$message,$file,$line) {throw new ErrorException($message,0,$severity,$file,$line);});
$actual = []; $checks = 0;
function insertSame($actual,$expected,$label) {
    global $checks; $checks++;
    if ($actual !== $expected) throw new RuntimeException($label.': '.var_export($actual,true));
}
foreach (['tst_','abc_'] as $club) {
    $tab_donnes=$club.'donnes'; $tab_connexions=$club.'connexions'; $tab_tournois=$club.'tournois';
    foreach ([1,2] as $genre) {
        foreach (['middle','packet','round','finish','finishIMP','duplicate','closed','gueridon','gueridonMiddle','gueridonOther','asymmetric'] as $case) {
            if ($genre===2 && str_starts_with($case,'gueridon')) continue;
            $db = new InsertDatabase(); $trace=[];
            $tournoi=['etat'=>33,'genre'=>$genre,'paquet'=>3,'gueridon'=>0,'relais'=>0,'pairesNS'=>$genre===2?6:8,'pairesEO'=>$genre===2?0:8,'npositions'=>8,'notour'=>1,'idtype'=>10];
            $ns=1; $eo=2; $dd=3; $points=420;
            $finish = str_starts_with($case,'finish');
            $cpt = $case==='middle' ? 0 : 2;
            if ($finish) {$cpt=23; $tournoi['notour']=8;}
            if ($case==='closed') $tournoi['etat']=34;
            if ($case==='finishIMP') $tournoi['idtype']=4;
            $parametres['affimp']=$case==='finishIMP'?1:0;
            if (str_starts_with($case,'gueridon')) {
                $tournoi['gueridon']=1; $tournoi['relais']=8; $tournoi['pairesNS']=7; $tournoi['pairesEO']=8; $tournoi['idtype']=15;
                $ns=$case==='gueridonOther'?6:7; $eo=1;
                if ($case==='gueridonMiddle') $cpt=0;
            }
            for ($i=1;$i<=8;$i++) $db->connexions[$i]=['cpt'=>$cpt,'rdy'=>$finish?9:($case==='round'?2:1),'eocpt'=>$cpt];
            $db->connexions[$ns]['rdy']=$finish?8:1;
            if ($genre===2) $db->connexions[$eo]['rdy']=$finish?8:1;
            if ($case==='asymmetric') $db->connexions[$eo]['cpt']=0;
            if ($case==='duplicate') $db->donnes[]=[995,$dd,$ns,$eo,'4 P','S','AT','0',$points,0];
            $beforeDonnes=$db->donnes; $beforeConnections=$db->connexions;
            $result=insertDonne(995,$dd,$ns,$eo,'4 P','S','AT','0',$points);
            insertSame($db->locked,false,'lock released');
            if (in_array($case,['closed','duplicate'],true)) {
                insertSame($db->donnes,$beforeDonnes,'no result write');
                insertSame($db->connexions,$beforeConnections,'no counter write');
                insertSame($result['ok'],$case==='closed'?-1:0,'rejected result');
            } else {
                insertSame(count($db->donnes),$genre===2?2:1,'result count');
                insertSame($db->connexions[$ns]['cpt'],$cpt+1,'NS count');
                insertSame($db->connexions[$ns]['rdy'],($finish?8:1)+(($cpt+1)%3===0?1:0),'NS position boundary');
                insertSame($result['ok'],1,'accepted result');
                if ($finish) insertSame($tournoi['etat'],34,'finished state');
                elseif ($case==='round') insertSame($tournoi['notour'],2,'new global round');
                else insertSame($tournoi['notour'],1,'wait for other pairs');
                if ($genre===2) {
                    insertSame($db->donnes[1][8],-$points,'opposite points');
                    insertSame(array_slice($db->donnes[1],2,2),[$eo,$ns],'opposite pairs');
                    insertSame($db->donnes[1][9],1,'inverse orientation');
                    insertSame($db->connexions[$eo]['cpt'],($case==='asymmetric'?0:$cpt)+1,'Howell EO count');
                } else insertSame($db->connexions[$eo]['eocpt'],$cpt+1+($case==='gueridon'?3:0),'EO count including relay');
                $snapshot=[$db->donnes,$db->connexions,$tournoi];
                $replay=insertDonne(995,$dd,$ns,$eo,'4 P','S','AT','0',$points);
                insertSame([$db->donnes,$db->connexions,$tournoi],$snapshot,'replay must not advance');
                insertSame($replay['ok'],$finish?-1:0,'replay status');
            }
            $actual["$club/$genre/$case"]=hash('sha256',serialize([$result,$db->donnes,$db->connexions,$tournoi,$trace]));
        }
    }
}
$fixture=__DIR__.'/fixtures/insert_reference.json';
$expected=json_decode(file_get_contents($fixture),true,512,JSON_THROW_ON_ERROR);
if ($expected!==$actual) {
    foreach ($actual as $key=>$value) if (($expected[$key]??null)!==$value) echo "Mismatch: $key\n";
    throw new RuntimeException('Insert behavior changed');
}
echo "$checks insert assertions; ".count($actual)." reference scenarios: OK\n";
