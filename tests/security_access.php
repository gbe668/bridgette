<?php
// No application configuration, real database, email or maintenance execution.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../appli/security.php';

class TestQuery {
    public function __construct(private string $sql) {}
    public array $args = [];
    public function execute($args) { $this->args = $args; }
    public function fetchColumn() {
        global $case;
        if (str_contains($this->sql, 'joueurs')) return empty($case['deleted']) ? 7 : false;
        if (($case['member'] ?? true) === false) return 0;
        if (count($this->args) === 4) return $this->args[3] === ($case['pair'] ?? 1) ? 1 : 0;
        return 1;
    }
}
class TestDB { public function prepare($sql) { return new TestQuery($sql); } }
function connectBDD() { return new TestDB(); }
function readTournoi($id) { global $case; return ['etat' => $case['state'] ?? 33, 'genre' => $case['genre'] ?? 1, 'paquet' => 3, 'pairesNS' => 6, 'pairesEO' => 6]; }
function isCheckedJoueur($id) { global $case; return $case['checked'] ?? true; }
function existeTournoiVivant() { return 5; }

if (isset($argv[1])) {
    $case = json_decode(base64_decode($argv[1]), true);
    $_SERVER = ['HTTP_HOST' => 'club.test', 'HTTPS' => 'on', 'HTTP_REFERER' => 'https://club.test/bridge40.php',
        'REQUEST_METHOD' => 'GET', 'SCRIPT_FILENAME' => '/app/'.$case['route']];
    if (isset($case['origin'])) $_SERVER['HTTP_ORIGIN'] = $case['origin'];
    if (!empty($case['no_origin'])) unset($_SERVER['HTTP_REFERER']);
    $_SESSION = ['prefix' => 'one_', 'ttlast' => time() - ($case['age'] ?? 0)];
    if (isset($case['role'])) $_SESSION += ['pseudo' => 'tester', 'fonction' => $case['role'], 'auth_club' => $case['club'] ?? 'one_'];
    if (!empty($case['player'])) $_SESSION['player_identity'] = ['club' => $case['club'] ?? 'one_', 'id' => 7, 'time' => time()];
    if (!empty($case['native'])) $_SESSION['android_ticket_hash'] = str_repeat('a', 64);
    $parametres = ['checkuser' => $case['mode'] ?? 2];
    $_COOKIE['bridgette_user'] = base64_encode(json_encode(['ttt' => 'one_', 'userid' => 7]));
    $_GET = $case['args'] ?? [];
    // Authorization cases use the new browser contract; CSRF failures are tested separately.
    if (in_array($case['route'], securityMutationRoutes(), true)) {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $_GET;
        $_POST['_csrf'] = securityCsrfToken();
    }
    $tab_joueurs = 'one_joueurs'; $tab_pairesNS = 'one_pairesNS'; $tab_pairesEO = 'one_pairesEO';
    $st_phase_jeu = 33; $st_phase_fini = 34; $t_howell = 2;
    register_shutdown_function(function () { echo '\nSTATUS='.(http_response_code() ?: 200); });
    require __DIR__.'/../appli/security_routes.php';
    echo 'REACHED';
    exit;
}

$tests = [];
$directors = ['createtournoi.php','closetournoi.php','erasetournoi.php','purgetournoi.php','erasedonne.php',
    'setpoints.php','updatedonne.php','setnpositions.php','setphaseinit.php','startinscriptiontournoi.php',
    'startmitchell.php','starthowell.php','stoptournoi.php','sendresults.php','saveparams.php','savecalendar.php',
    'f41inittournoi.php','f41setjoueur.php','f41settypetournoi.php','f41unsetjoueur.php',
    'f30createjoueur.php','f30deletejoueur.php','f30erasejoueur.php','f30restorejoueur.php','f30updatejoueur.php',
    'f25setjoueur.php','insertcontact.php','updatecontact.php','erasecontact.php','setposjoueur.php',
    'bridge49h.php','bridge49m.php','mitch44.php','howell44.php'];
