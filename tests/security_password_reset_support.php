<?php
if (PHP_SAPI !== 'cli' && !defined('RESET_TEST_FIXTURE')) { http_response_code(403); exit; }
if (!class_exists('PDO')) { class PDO { const FETCH_ASSOC = 2; } }
class ResetDB {
    public $rows = [], $players = [], $loseClaim = false, $changeBeforeUpdate = null;
    function prepare($sql) { return new ResetQuery($this, $sql); }
}
class ResetQuery {
    private $result = [], $affected = 0;
    function __construct(private $db, private $sql) {}
    function execute($a) {
        $s = $this->sql; $db = $this->db;
        if (str_starts_with($s, 'INSERT INTO sessions')) {
            if (count($a) === 5 && isset($db->rows[$a[0]]) && $db->rows[$a[0]]['access'] > $a[3]) {
                $db->rows[$a[0]]['data'] = (string)((int)$db->rows[$a[0]]['data'] + 1);
            } else $db->rows[$a[0]] = ['access' => $a[1], 'data' => $a[2]];
        } elseif (str_starts_with($s, 'SELECT data FROM sessions')) {
            $this->result = isset($db->rows[$a[0]]) ? [['data' => $db->rows[$a[0]]['data']]] : [];
        } elseif (str_starts_with($s, 'DELETE FROM sessions')) {
            if (!$db->loseClaim && isset($db->rows[$a[0]]) && $db->rows[$a[0]]['data'] === $a[1]) {
                unset($db->rows[$a[0]]); $this->affected = 1;
            }
        } elseif (str_starts_with($s, 'SELECT id, email, password')) {
            $table = str_contains($s, '`two_joueurs`') ? 'two_' : 'one_';
            foreach ($db->players[$table] ?? [] as $p) {
                if (str_contains($s, 'WHERE email') ? $p['email'] === $a[0] : ($p['id'] === $a[0] && $p['datesupp'] === 0)) $this->result[] = $p;
            }
        } elseif (str_starts_with($s, 'UPDATE')) {
            if ($db->changeBeforeUpdate !== null) $db->players['one_'][0]['password'] = $db->changeBeforeUpdate;
            foreach ($db->players['one_'] as &$p) if ($p['id'] === $a[1] && $p['email'] === $a[2] && $p['password'] === $a[3] && $p['datesupp'] === 0) {
                $p['password'] = $a[0]; $this->affected = 1;
            }
        } else throw new RuntimeException('Unexpected SQL: '.$s);
        return true;
    }
    function fetch($mode) { return $this->result[0] ?? false; }
    function fetchAll($mode) { return $this->result; }
    function fetchColumn() { return isset($this->result[0]) ? reset($this->result[0]) : false; }
    function rowCount() { return $this->affected; }
}
