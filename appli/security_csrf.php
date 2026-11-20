<?php
// Browser mutations use a session secret scoped to the selected club.
function securityCsrfToken(): string {
    $club = $_SESSION['prefix'] ?? '';
    if (!isset($_SESSION['csrf']) || ($_SESSION['csrf']['club'] ?? null) !== $club) {
        $_SESSION['csrf'] = ['club' => $club, 'token' => bin2hex(random_bytes(32))];
    }
    return $_SESSION['csrf']['token'];
}

function securityCsrfRequire(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        securityDeny('Cette action nécessite un envoi POST. Rechargez la page du club.', 405);
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);
    $saved = $_SESSION['csrf'] ?? [];
    if (!is_string($token) || !is_string($saved['token'] ?? null) ||
        ($saved['club'] ?? null) !== ($_SESSION['prefix'] ?? '') ||
        !hash_equals($saved['token'], $token)) {
        securityDeny('Formulaire expiré ou jeton incorrect. Rechargez la page du club.');
    }
    header('Cache-Control: no-store');
}

function securityCsrfField(): void {
    echo '<input type="hidden" name="_csrf" value="'.securityCsrfToken().'">';
}

function securityCsrfHead(): void {
    echo '<script>window.bridgetteSecurity='.json_encode([
        'token' => securityCsrfToken(),
        'base' => securityAppPath(''),
        'routes' => securityMutationRoutes(),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
    echo '<script src="'.htmlspecialchars(securityAppPath('js/security-csrf.js'), ENT_QUOTES, 'UTF-8').'"></script>';
}
