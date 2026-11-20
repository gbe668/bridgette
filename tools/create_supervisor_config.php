<?php
// Local Wamp helper; never serve this script as a web setup endpoint.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Accès refusé.'); }
require_once dirname(__DIR__).'/appli/supervisor_lib.php';
$args = getopt('', ['host:', 'prefix:', 'label:']);
$host = $args['host'] ?? '';
$prefix = $args['prefix'] ?? null;
$label = $args['label'] ?? 'Club';
try {
    $host = supervisorHost($host);
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
if (!bridgetteValidClubPrefix($prefix) || !is_string($label)) {
    fwrite(STDERR, "Usage : php tools/create_supervisor_config.php --host=DOMAINE --prefix=ABC_ --label=Club\nPréfixe obligatoire : exactement trois lettres ou chiffres suivis de _ (exemple : tst_).\n");
    exit(1);
}
$destination = dirname(__DIR__).'/appli/clubs/'.supervisorActivationName($host);
$handle = @fopen($destination, 'x'); // Never overwrite an existing activation.
if (!$handle) { fwrite(STDERR, "Création impossible : fichier déjà présent ou dossier non accessible.\n"); exit(1); }
$password = bin2hex(random_bytes(16));
$config = ['enabled' => true, 'expires_at' => time() + 86400,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'host' => $host,
    'clubs' => [$prefix => $label], 'patch_sha256' => ''];
$content = "<?php\nif (!defined('BRIDGETTE_SUPERVISOR')) { http_response_code(404); exit; }\nreturn ".var_export($config, true).";\n";
$written = fwrite($handle, $content);
fclose($handle);
if ($written !== strlen($content)) { fwrite(STDERR, "Écriture incomplète : supprimer le fichier et recommencer.\n"); exit(1); }
echo "Fichier créé : $destination\nMot de passe opérateur (à conserver dans votre gestionnaire de mots de passe) : $password\nActivation valable 24 heures. Aucun accès à la base effectué.\n";
