<?php
if (PHP_SAPI !== 'cli' && !defined('ANDROID_TEST_FIXTURE')) { http_response_code(403); exit; }
if (!class_exists('PDO')) { class PDO { const FETCH_ASSOC = 2; } }
// Minimal PDO double. Optional file backing lets isolated HTTP clients share
// ticket rows; it never opens the application's configuration or database.
class AndroidTestDB {
    public array $rows = [];
    public function __construct(public ?string $file = null) {}
    public function prepare($sql) { return new AndroidTestQuery($this, $sql); }
}
class AndroidTestQuery {
    private array $result = [];
    private int $affected = 0;
    public function __construct(private AndroidTestDB $db, private string $sql) {}
    public function execute($args) {
        $handle = null;
        if ($this->db->file) {
            $handle = fopen($this->db->file, 'c+'); flock($handle, LOCK_EX);
            $this->db->rows = json_decode(stream_get_contents($handle), true) ?: [];
        }
        $rows =& $this->db->rows;
        if (str_starts_with($this->sql, 'SELECT id FROM')) {
            $this->result = (int)$args[0] === 7 ? ['id' => 7] : [];
        } elseif (str_starts_with($this->sql, 'SELECT data')) {
            $this->result = $rows[$args[0]] ?? [];
        } elseif (str_starts_with($this->sql, 'INSERT INTO')) {
            if (isset($rows[$args[0]])) throw new RuntimeException('Duplicate ticket');
            $rows[$args[0]] = ['access' => $args[1], 'data' => $args[2]];
            $this->affected = 1;
        } elseif (str_contains($this->sql, 'LEFT(id')) {
            foreach ($rows as $key => $row) if (str_starts_with($key, 'apk_') && $row['access'] < $args[0]) unset($rows[$key]);
        } elseif (str_starts_with($this->sql, 'DELETE FROM')) {
            if (isset($rows[$args[0]]) && $rows[$args[0]]['data'] === $args[1]) {
                unset($rows[$args[0]]); $this->affected = 1;
            }
        } else { throw new RuntimeException('Unexpected query: '.$this->sql); }
        if ($handle) {
            rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode($rows));
            flock($handle, LOCK_UN); fclose($handle);
        }
        return true;
    }
    public function fetch($mode) { return $this->result ?: false; }
    public function fetchColumn() { return $this->result ? reset($this->result) : false; }
    public function rowCount() { return $this->affected; }
}
