<?php
// Shared by web, Android and Joomla through bridgette_bdd.php.
function clubParametersDefaults(): array {
    return [
        'mailcopie' => '', 'dureedonne' => 7.5, 'dureediagrammes' => 2, 'dureeinitiale' => 3,
        'maxt' => 10, 'maxbj' => 10, 'maxdel' => 10, 'maxw' => 700, 'maxw2' => 300,
        'sizpix' => 50, 'checkin' => 0, 'checkuser' => 0, 'affimp' => 0,
        'affposprov' => 0, 'affprov' => 0, 'back241' => 0, 'avancem' => 0, 'avanceh' => 1,
        'affperf' => 0, 'nbjperf' => 10, 'nbmperf' => 3, 'minperf' => 15,
        'param1' => 0,
        'opendays' => array_fill_keys(['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'], 1),
        'maxweeks' => 4,
    ];
}

function clubParametersNumber($value, string $key, $min, $max, bool $integer = true) {
    // Historic radio buttons and opening days are JSON strings "0"/"1".
    if (!(is_int($value) || is_float($value) ||
        (is_string($value) && preg_match('/\A-?[0-9]+(?:\.[0-9]+)?\z/D', $value)))) {
        throw new InvalidArgumentException('Paramètre incorrect : '.$key.'.');
    }
    $number = (float)$value;
    if (!is_finite($number) || $number < $min || $number > $max ||
        ($integer && floor($number) !== $number)) {
        throw new InvalidArgumentException('Valeur hors limites : '.$key.'.');
    }
    return $integer ? (int)$number : $number;
}

function clubParametersDecode($json): array {
    if (!is_string($json) || strlen($json) > 16384) {
        throw new InvalidArgumentException('Paramètres absents ou trop volumineux.');
    }
    try { $object = json_decode($json, false, 8, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { throw new InvalidArgumentException('JSON des paramètres incorrect.'); }
    if (!$object instanceof stdClass) throw new InvalidArgumentException('Objet de paramètres attendu.');
    $values = get_object_vars($object);
    // Obsolete unused option: accept old files/forms, never expose or save it again.
    unset($values['param2']);
    $result = clubParametersDefaults();
    // Preserve the historical fallback for existing files, distinct from a new club.
    $result['affposprov'] = 1;
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $result)) throw new InvalidArgumentException('Paramètre inconnu.');
    }
    foreach (['checkin', 'checkuser'] as $key) {
        if (!array_key_exists($key, $values)) throw new InvalidArgumentException('Paramètre de sécurité manquant : '.$key.'.');
    }
    $ranges = [
        'dureedonne' => [5,15,false], 'dureediagrammes' => [0,5,false], 'dureeinitiale' => [0,5,false],
        'maxt' => [1,100], 'maxbj' => [1,100], 'maxdel' => [1,100],
        'maxw' => [300,2147483647], 'maxw2' => [100,2147483647], 'sizpix' => [20,80],
        'checkuser' => [0,2], 'nbjperf' => [3,100], 'nbmperf' => [1,12], 'minperf' => [4,100],
        'maxweeks' => [1,13,false], 'param1' => [0,2],
    ];
    foreach (['checkin','affimp','affposprov','affprov','back241','avancem','avanceh','affperf'] as $key) $ranges[$key] = [0,1];
    foreach ($ranges as $key => $range) {
        if (array_key_exists($key, $values)) $result[$key] = clubParametersNumber($values[$key], $key, $range[0], $range[1], $range[2] ?? true);
    }
    if (array_key_exists('mailcopie', $values)) {
        $mail = $values['mailcopie'];
        if (!is_string($mail) || strlen($mail) > 254 ||
            ($mail !== '' && (!filter_var($mail, FILTER_VALIDATE_EMAIL) || preg_match('/[\x00-\x20\x7f<>"\x27]/', $mail)))) {
            throw new InvalidArgumentException('Adresse mail de copie incorrecte.');
        }
        $result['mailcopie'] = $mail;
    }
    if (array_key_exists('opendays', $values)) {
        if (!$values['opendays'] instanceof stdClass) throw new InvalidArgumentException('Jours d’ouverture incorrects.');
        $days = get_object_vars($values['opendays']);
        if (count($days) !== 7 || array_diff_key($days, $result['opendays'])) throw new InvalidArgumentException('Les sept jours sont requis.');
        foreach ($days as $day => $value) $result['opendays'][$day] = clubParametersNumber($value, $day, 0, 1);
    }
    return $result;
}

function clubParametersLoad(string $path): array {
    if (!file_exists($path)) return clubParametersDefaults();
    $json = @file_get_contents($path, false, null, 0, 16385);
    if ($json === false) throw new RuntimeException('Lecture des paramètres impossible.');
    return clubParametersDecode($json);
}

function clubParametersSave(string $path, $json): void {
    // Validate completely before touching the destination. Never accept a client path.
    $data = json_encode(clubParametersDecode($json), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $temporary = dirname($path).'/.params-'.bin2hex(random_bytes(16)).'.tmp';
    $handle = @fopen($temporary, 'x+b');
    if ($handle === false) throw new RuntimeException('Création du fichier temporaire impossible.');
    try {
        $mode = file_exists($path) ? (@fileperms($path) & 0777) : 0640;
        if (!@chmod($temporary, $mode)) throw new RuntimeException('Permissions du fichier impossibles.');
        $offset = 0;
        while ($offset < strlen($data)) {
            $written = fwrite($handle, substr($data, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('Écriture des paramètres impossible.');
            $offset += $written;
        }
        if (!fflush($handle)) throw new RuntimeException('Écriture des paramètres impossible.');
        fclose($handle);
        $handle = null;
        // Same directory/filesystem: readers see either the old or the complete new file.
        if (!@rename($temporary, $path)) throw new RuntimeException('Remplacement des paramètres impossible.');
        clearstatcache(true, $path);
    } finally {
        if (is_resource($handle)) fclose($handle);
        if (file_exists($temporary)) @unlink($temporary);
    }
}
