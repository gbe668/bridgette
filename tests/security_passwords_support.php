<?php
if (PHP_SAPI !== 'cli' && !defined('PASSWORD_TEST_FIXTURE')) { http_response_code(403); exit; }
if (!class_exists('PDO')) { class PDO { const FETCH_ASSOC = 2; } }
class PasswordTestDB {
    public $tables = [], $rows = [], $race = null, $deviceRace = null;
    function prepare($sql) { return new PasswordTestQuery($this, $sql); }
}
class PasswordTestQuery {
    private $result = [], $affected = 0;
    function __construct(private $db, private $sql) {}
    function execute($a) {
        $db = $this->db; $s = $this->sql;
        if (preg_match('/^SELECT \* FROM `([^`]+)` WHERE id = \? AND datesupp = 0/', $s, $m)) {
            foreach ($db->tables[$m[1]] ?? [] as $row) if ($row['id'] === $a[0] && (int)$row['datesupp'] === 0) $this->result[] = $row;
        } elseif (preg_match('/^SELECT id FROM `([^`]+)` WHERE id = \? AND datesupp = 0/', $s, $m)) {
            foreach ($db->tables[$m[1]] ?? [] as $row) if ($row['id'] === $a[0] && (int)$row['datesupp'] === 0) $this->result[] = ['id'=>$row['id']];
        } elseif ($s === 'SELECT data FROM sessions WHERE id = ?') {
            if (isset($db->rows[$a[0]])) $this->result[] = $db->rows[$a[0]];
        } elseif (str_starts_with($s, 'UPDATE sessions SET access = ?')) {
            if ($db->deviceRace === 'delete') unset($db->rows[$a[2]]);
            if ($db->deviceRace === 'refresh') $db->rows[$a[2]]['data'] = 'concurrent';
            if ($db->deviceRace === 'fail') throw new RuntimeException('Simulated SQL failure');
            if (isset($db->rows[$a[2]]) && $db->rows[$a[2]]['data'] === $a[3]) {
                $db->rows[$a[2]] = ['access'=>$a[0], 'data'=>$a[1]]; $this->affected = 1;
            }
        } elseif (in_array($s, ['DELETE FROM sessions WHERE id = ? AND data = ?', 'DELETE FROM sessions WHERE id = ? AND BINARY data = ?'], true)) {
            if (isset($db->rows[$a[0]]) && $db->rows[$a[0]]['data'] === $a[1]) {
                unset($db->rows[$a[0]]); $this->affected = 1;
            }
        } elseif (str_contains($s, "LEFT(id, 4) = 'adr_'")) {
            foreach ($db->rows as $key=>$row) if (str_starts_with($key,'adr_') && $row['access'] < $a[0]) unset($db->rows[$key]);
        } elseif (preg_match('/^SELECT \* FROM `([^`]+)` WHERE (?:`?(email|pseudo)`? = \?|datesupp = 0 AND email = \?)/', $s, $m)) {
            $col = $m[2] ?? 'email';
            foreach ($db->tables[$m[1]] ?? [] as $row) if ($row[$col] === $a[0] && (!str_contains($s, 'datesupp') || (int)$row['datesupp'] === 0)) $this->result[] = $row;
        } elseif (preg_match('/^UPDATE `([^`]+)` SET password = \? WHERE id = \? AND BINARY password = \?/', $s, $m)) {
            if ($db->race) { $db->tables[$m[1]][0]['password'] = $db->race; }
            foreach ($db->tables[$m[1]] as &$row) {
                if ($row['id'] !== $a[1] || $row['password'] !== $a[2]) continue;
                if (str_contains($s, 'datesupp') && (int)$row['datesupp'] !== 0) continue;
                if (isset($a[3]) && $row[str_contains($s, '`email`') ? 'email' : 'pseudo'] !== $a[3]) continue;
                if (isset($a[4]) && $row['droits'] !== $a[4]) continue;
                $row['password'] = $a[0]; $this->affected = 1;
            }
        } elseif (preg_match('/^INSERT INTO `([^`]+)` \(pseudo, password, droits\)/', $s, $m)) {
            $db->tables[$m[1]][] = ['id'=>count($db->tables[$m[1]] ?? [])+1,'pseudo'=>$a[0],'password'=>$a[1],'droits'=>$a[2]];
            $this->affected = 1;
        } elseif (str_starts_with($s, 'INSERT INTO sessions')) {
            $db->rows[$a[0]] = ['access'=>$a[1], 'data'=>$a[2]]; $this->affected = 1;
        } elseif (str_contains($s, "LEFT(id, 4) = 'apk_'")) {
            foreach ($db->rows as $key=>$row) if (str_starts_with($key,'apk_') && $row['access'] < $a[0]) unset($db->rows[$key]);
        } else throw new RuntimeException('Unexpected SQL: '.$s);
        return true;
    }
    function fetchAll($mode) { return $this->result; }
    function fetch($mode) { return $this->result[0] ?? false; }
    function fetchColumn() { return $this->result ? reset($this->result[0]) : false; }
    function rowCount() { return $this->affected; }
}
