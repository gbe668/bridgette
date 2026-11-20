<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../appli/session_maintenance.php';
function gcCheck($ok, $label) { if (!$ok) throw new RuntimeException($label); }
class MonthlyGcDb {
    public array $rows = [];
    public bool $busy = false;
    public bool $fail = false;
    public bool $raced = false;
    public int $deletes = 0;
    public int $releases = 0;
    function prepare($sql) { return new MonthlyGcStatement($this, $sql); }
}
class MonthlyGcStatement {
    private $db; private $sql; private $value; private int $count = 0;
    function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    function execute($args) {
        $db = $this->db;
        if (str_starts_with($this->sql, 'SELECT data')) $this->value = $db->rows[$args[0]]['data'] ?? false;
        elseif (str_starts_with($this->sql, 'SELECT GET_LOCK')) {
            $this->value = $db->busy ? 0 : 1;
            if ($db->raced) $db->rows['gc_monthly'] = ['access' => 0, 'data' => '2026-09'];
        } elseif (str_starts_with($this->sql, 'SELECT RELEASE_LOCK')) $db->releases++;
        elseif (str_starts_with($this->sql, 'DELETE')) {
            if ($db->fail) throw new RuntimeException('Simulated deletion failure');
            $db->deletes++;
            foreach ($db->rows as $id => $row) {
                if ($row['access'] < $args[0] && $id !== $args[1]) { unset($db->rows[$id]); $this->count++; }
            }
        } elseif (str_starts_with($this->sql, 'REPLACE')) $db->rows[$args[0]] = ['access' => $args[1], 'data' => $args[2]];
        else throw new RuntimeException('Unexpected SQL');
        return true;
    }
    function fetchColumn() { return $this->value; }
    function rowCount() { return $this->count; }
}
$now = strtotime('2026-09-17 12:00:00 UTC');
$db = new MonthlyGcDb();
$db->rows = [
    'old_club_one' => ['access' => $now - 101 * 86400, 'data' => 'one'],
    'old_club_two' => ['access' => $now - 101 * 86400, 'data' => 'two'],
    'boundary' => ['access' => $now - 100 * 86400, 'data' => 'keep'],
    'recent' => ['access' => $now - 86400, 'data' => 'keep'],
    'apk_ticket' => ['access' => $now - 60, 'data' => 'keep'],
];
gcCheck(bridgetteMonthlySessionGc($db, 'shared', $now) === 2, 'First monthly purge spans clubs');
gcCheck(isset($db->rows['boundary'], $db->rows['recent'], $db->rows['apk_ticket']), 'Boundary and current sessions/ticket preserved');
gcCheck($db->rows['gc_monthly']['data'] === '2026-09', 'Successful month recorded');
gcCheck(bridgetteMonthlySessionGc($db, 'shared', $now + 86400) === null && $db->deletes === 1, 'Repeated visits do not purge');
gcCheck(bridgetteMonthlySessionGc($db, 'shared', strtotime('2026-10-01 UTC')) === 1 && $db->deletes === 2, 'Next calendar month purges');
$other = new MonthlyGcDb();
gcCheck(bridgetteMonthlySessionGc($other, 'other-server', $now) === 0, 'Independent database schedule');
$busy = new MonthlyGcDb(); $busy->busy = true;
gcCheck(bridgetteMonthlySessionGc($busy, 'shared', $now) === null && !$busy->rows && $busy->deletes === 0, 'Busy lock skips without marker');
$race = new MonthlyGcDb(); $race->raced = true;
gcCheck(bridgetteMonthlySessionGc($race, 'shared', $now) === null && $race->deletes === 0 && $race->releases === 1, 'Concurrent completion rechecked');
$failure = new MonthlyGcDb(); $failure->fail = true;
try { bridgetteMonthlySessionGc($failure, 'shared', $now); throw new LogicException('Expected failure'); } catch (RuntimeException $expected) { }
gcCheck(!$failure->rows && $failure->releases === 1, 'Failure releases lock and does not record success');
$failure->fail = false;
gcCheck(bridgetteMonthlySessionGc($failure, 'shared', $now) === 0, 'Failed purge retries');
echo "Monthly session GC: schedule, retention boundary, clubs, databases, concurrency, failure and retry OK (simulated DB).\n";
