<?php
// Execute real business queries against a recorder; never load configuration/MySQL.
require __DIR__.'/../appli/security.php';
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
class BoundStatement implements IteratorAggregate {
    function __construct(public $db, public $sql) {}
    function execute($args) {
        preg_match_all('/:\w+/', $this->sql, $named);
        if ($named[0]) {
            foreach ($named[0] as $name) if (!array_key_exists($name, $args)) throw new RuntimeException('Missing named binding '.$name);
        } elseif (substr_count($this->sql, '?') !== count($args)) throw new RuntimeException('Binding count: '.$this->sql);
        $this->db->queries[] = [$this->sql, $args];
        return true;
    }
    function fetchColumn() { return $this->db->count; }
    function rowCount() { return 0; }
    function getIterator(): Traversable { return new ArrayIterator([]); }
}
class BoundDB {
    public $queries = [];
    public $count = 0;
    function prepare($sql) { return new BoundStatement($this, $sql); }
}
$db = new BoundDB();
function connectBDD() { global $db; return $db; }
function loadFunction($file, $name) {
    $source = file_get_contents(__DIR__.'/../appli/'.$file);
    $start = strpos($source, 'function '.$name.'(');
    if ($start === false) throw new RuntimeException($name);
    // Scan PHP tokens so braces in SQL, strings and comments do not affect extraction.
    $tokens = token_get_all('<?php '.substr($source, $start));
    $body = ''; $depth = 0; $opened = false;
    foreach (array_slice($tokens, 1) as $token) {
        $body .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $opened = true; }
        if ($token === '}' && --$depth === 0 && $opened) break;
    }
    eval($body);
}
foreach (['_logevent', '_getJoueur', 'getListeJoueurs', 'htmlAnnuaire', 'getClassement', '_razCnxTables', '_getlignes', 'set_etat'] as $name) loadFunction('bridgette_bdd.php', $name);
loadFunction('getannuaire.php', 'getAnnuaire');
foreach (['joueurs','tournois','pairesNS','pairesEO','events','connexions'] as $table) ${'tab_'.$table} = 'tst_'.$table;
$min_noclub = 100; $max_tables = 15; $cnx_ko = 0; $st_closed = 4;
$_SESSION = ['pseudo' => "O'Brien"];
$attack = "x' OR 1=1; DROP TABLE tst_joueurs; --";
_logevent($db, $attack);
_getJoueur($db, $attack);
foreach (['', "O'Brien", $attack] as $filter) {
    foreach ([1, 2, 3] as $list) {
        getListeJoueurs($list, $attack, $filter);
        getAnnuaire($list, 'nom', $filter);
    }
}
htmlAnnuaire();
$parametres = ['nbmperf' => $attack, 'minperf' => 15, 'nbjperf' => 10];
ob_start(); getClassement(); ob_end_clean();
_razCnxTables($db);
$db->count = 1;
ob_start(); getClassement(); ob_end_clean();
_getlignes($db, 5);
set_etat($attack, 34);
if (end($db->queries)[1] !== [34, $attack]) throw new RuntimeException('State transition parameter order');
foreach ($db->queries as [$sql, $args]) {
    if (str_contains($sql, $attack) || str_contains($sql, "O'Brien")) throw new RuntimeException('Interpolated value');
}
if ($db->queries[0][1][0] !== "O'Brien" || $db->queries[0][1][2] !== $attack || $db->queries[1][1] !== [$attack]) throw new RuntimeException('Altered values');
echo count($db->queries)." SQL queries: hostile values bound separately, ordering allowlist and placeholder counts OK.\n";
