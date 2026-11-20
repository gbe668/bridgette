<?php
// Real update and rank calculation under table locks, on simulated data only.
$source=file_get_contents(__DIR__.'/../appli/bridgette_bdd.php');
$start=strpos($source,'function _setRang(');
$end=strpos($source,"require_once __DIR__.'/libresultats.php';",$start);
eval(substr($source,$start,$end-$start));
$contratNJ='N J'; $db=null; $trace=[];
class UpdateStatement implements IteratorAggregate {
    function __construct(private $value) {}
    function fetchColumn() {return $this->value;}
    function getIterator(): Traversable {return new ArrayIterator($this->value);}
}
class UpdateDatabase {
    public $rows=[]; public $active=false; public $step=0;
    public $failAt=0; public $failLock=false; public $failUnlock=false;
    function query($sql) {
        global $trace,$tab_donnes;
        $trace[]=[$sql];
        if ($sql==="LOCK TABLES $tab_donnes WRITE;") {
            if ($this->failLock==='throw') throw new RuntimeException('Injected lock failure');
            if ($this->failLock) return false;
            $this->active=true; return true;
        }
        if ($sql==='UNLOCK TABLES;') {
            if ($this->failUnlock==='throw') throw new RuntimeException('Injected unlock failure');
            if ($this->failUnlock) return false;
            $this->active=false; return true;
        }
        throw new RuntimeException('Unexpected raw SQL (transactions are forbidden)');
    }
}
function connectBDD() {global $db,$trace; $trace[]=['connect']; return $db;}
function securityQuery($connection,$sql,$args) {
    global $db,$trace;
    if ($connection!==$db || !$db->active) throw new RuntimeException('Operation outside table lock');
    if (substr_count($sql,'?')!==count($args)) throw new RuntimeException('Binding count');
    $trace[]=[preg_replace('/\s+/',' ',trim($sql)),$args];
    if (++$db->step===$db->failAt) throw new RuntimeException('Injected SQL failure');
    if (str_starts_with($sql,'SELECT count(*)')) {
        $hweo=str_contains($sql,'hweo=1')?1:0; $count=0;
        foreach ($db->rows as $r) if ([$r['idtournoi'],$r['etui'],$r['ns'],$r['eo'],$r['hweo']]===array_merge($args,[$hweo])) $count++;
        return new UpdateStatement($count);
    }
    if (str_starts_with($sql,'SELECT id,')) {
        $rows=[];
        foreach ($db->rows as $r) if ([$r['idtournoi'],$r['etui'],$r['hweo']]===array_slice($args,0,3) && $r['contrat']!==$args[3]) $rows[]=$r;
        return new UpdateStatement($rows);
    }
    if (str_contains($sql,'SET contrat =')) {
        $hweo=str_contains($sql,'hweo=1')?1:0;
        foreach ($db->rows as &$r) if ([$r['idtournoi'],$r['etui'],$r['ns'],$r['eo'],$r['hweo']]===array_merge(array_slice($args,5),[$hweo])) {
            foreach (['contrat','jouepar','entame','resultat','points'] as $i=>$key) $r[$key]=$args[$i];
        }
    } elseif (str_contains($sql,"SET rang = '0'")) {
        $db->rows[$args[0]]['rang']='0'; $db->rows[$args[0]]['note']='50';
    } elseif (str_contains($sql,'SET rang = ?')) {
        $db->rows[$args[2]]['rang']=$args[0]; $db->rows[$args[2]]['note']=$args[1];
    } else throw new RuntimeException('Unhandled query');
    return new UpdateStatement(null);
}
require_once __DIR__.'/../appli/libdonnes.php';
require_once __DIR__.'/../appli/libdonnes.php';
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
function initialUpdate($kind) {
    global $db,$trace;
    $db=new UpdateDatabase(); $trace=[];
    $base=['id'=>1,'idtournoi'=>995,'etui'=>3,'ns'=>3,'eo'=>6,'hweo'=>0,'contrat'=>'4 P','jouepar'=>'S','entame'=>'AT','resultat'=>'0','points'=>420,'rang'=>0,'note'=>50];
    if ($kind!=='missing' && $kind!=='orphan') $db->rows[1]=$base;
    if ($kind!=='mitchell' && $kind!=='missing') $db->rows[2]=array_replace($base,['id'=>2,'ns'=>6,'eo'=>3,'hweo'=>1,'points'=>-420]);
    if ($kind==='duplicate') $db->rows[3]=array_replace($base,['id'=>3]);
    if ($kind==='duplicateInverse') $db->rows[3]=array_replace($db->rows[2],['id'=>3]);
    if ($kind==='ranking') {
        $db->rows[3]=array_replace($base,['id'=>3,'ns'=>1,'eo'=>2,'points'=>100]);
        $db->rows[4]=array_replace($base,['id'=>4,'ns'=>2,'eo'=>1,'hweo'=>1,'points'=>-100]);
    }
}
$checks=0;
function updateSame($actual,$expected,$label) {global $checks;$checks++;if($actual!==$expected)throw new RuntimeException($label.': '.var_export($actual,true));}
$actual=[];
foreach(['tst_','abc_'] as $club) {
    $tab_donnes=$club.'donnes';
    foreach(['mitchell','howell','missing','orphan','duplicate','duplicateInverse','ranking'] as $kind) {
        foreach([420,-100,0] as $points) {
            initialUpdate($kind);
            $before=$db->rows;
            $message=updateDonne(995,3,3,6,'3 SA','N','10C','-1',$points);
            updateSame($db->active,false,'lock released');
            updateSame($trace[1],["LOCK TABLES $tab_donnes WRITE;"],'correct club locked before reads');
            updateSame(end($trace),['UNLOCK TABLES;'],'unlocked after rank updates');
            if(in_array($kind,['missing','orphan','duplicate'],true)) {
                updateSame($db->rows,$before,'missing or ambiguous result unchanged');
                updateSame($message,'Enregistrement non trouvé.','missing message');
            } else {
                updateSame($db->rows[1]['points'],$points,'primary score');
                if($kind!=='mitchell' && $kind!=='duplicateInverse') {
                    updateSame($db->rows[2]['points'],-$points,'opposite score');
                    updateSame($message,'Deux enregistrements mis à jour.','Howell message');
                } else updateSame($message,'Un enregistrement mis à jour.','one result message');
            }
            // Compare historical results/messages/business SQL without changing the fixture.
            // Check the new synchronization independently above.
            $comparisonTrace=array_map(function($entry) use ($tab_donnes) {
                if ($entry===["LOCK TABLES $tab_donnes WRITE;"]) return ['START TRANSACTION;'];
                if ($entry===['UNLOCK TABLES;']) return ['COMMIT;'];
                return $entry;
            },$trace);
            $actual["$club/$kind/$points"]=hash('sha256',serialize([$message,$db->rows,$comparisonTrace]));
        }
    }
}
$fixture=__DIR__.'/fixtures/update_reference.json';
$expected=json_decode(file_get_contents($fixture),true,512,JSON_THROW_ON_ERROR);
if($actual!==$expected)throw new RuntimeException('Successful update behavior changed');
// Faults after each SQL step: unlock is required, but previous writes persist.
    foreach(array_merge(range(1,8),['lock','unlock','lockException','unlockException']) as $failure) {
        initialUpdate('howell'); $before=$db->rows;
        if($failure==='lock')$db->failLock=true;
        elseif($failure==='unlock')$db->failUnlock=true;
        elseif($failure==='lockException')$db->failLock='throw';
        elseif($failure==='unlockException')$db->failUnlock='throw';
        else $db->failAt=$failure;
        $caught=false;
        try {updateDonne(995,3,3,6,'3 SA','N','10C','-1',-100);} catch(Throwable $e){$caught=true;}
        updateSame($caught,true,'no false success');
        $lockFailure=str_starts_with((string)$failure,'lock');
        $unlockFailure=str_starts_with((string)$failure,'unlock');
        if(!$unlockFailure) updateSame($db->active,false,'lock released after SQL failure');
        updateSame(count(array_filter($trace,fn($r)=>$r===['UNLOCK TABLES;'])),$lockFailure?0:1,'unlock attempted exactly once');
        if($lockFailure || (is_int($failure) && $failure<=2)) updateSame($db->rows,$before,'no write before failure');
        else {
            updateSame($db->rows[1]['points'],-100,'first correction persists without rollback');
            updateSame($db->rows[2]['points'],is_int($failure) && $failure<=6?-420:100,'inverse write depends on failure stage');
        }
    }
echo "$checks update assertions; ".count($actual)." reference scenarios: OK\n";
