<?php
define('BRIDGETTE_SUPERVISOR', true);
require_once __DIR__.'/supervisor_lib.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', '0');
function supervisorEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function supervisorPost(string $key): string { return is_string($_POST[$key] ?? null) ? $_POST[$key] : ''; }
$error = '';
$message = '';
$config = null;
$authenticated = false;
$pending = null;
$activationName = 'supervisor_access_<domaine>.php';
$secure = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
try {
    if (!$secure && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        throw new RuntimeException('Utiliser HTTPS pour accéder au superviseur. HTTP est réservé aux tests sur cet ordinateur.');
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        throw new RuntimeException('Méthode non autorisée.');
    }
    $serverHost = supervisorHost($_SERVER['SERVER_NAME'] ?? null);
    $activationName = supervisorActivationName($serverHost);
    $file = __DIR__.'/clubs/'.$activationName;
    if (!is_file($file)) throw new RuntimeException('Superviseur désactivé. Déposer le fichier d’activation par FTP.');
    $config = supervisorValidateConfig(require $file, $serverHost);
    // Native file sessions, distinct name and payload from all club/MySQL sessions.
    ini_set('session.save_handler', 'files');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('BRIDGETTE_SUPERVISOR_'.substr(hash('sha256', __DIR__), 0, 12));
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
    if (!session_start()) throw new RuntimeException('Sessions opérateur indisponibles. Vérifier le répertoire temporaire PHP.');
    $binding = hash('sha256', serialize($config));
    if (($_SESSION['binding'] ?? '') !== $binding || time() - ($_SESSION['last'] ?? 0) > 900 ||
        time() - ($_SESSION['started'] ?? 0) > 3600) {
        $_SESSION = ['binding' => $binding, 'started' => time()];
        if (!session_regenerate_id(true)) throw new RuntimeException('Session opérateur indisponible.');
    }
    $_SESSION['last'] = time();
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $authenticated = ($_SESSION['operator'] ?? false) === true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], supervisorPost('csrf'))) {
            http_response_code(403);
            throw new RuntimeException('Formulaire expiré ou invalide. Recharger la page.');
        }
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $action = supervisorPost('action');
        if ($action === 'login') {
            if (!supervisorPassword($config, supervisorPost('password'))) throw new RuntimeException('Connexion refusée.');
            if (!session_regenerate_id(true)) throw new RuntimeException('Session opérateur indisponible.');
            $_SESSION['operator'] = true;
            $_SESSION['started'] = time();
            $authenticated = true;
        } elseif ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            header('Location: supervisor.php', true, 303);
            exit;
        } elseif (!$authenticated) {
            http_response_code(403);
            throw new RuntimeException('Connexion opérateur nécessaire.');
        } elseif ($action === 'preview') {
            unset($_SESSION['pending']);
            $prefix = supervisorPost('prefix');
            $command = supervisorPost('command');
            supervisorTarget($config, $prefix, $command);
            $patch = $command === 'patch' ? supervisorPatch($config) : '';
            $_SESSION['pending'] = ['prefix' => $prefix, 'command' => $command, 'patch' => $patch, 'time' => time()];
        } elseif ($action === 'cancel') {
            unset($_SESSION['pending']);
        } elseif ($action === 'execute') {
            $operation = $_SESSION['pending'] ?? null;
            // Consume before any connection/write: browser reload cannot execute twice.
            unset($_SESSION['pending']);
            if (!is_array($operation) || time() - $operation['time'] > 300) throw new RuntimeException('Confirmation expirée. Préparer à nouveau l’opération.');
            $prefix = $operation['prefix'];
            $command = $operation['command'];
            supervisorTarget($config, $prefix, $command);
            if (supervisorPost('confirmation') !== supervisorConfirmation($prefix, $command)) throw new RuntimeException('Texte de confirmation incorrect.');
            if (!supervisorPassword($config, supervisorPost('password'))) throw new RuntimeException('Mot de passe opérateur incorrect.');
            if ($command === 'patch' && !hash_equals($operation['patch'], supervisorPatch($config))) throw new RuntimeException('Le patch a changé.');
            $admin = in_array($command, ['install', 'addadmin'], true) ? supervisorAdmin(supervisorPost('admin_name'), supervisorPost('admin_password')) : [];
            // Validate and persist consumed authorization before touching the database.
            if (!session_write_close()) throw new RuntimeException('Impossible de conserver la confirmation. Opération annulée.');
            $reference = bin2hex(random_bytes(6));
            $dbh = null;
            $locked = false;
            $buffer = ob_get_level();
            try {
                $dbh = supervisorConnect($config);
                $lockName = 'bridgette-supervisor-'.substr(hash('sha256', DB_NAME), 0, 32);
                $lock = $dbh->prepare('SELECT GET_LOCK(?, 0)');
                $lock->execute([$lockName]);
                $locked = (int)$lock->fetchColumn() === 1;
                if (!$locked) throw new RuntimeException('Maintenance concurrente.');
                error_log('Bridgette supervisor '.$reference.' start '.$command.' prefix='.$prefix);
                ob_start(); // Legacy helpers/patches must not leak database errors or secrets.
                $message = supervisorRun($dbh, $config, $prefix, $command, $admin);
                ob_end_clean();
                error_log('Bridgette supervisor '.$reference.' success');
            } catch (Throwable $e) {
                while (ob_get_level() > $buffer) ob_end_clean();
                error_log('Bridgette supervisor '.$reference.' failed '.get_class($e).' code='.$e->getCode());
                if ($e instanceof SupervisorAdminExists) throw new RuntimeException($e->getMessage());
                throw new RuntimeException('Opération interrompue (référence '.$reference.'). Vérifier la connexion, les droits MySQL et les journaux serveur. Des changements partiels sont possibles ; ne pas relancer sans contrôle.');
            } finally {
                if ($locked) {
                    try { $dbh->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]); } catch (Throwable $ignored) { }
                }
                $dbh = null;
            }
        } else {
            throw new RuntimeException('Action inconnue.');
        }
    }
    $pending = $_SESSION['pending'] ?? null;
    if ($pending && time() - $pending['time'] > 300) { unset($_SESSION['pending']); $pending = null; }
} catch (Throwable $e) {
    $error = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Superviseur indisponible. Vérifier la configuration et les journaux PHP.';
    if (!$config) http_response_code(503);
}
$csrf = $_SESSION['csrf'] ?? '';
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$labels = ['install' => 'Installer / compléter les tables', 'addadmin' => 'Ajouter un administrateur au club', 'droptables' => 'Supprimer les tables du club', 'gc' => 'Purger les sessions de plus de 100 jours', 'patch' => 'Exécuter le patch autorisé'];
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bridgette — Superviseur</title>
<style>
body{font:17px/1.5 system-ui,sans-serif;color:#203047;background:#eef2f6;margin:0;padding:24px}main{max-width:800px;margin:24px auto}h1{margin-bottom:0}h2{font-size:1.3em}.card{background:white;padding:24px;border-radius:12px;margin:20px 0;box-shadow:0 2px 12px #20304712}label{display:block;margin:16px 0 6px}input,select,button{box-sizing:border-box;font:inherit;border:1px solid #8493a6;border-radius:6px;padding:10px}input,select{width:100%}button{background:#175a82;color:white;border:0;cursor:pointer;margin-top:20px}button.secondary{background:#526174}.error{border-left:5px solid #b12832;background:#fff0f0;padding:16px}.success{border-left:5px solid #267441;background:#effaf2;padding:16px}.note{color:#536377}code{overflow-wrap:anywhere}li{margin:4px 0}.danger{color:#a32630}small{display:block;margin-top:12px}
</style></head><body><main>
<h1>Superviseur Bridgette</h1><p class="note">Maintenance de l’hébergement · accès opérateur</p>
<?php if ($error): ?><p class="error" role="alert"><?= supervisorEscape($error) ?></p><?php endif ?>
<?php if ($message): ?><p class="success" role="status"><?= supervisorEscape($message) ?></p><?php endif ?>
<?php if ($config && $csrf && !$authenticated): ?>
<section class="card"><h2>Connexion opérateur</h2><p>Utiliser le mot de passe du fichier d’activation, indépendant des comptes administrateurs et directeurs de club.</p>
<form method="post" action="supervisor.php"><input type="hidden" name="csrf" value="<?= supervisorEscape($csrf) ?>"><input type="hidden" name="action" value="login">
<label for="password">Mot de passe opérateur</label><input id="password" type="password" name="password" required maxlength="1024" autocomplete="current-password"><button>Se connecter</button></form></section>
<?php elseif ($config && $csrf && $authenticated): ?>
<p>Configuration : <strong><?= supervisorEscape($config['host']) ?></strong><br>Activation jusqu’au <?= supervisorEscape(gmdate('d/m/Y H:i', $config['expires_at'])) ?> UTC.</p>
<?php if ($pending): $p = $pending['prefix']; $cmd = $pending['command']; ?>
<section class="card"><h2>Confirmer l’opération</h2><p><strong><?= supervisorEscape($labels[$cmd]) ?></strong><br>Club : <?= supervisorEscape($config['clubs'][$p]) ?> — préfixe <code><?= supervisorEscape($p) ?></code></p>
<?php if ($cmd === 'gc'): ?><p>Cette purge concerne la table partagée <code>sessions</code> de <strong>tous les clubs</strong>. Seules les sessions de plus de 100 jours sont supprimées.</p>
<?php elseif ($cmd === 'patch'): ?><p class="danger">Le fichier PHP autorisé peut agir sur toute la base. Son périmètre doit avoir été vérifié avant son dépôt par FTP.</p><p>Empreinte : <code><?= supervisorEscape($pending['patch']) ?></code></p>
<?php elseif ($cmd === 'addadmin'): ?><p>Un nouveau compte administrateur sera ajouté à la table <code><?= supervisorEscape(supervisorTarget($config, $p, $cmd)['directeurs']) ?></code>. Les comptes existants sont conservés. Un pseudo déjà utilisé est refusé. Les tables du club doivent déjà être installées.</p>
<?php else: ?><p>Tables concernées :</p><ul><?php foreach (supervisorTarget($config, $p, $cmd) as $table): ?><li><code><?= supervisorEscape($table) ?></code></li><?php endforeach ?></ul>
<?php if ($cmd === 'droptables'): ?><p class="danger">Toutes les données de ces tables seront perdues. Disposer d’une sauvegarde vérifiée avant de continuer. Les sessions existantes ne sont pas révoquées : mettre le club hors ligne pendant la suppression et la réinstallation.</p><?php else: ?><p>Les tables existantes sont conservées. L’installation ne met pas à niveau leur schéma. Les invités et connexions sont initialisés uniquement si leurs tables sont vides.</p><?php endif ?><?php endif ?>
<?php if ($cmd !== 'addadmin'): ?><p>Les opérations de structure MySQL ne sont pas annulables automatiquement. Mettre le club hors ligne avant une intervention sur ses tables.</p><?php endif ?>
<form method="post" action="supervisor.php"><input type="hidden" name="csrf" value="<?= supervisorEscape($csrf) ?>"><input type="hidden" name="action" value="execute">
<?php if (in_array($cmd, ['install', 'addadmin'], true)): ?><h2><?= $cmd === 'install' ? 'Premier administrateur du club' : 'Nouvel administrateur du club' ?></h2><?php if ($cmd === 'install'): ?><p>Créé uniquement si la table des responsables est vide. Aucun compte prédéfini n’est ajouté.</p><?php endif ?><label for="admin_name">Pseudo</label><input id="admin_name" name="admin_name" maxlength="32" required autocomplete="off"><label for="admin_password">Mot de passe du nouvel administrateur (14 caractères minimum)</label><input id="admin_password" name="admin_password" type="password" minlength="14" maxlength="256" required autocomplete="new-password"><?php endif ?>
<label for="confirmation">Recopier exactement : <strong><?= supervisorEscape(supervisorConfirmation($p, $cmd)) ?></strong></label><input id="confirmation" name="confirmation" required autocomplete="off">
<label for="operator_password">Mot de passe opérateur</label><input id="operator_password" name="password" type="password" required maxlength="1024" autocomplete="current-password"><button>Exécuter cette opération</button><small>Confirmation valable cinq minutes et utilisable une seule fois.</small></form>
<form method="post" action="supervisor.php"><input type="hidden" name="csrf" value="<?= supervisorEscape($csrf) ?>"><button class="secondary" name="action" value="cancel">Annuler</button></form></section>
<?php else: ?>
<section class="card"><h2>Préparer une intervention</h2><form method="post" action="supervisor.php"><input type="hidden" name="csrf" value="<?= supervisorEscape($csrf) ?>"><input type="hidden" name="action" value="preview">
<label for="prefix">Club autorisé</label><select id="prefix" name="prefix"><?php foreach ($config['clubs'] as $prefix => $label): ?><option value="<?= supervisorEscape($prefix) ?>"><?= supervisorEscape($label.' — '.$prefix) ?></option><?php endforeach ?></select>
<label for="command">Opération</label><select id="command" name="command"><?php foreach ($labels as $command => $label): ?><option value="<?= $command ?>"><?= supervisorEscape($label) ?></option><?php endforeach ?></select><button>Vérifier avant exécution</button></form><p class="note">Préparer l’opération ne modifie aucune donnée. Un patch nécessite une empreinte explicitement autorisée dans le fichier d’activation.</p></section>
<?php endif ?>
<form method="post" action="supervisor.php"><input type="hidden" name="csrf" value="<?= supervisorEscape($csrf) ?>"><button class="secondary" name="action" value="logout">Se déconnecter</button></form><p class="note">À la fin, supprimer le fichier d’activation par FTP. Déconnexion après 15 minutes d’inactivité, ou une heure au maximum.</p>
<?php else: ?><section class="card"><p>L’accès nécessite le fichier <code>clubs/<?= supervisorEscape($activationName) ?></code>. Utiliser le modèle fourni et le guide <code>SUPERVISOR.md</code>. Aucun accès à la base n’est effectué tant que l’opération n’est pas confirmée.</p></section><?php endif ?>
</main></body></html>
