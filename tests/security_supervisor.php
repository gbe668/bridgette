<?php
require __DIR__.'/../appli/supervisor_lib.php';
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
function denied(callable $fn) { try { $fn(); } catch (RuntimeException $e) { return; } throw new RuntimeException('Expected denial'); }
$c = ['enabled' => true, 'expires_at' => time()+600, 'password_hash' => password_hash('test-only-long-password', PASSWORD_DEFAULT), 'host' => 'localhost', 'clubs' => ['tst_' => 'Test', 'sol_' => 'Standalone']];
check(supervisorValidateConfig($c) === $c, 'valid config');
check(supervisorActivationName('Example.ORG') === 'supervisor_access_example.org.php', 'canonical domain file');
check(supervisorValidateConfig($c, 'LOCALHOST') === $c, 'matching server');
denied(function () use ($c) { supervisorValidateConfig($c, 'other.example.org'); });
foreach (['', '../secret', 'https://example.org', 'example.org:443', 'example..org', '-bad.org', 'bad-.org', ['localhost']] as $badHost) {
    denied(function () use ($badHost) { supervisorHost($badHost); });
}
foreach (['enabled' => false, 'expires_at' => time()-1, 'password_hash' => 'plaintext', 'host' => '../secret', 'clubs' => ['../' => 'bad']] as $key => $value) {
    denied(function () use ($c, $key, $value) { supervisorValidateConfig(array_replace($c, [$key => $value])); });
}
denied(function () use ($c) { supervisorTarget($c, 'other_', 'install'); });
denied(function () use ($c) { supervisorTarget($c, 'tst_', 'sql'); });
denied(function () use ($c) { supervisorPatch($c); });
denied(function () { supervisorAdmin('admin', 'short'); });
denied(function () { supervisorAdmin('zorglub', 'long-enough-password'); });
foreach (['', 'a', 'ab', 'ab_', '___', 'a_b_', '../abc', 'abc-', 'abc', 'a12', 'abcd_', 'abc__', 'a_bc', str_repeat('a', 49)] as $bad) {
    check(!bridgetteValidClubPrefix($bad), 'invalid prefix');
    denied(function () use ($c, $bad) { supervisorValidateConfig(array_replace($c, ['clubs' => [$bad => 'Invalid']])); });
    denied(function () use ($c, $bad) { supervisorTarget($c, $bad, 'install'); });
}
foreach (['abc_', 'a12_', '123_', 'tst_', 'AB9_'] as $good) check(bridgetteValidClubPrefix($good), 'valid prefix');
check(count(supervisorTarget($c, 'tst_', 'droptables')) === 9, 'exact table set');
check(supervisorConfirmation('tst_', 'gc') === supervisorConfirmation('sol_', 'gc'), 'global GC');
class SupervisorStatement {
    public $db; public $sql;
    function __construct($db, $sql) { $this->db=$db; $this->sql=$sql; }
    function execute($args) {
        $this->db->queries[] = [$this->sql, $args];
        if ($this->db->failInsert && str_starts_with($this->sql, 'INSERT INTO')) throw new RuntimeException('Insert failed');
        if ($this->db->falseInsert && str_starts_with($this->sql, 'INSERT INTO')) return false;
        return true;
    }
    function fetchColumn() { return str_contains($this->sql, 'WHERE pseudo = ?') && $this->db->duplicate ? 1 : 0; }
    function rowCount() { return str_starts_with($this->sql, 'INSERT INTO') ? ($this->db->missingRow ? 0 : 1) : 3; }
}
class SupervisorDB {
    public $queries=[], $duplicate=false, $failInsert=false, $failLock=false, $falseInsert=false, $missingRow=false, $failUnlock=false;
    function exec($sql) {
        $this->queries[] = [$sql, []];
        return ($this->failLock && str_starts_with($sql, 'LOCK TABLES')) || ($this->failUnlock && $sql === 'UNLOCK TABLES') ? false : 0;
    }
    function query($sql) { $this->queries[] = [$sql, []]; return new SupervisorStatement($this, $sql); }
    function prepare($sql) { return new SupervisorStatement($this, $sql); }
}
$db = new SupervisorDB();
supervisorRun($db, $c, 'tst_', 'droptables');
check(count($db->queries) === 9, 'drop count');
foreach ($db->queries as [$sql]) check(preg_match('/\ADROP TABLE IF EXISTS `tst_[A-Za-z]+`\z/', $sql), 'drop isolation');
$db = new SupervisorDB();
supervisorRun($db, $c, 'sol_', 'gc');
check(count($db->queries) === 1 && abs($db->queries[0][1][0] - (time()-8640000)) < 2, 'GC boundary');
$db = new SupervisorDB();
$admin = supervisorAdmin('Initial', 'A long & safe password');
supervisorRun($db, $c, 'tst_', 'install', $admin);
$creates = array_filter($db->queries, static function($q) { return stripos($q[0], 'CREATE TABLE') === 0; });
check(count($creates) === 9, 'nine creates');
$admins = array_values(array_filter($db->queries, static function($q) { return strpos($q[0], 'INSERT INTO `tst_directeurs`') === 0; }));
check(count($admins) === 1 && $admins[0][1] === [$admin[0], $admin[1], 'admin'] && bridgettePasswordVerify('A long & safe password', $admin[1]), 'chosen modern admin only');
check(count(array_filter($db->queries, static function($q) { return stripos($q[0], 'Insert into tst_joueurs') === 0; })) === 64, 'guest seed');
check(!function_exists('init_tab_directeurs'), 'Predefined administrator seeding removed');
check(supervisorTarget($c, 'sol_', 'addadmin') === ['directeurs'=>'sol_directeurs'], 'Admin operation targets one club table');
$db = new SupervisorDB();
supervisorRun($db, $c, 'sol_', 'addadmin', $admin);
check($db->queries[0][0] === 'LOCK TABLES `sol_directeurs` WRITE' && end($db->queries)[0] === 'UNLOCK TABLES', 'Club table locked and released');
$insert = array_values(array_filter($db->queries, static function($q) { return str_starts_with($q[0], 'INSERT INTO'); }));
check(count($insert) === 1 && $insert[0][1] === [$admin[0], $admin[1], 'admin'], 'Chosen hashed account only');
check(!str_contains($insert[0][0], $admin[0]), 'Pseudo bound, not interpolated');
foreach (['duplicate', 'failInsert', 'failLock', 'falseInsert', 'missingRow', 'failUnlock'] as $failure) {
    $db = new SupervisorDB(); $db->$failure = true;
    denied(function () use ($db, $c, $admin) { supervisorRun($db, $c, 'tst_', 'addadmin', $admin); });
    if ($failure !== 'failLock') check(end($db->queries)[0] === 'UNLOCK TABLES', 'Error releases table lock');
    if (in_array($failure, ['duplicate', 'failLock'], true)) check(!array_filter($db->queries, static function($q) { return str_starts_with($q[0], 'INSERT INTO'); }), 'No insert after duplicate/failed lock');
}
echo "Supervisor: configuration, club isolation, confirmations, GC and initial installation OK (simulated DB).\n";
