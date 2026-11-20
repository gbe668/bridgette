<?php
// Exercise PHP's real session callbacks with an in-memory database double.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
class Database {
    public static array $rows = [];
    public static int $opened = 0;
    private bool $connected = true;
    private string $sql = '';
    private array $args = [];
    public function __construct() { self::$opened++; }
    public function query($sql) { $this->sql = $sql; $this->args = []; }
    public function bind($name, $value) { $this->args[$name] = $value; }
    public function execute() {
        if (!$this->connected) return false;
        $id = $this->args[':id'] ?? '';
        if (str_starts_with($this->sql, 'REPLACE')) self::$rows[$id] = $this->args[':data'];
        if (str_starts_with($this->sql, 'DELETE')) unset(self::$rows[$id]);
        return true;
    }
    public function rowCount() { return isset(self::$rows[$this->args[':id'] ?? '']) ? 1 : 0; }
    public function single() { return ['data' => self::$rows[$this->args[':id']] ?? '']; }
    public function close() { $this->connected = false; return true; }
}
function verify($ok, $label) { if (!$ok) throw new RuntimeException($label); }
set_error_handler(function ($level, $message) { throw new RuntimeException($message); });
require __DIR__.'/../appli/src/mysql.sessions.php';
require __DIR__.'/../appli/security.php';
$handler = new Session();
verify(ini_get('session.gc_probability') === '0', 'Random GC disabled');
verify((int)ini_get('session.gc_maxlifetime') === 100 * 86400, 'GC retention aligned');
Database::$rows['gc_monthly'] = '2026-09';
verify($handler->read('gc_monthly') === '', 'GC marker cannot become a session');
verify(!$handler->write('gc_monthly', 'overwrite'), 'GC marker cannot be overwritten');
verify($handler->destroy('gc_monthly') && Database::$rows['gc_monthly'] === '2026-09', 'GC marker cannot be destroyed');
verify(!$handler->updateTimestamp('gc_monthly', ''), 'GC marker cannot be refreshed');
Database::$rows['apk_reserved'] = 'ticket';
verify($handler->read('apk_reserved') === '', 'Ticket cannot be read as a session');
verify(!$handler->write('apk_reserved', 'overwrite'), 'Ticket cannot be overwritten as a session');
verify($handler->destroy('apk_reserved') && Database::$rows['apk_reserved'] === 'ticket', 'Session destroy preserves ticket');
verify(!$handler->updateTimestamp('apk_reserved', ''), 'Session refresh preserves ticket expiry');
foreach (['pwd_reserved', 'rlm_reserved', 'adr_reserved'] as $reserved) {
    Database::$rows[$reserved] = 'technical-record';
    verify($handler->read($reserved) === '', 'Reset records cannot become sessions');
    verify(!$handler->write($reserved, 'overwrite'), 'Session writes preserve reset records');
    verify($handler->destroy($reserved) && Database::$rows[$reserved] === 'technical-record', 'Session destruction preserves reset records');
    verify(!$handler->updateTimestamp($reserved, ''), 'Session refresh preserves reset expiry/rate window');
}
securitySelectClub(['prefix' => 'one_', 'timex' => time()]);
$_SESSION['pseudo'] = 'director';
$_SESSION['fonction'] = 'directeur';
$_SESSION['auth_club'] = 'one_';
$_SESSION['ttlast'] = time();
verify(session_write_close(), 'Initial session write');
$before = session_id();
verify(isset(Database::$rows[$before]), 'Session persisted');
verify(session_start(), 'Reopen persisted session');
verify(securityRole('directeur'), 'Role restored');
verify(session_regenerate_id(true), 'Rotate authenticated session');
$after = session_id();
verify($after !== $before && !isset(Database::$rows[$before]), 'Old identifier removed');
verify(session_write_close(), 'Write after rotation');
verify(isset(Database::$rows[$after]), 'New identifier persisted');
verify(session_start(), 'Reopen after rotation');
verify(securityRole('directeur'), 'Role survives rotation');
securitySelectClub(['prefix' => 'two_', 'timex' => time()]);
verify(!securityRole('directeur'), 'Club switch revokes role');
verify(session_write_close(), 'Write after club switch');
verify(session_start(), 'Reopen changed club');
verify($_SESSION['prefix'] === 'two_' && !isset($_SESSION['pseudo']), 'Changed club persisted');
session_destroy();
restore_error_handler();
echo "Session lifecycle: persistence, rotation and club switch OK\n";

