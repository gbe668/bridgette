<?php
// Versioned prehash: bcrypt's 72-byte limit must never truncate a password.
// b2: + a 60-character bcrypt hash fits existing VARCHAR(64) columns.
function bridgettePasswordInput($password, $allowEmpty = false) {
    return is_string($password) && strlen($password) <= 1024 && ($allowEmpty || $password !== '');
}
function bridgettePasswordMaterial($password) {
    return base64_encode(hash('sha384', $password, true));
}
function bridgettePasswordHash($password) {
    if (!bridgettePasswordInput($password)) throw new InvalidArgumentException('Mot de passe vide ou trop long.');
    return 'b2:'.password_hash(bridgettePasswordMaterial($password), PASSWORD_BCRYPT, ['cost' => 12]);
}
function bridgettePasswordVerify($password, $stored) {
    if (!bridgettePasswordInput($password, true) || !is_string($stored)) return false;
    if (str_starts_with($stored, 'b2:')) return password_verify(bridgettePasswordMaterial($password), substr($stored, 3));
    // Only legacy hashes use the historic input transformations.
    if (preg_match('/\A[a-f0-9]{64}\z/D', $stored)) {
        return hash_equals($stored, hash('sha256', htmlspecialchars(stripslashes($password))));
    }
    return false;
}
function bridgettePasswordNeedsUpgrade($stored) {
    return !str_starts_with($stored, 'b2:') || password_needs_rehash(substr($stored, 3), PASSWORD_BCRYPT, ['cost' => 12]);
}
function bridgettePasswordAccount($db, $table, $column, $value, $password, $role = null) {
    if (!in_array($column, ['email', 'pseudo'], true) || !bridgettePasswordInput($password, true)) return null;
    $where = $column === 'email' ? ' AND datesupp = 0' : '';
    $rows = securityQuery($db, "SELECT * FROM `$table` WHERE `$column` = ?".$where, [$value])->fetchAll(PDO::FETCH_ASSOC);
    // Do not authenticate an ambiguous identity, even if just one password matches.
    if (count($rows) !== 1) return null;
    $row = $rows[0];
    if (($role !== null && ($row['droits'] ?? null) !== $role) || !bridgettePasswordVerify($password, $row['password'])) return null;
    if (bridgettePasswordNeedsUpgrade($row['password']) && $password !== '') {
        $hash = bridgettePasswordHash($password);
        $args = [$hash, $row['id'], $row['password'], $value];
        $roleWhere = $role === null ? '' : ' AND droits = ?';
        if ($role !== null) $args[] = $role;
        $changed = securityQuery($db, "UPDATE `$table` SET password = ? WHERE id = ? AND BINARY password = ? AND `$column` = ?".$where.$roleWhere, $args);
        if ($changed->rowCount() !== 1) return null; // Concurrent change: require a fresh login.
        $row['password'] = $hash;
    }
    return $row;
}