foreach ($directors as $route) {
    $tests[] = [['route' => $route], 403];
    $tests[] = [['route' => $route, 'role' => 'directeur'], 200];
    $tests[] = [['route' => $route, 'role' => 'admin'], 403];
}
foreach (['setloginadmin.php','setlogindirecteur.php','setloginformateur.php','deletedirecteur.php','purgevieuxtournois.php'] as $route) {
    $tests[] = [['route' => $route], 403];
    $tests[] = [['route' => $route, 'role' => 'admin'], 200];
    $tests[] = [['route' => $route, 'role' => 'directeur'], 403];
}
foreach ([['club' => 'other_'], ['age' => 1441], ['origin' => 'https://evil.test'],
    ['args' => ['idtournoi' => '1 OR 1=1']], ['args' => ['idtournoi' => ['1']]]] as $extra) {
    $tests[] = [array_merge(['route' => 'erasetournoi.php', 'role' => 'directeur'], $extra), isset($extra['args']) ? 400 : 403];
}
foreach (['f25setpaire.php' => 'ida', 'f25updatefield.php' => 'idjoueur'] as $route => $key) {
    $tests[] = [['route' => $route, 'args' => [$key => 7]], 401]; // Forged cookie ignored.
    $tests[] = [['route' => $route, 'player' => true, 'args' => [$key => 7]], 200];
    $tests[] = [['route' => $route, 'player' => true, 'args' => [$key => 8]], 403];
    $tests[] = [['route' => $route, 'player' => true, 'deleted' => true, 'args' => [$key => 7]], 401];
    $tests[] = [['route' => $route, 'player' => true, 'club' => 'other_', 'args' => [$key => 7]], 401];
}
$score = ['route' => 'insertdonne.php', 'player' => true,
    'args' => ['idtournoi' => 5, 'ns' => 1, 'eo' => 2, 'donne' => 1, 'points' => 420, 'contrat' => '4 P', 'jouepar' => 'N', 'entame' => 'AC', 'resultat' => '0']];
$tests[] = [$score, 200];
$tests[] = [array_merge($score, ['state' => 34]), 403]; // Finished play cannot accept scores.
foreach (['howell64.php', 'mitch64.php', 'mitch64diags.php'] as $route) {
    foreach ([0, 1, 2] as $mode) {
        $finishPage = ['route' => $route, 'mode' => $mode, 'player' => true, 'args' => ['idtournoi' => 5]];
        $tests[] = [array_merge($finishPage, ['state' => 33]), 200];
        $tests[] = [array_merge($finishPage, ['state' => 34]), 200];
        $tests[] = [array_merge($finishPage, ['state' => 4]), 403];
        $tests[] = [array_merge($finishPage, ['state' => 34, 'member' => false]), $mode < 2 ? 200 : 403];
    }
}
foreach (['howell62.php', 'howell63.php', 'howell65.php', 'mitch63.php'] as $route) {
    $tests[] = [['route' => $route, 'player' => true, 'state' => 34, 'args' => ['idtournoi' => 5]], 403];
}
foreach ([['member' => false], ['state' => 4], ['checked' => false]] as $extra) $tests[] = [array_merge($score, $extra), 403];
$tests[] = [array_merge($score, ['pair' => 3]), 200]; // P checks tournament registration, not the selected pair.
foreach ([0, 1, 2] as $mode) {
    $tests[] = [array_merge($score, ['mode' => $mode, 'player' => false]), $mode === 0 ? 200 : 401];
    $tests[] = [array_merge($score, ['mode' => $mode, 'member' => false]), $mode < 2 ? 200 : 403];
    $tests[] = [array_merge($score, ['mode' => $mode, 'checked' => false]), 403];
    $tests[] = [array_merge($score, ['mode' => $mode, 'state' => 4]), 403];
    $tests[] = [array_merge($score, ['mode' => $mode, 'deleted' => true]), $mode === 0 ? 200 : 401];
    $tests[] = [array_merge($score, ['mode' => $mode, 'native' => true, 'checked' => false]), 200];
    $tests[] = [array_merge($score, ['mode' => $mode, 'native' => true, 'member' => false]), 403];
}
$tests[] = [array_merge($score, ['mode' => 1, 'club' => 'two_']), 401];
$tests[] = [array_merge($score, ['mode' => 0, 'native' => true, 'player' => false, 'checked' => false]), 403];
$tests[] = [array_merge($score, ['args' => array_merge($score['args'], ['ns' => 99])]), 400];
$tests[] = [array_merge($score, ['genre' => 2, 'pair' => 2]), 200];
foreach (['f65setdiagramme.php','f65upddiagramme.php','f65deldiagramme.php'] as $route) {
    $tests[] = [['route' => $route, 'role' => 'formateur', 'args' => ['idtournoi' => 0, 'donne' => 1]], 200];
    $tests[] = [['route' => $route, 'player' => true, 'args' => ['idtournoi' => 0, 'donne' => 1]], 403];
    $tests[] = [['route' => $route, 'player' => true, 'args' => ['idtournoi' => 5, 'donne' => 1]], 200];
}
foreach (['api_displaytournoi.php','api_existetournoiclos.php','getpositionsjoueurs.php'] as $route) $tests[] = [['route' => $route], 200];

$failed = 0;
foreach ($tests as [$input, $expected]) {
    $pipes = [];
    $p = proc_open([PHP_BINARY, '-n', __FILE__, base64_encode(json_encode($input))], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($p);
    if ($exit !== 0 || $error || !str_ends_with($output, 'STATUS='.$expected) || (($expected === 200) !== str_contains($output, 'REACHED'))) {
        echo 'FAIL '.json_encode($input).' => '.$output.$error."\n"; $failed++;
    }
}
echo count($tests).' access cases, '.$failed." failures\n";
exit($failed ? 1 : 0);
