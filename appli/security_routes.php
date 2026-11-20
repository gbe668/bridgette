<?php
// Executed by bridgette_bdd.php before a route can perform any business write.
// SCRIPT_FILENAME also identifies Android URLs ending in .php/ correctly.
$securityRoute = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
header('Cache-Control: no-store');
if (in_array($securityRoute, securityMutationRoutes(), true) ||
    (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !in_array($securityRoute, ['apk_loguserin.php', 'apk_resume.php'], true))) {
    securityCsrfRequire();
    if (isset($_SERVER['HTTP_ORIGIN'])) securitySameOrigin();
    // Legacy handlers read GET. Only the validated POST body supplies action values.
    if (in_array($securityRoute, securityMutationRoutes(), true)) $_GET = $_POST;
}

$securityDirectorRoutes = [
    'createtournoi.php', 'closetournoi.php', 'erasetournoi.php', 'purgetournoi.php',
    'erasedonne.php', 'setpoints.php', 'updatedonne.php', 'setnpositions.php',
    'setphaseinit.php', 'startinscriptiontournoi.php', 'startmitchell.php',
    'starthowell.php', 'stoptournoi.php', 'sendresults.php', 'saveparams.php',
    'savecalendar.php', 'f41inittournoi.php', 'f41permutejoueurs.php',
    'f41setjoueur.php', 'f41settypetournoi.php', 'f41unsetjoueur.php',
    'f30createjoueur.php', 'f30deletejoueur.php', 'f30erasejoueur.php',
    'f30restorejoueur.php', 'f30updatejoueur.php', 'f25setjoueur.php',
    'insertcontact.php', 'updatecontact.php', 'erasecontact.php', 'setposjoueur.php',
    'bridge49h.php', 'bridge49m.php', 'mitch44.php', 'howell44.php'
];
$securityAdminRoutes = ['deletedirecteur.php', 'purgevieuxtournois.php'];
$securityAdminForms = ['setloginadmin.php', 'setlogindirecteur.php', 'setloginformateur.php'];
$securityPlayerRoutes = ['f25setpaire.php', 'f25updatefield.php', 'inscriptiontournoi.php',
    'insertdonne.php', 'f65setdiagramme.php', 'f65upddiagramme.php', 'f65deldiagramme.php',
    'relaishowell.php', 'relaismitchell.php', 'relaisgueridon.php'];

if (in_array($securityRoute, $securityAdminForms, true)) {
    securityRequireRole('admin');
}
if ($securityRoute === 'purgepreinscriptions.php') securityRequireRole('directeur');
if (in_array($securityRoute, $securityDirectorRoutes, true)) securityRequireRole('directeur');
if (in_array($securityRoute, $securityAdminRoutes, true)) securityRequireRole('admin');

if (in_array($securityRoute, array_merge($securityDirectorRoutes, $securityAdminRoutes, $securityPlayerRoutes), true)) {
    // Numeric identifiers must not become SQL fragments in legacy functions.
    foreach (['idtournoi', 'tournoi', 'id', 'idjoueur', 'idj', 'ida', 'idb', 'etui', 'z_etui',
        'donne', 'ns', 'eo', 'numpaire', 'position', 'paquet', 'inc', 'npositions', 'type',
        'okns', 'okeo', 'paires', 'oldtable', 'newtable', 'oldplace', 'newplace'] as $key) {
        if (isset($_GET[$key])) securityInt($key, 0);
    }
    if (isset($_GET['points'])) securityInt('points', -10000, 10000);
    if (isset($_GET['k'])) securityInt('k', -1, 29);
}

if ($securityRoute === 'f41permutejoueurs.php') {
    foreach (['src', 'dst'] as $key) {
        if (!is_string($_GET[$key] ?? null) || !preg_match('/\A(NS|EO)[1-9][0-9]?\z/D', $_GET[$key])) securityDeny('Paire incorrecte.', 400);
    }
}
if ($securityRoute === 'f25setpaire.php') securityOwnPlayer('ida');
if ($securityRoute === 'f25updatefield.php') securityOwnPlayer('idjoueur');
if ($securityRoute === 'inscriptiontournoi.php') {
    if (!securityRole('directeur')) securityRequirePlayer();
    $date = $_GET['datetournoi'] ?? '';
    if (!is_string($date) || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $date) ||
        !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) securityDeny('Date incorrecte.', 400);
}
if ($securityRoute === 'insertdonne.php') {
    securityTournament(securityInt('idtournoi'), securityInt('ns'), securityInt('eo'));
    securityInt('donne', 1, 36);
    securityInt('points', -10000, 10000);
    foreach (['contrat' => 10, 'jouepar' => 5, 'entame' => 5, 'resultat' => 5] as $key => $length) {
        if (!is_string($_GET[$key] ?? null) || strlen($_GET[$key]) > $length ||
            !preg_match('/\A[A-Za-z0-9 +\-=]*\z/D', $_GET[$key])) securityDeny('Résultat incorrect.', 400);
    }
}
if (in_array($securityRoute, ['f65setdiagramme.php', 'f65upddiagramme.php', 'f65deldiagramme.php'], true)) {
    $id = securityInt('idtournoi', 0);
    // Formation uses tournament zero, never a real club tournament.
    if ($id === 0) securityRequireRole('formateur');
    else securityTournament($id);
    securityInt('donne', 1, 36);
}
if (in_array($securityRoute, ['relaishowell.php', 'relaismitchell.php', 'relaisgueridon.php'], true)) {
    $id = (int)existeTournoiVivant();
    if ($id <= 0) securityDeny('Aucun tournoi en cours.');
    securityTournament($id, isset($_GET['ns']) ? securityInt('ns') : 0, securityInt('eo', 0));
    $t = readTournoi($id);
    if (securityInt('paquet', 1, 36) !== (int)$t['paquet']) securityDeny('Paquet incorrect.', 400);
    if (isset($_GET['inc']) && securityInt('inc', 1, 36) !== (int)$t['paquet']) securityDeny('Avancement incorrect.', 400);
}

// Player navigation remains in the native WebView; never redirect to web login.
if (preg_match('/\Aapk_(mitch6[2-5](diags)?|howell6[2-5])\.php\z/', $securityRoute)) securityAndroidRequire();
// Apply the configured policy before gameplay pages can display or update state.
if (preg_match('/\A(apk_)?(mitch6[1-5](diags)?|howell6[1-5])\.php\z/', $securityRoute)) {
    $native = str_starts_with($securityRoute, 'apk_');
    if (!$native && securityPlayerMode() > 0) securityWebLogin();
    $tournament = securityInt('idtournoi');
    if (!$native && !isCheckedJoueur($tournament)) {
        header('Location: '.securityAppPath('checkjoueur.php'));
        exit;
    }
    // State 64 still presents the last board and provisional results after play ends.
    $allowFinished = in_array($securityRoute, ['howell64.php', 'apk_howell64.php',
        'mitch64.php', 'apk_mitch64.php', 'mitch64diags.php', 'apk_mitch64diags.php'], true);
    securityTournament($tournament, null, null, $allowFinished);
    securityRememberPlayer();
}
unset($securityRoute);
