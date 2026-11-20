<?php
// Audit executable interpolated SQL strings, excluding comments and disabled copies.
$allowed = ['$x_donnes', '$table', '$column', '$col_j', '$col_partenaire', '$groupe', '$filtrage', '$selordre', '$a', '$b'];
$checked = 0;
$emailProjectionChecked = false;
foreach (glob(__DIR__.'/../appli/*.php') as $file) {
    $source = file_get_contents($file);
    if (str_contains(substr($source, 0, 250), 'http_response_code(403)')) continue;
    $inside = false; $text = ''; $variables = [];
    foreach (token_get_all($source) as $token) {
        if ($token === '"') {
            if (!$inside) { $inside = true; $text = ''; $variables = []; continue; }
            $inside = false;
            if (!preg_match('/\A\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE)\b/i', $text)) continue;
            $checked++;
            foreach ($variables as $variable) {
                if ($variable === '$champEmail' && basename($file) === 'bridgette_bdd.php') {
                    // Permit only the reviewed internal boolean projection, not arbitrary input.
                    $code = '';
                    foreach (token_get_all($source) as $part) {
                        if (is_array($part) && in_array($part[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)) continue;
                        $code .= is_array($part) ? $part[1] : $part;
                    }
                    if (substr_count($code, '$champEmail=') !== 1 ||
                        !str_contains($code, "$"."champEmail=$"."avecEmail?', email':'';")) {
                        throw new RuntimeException('Email projection must remain a fixed internal choice');
                    }
                    $emailProjectionChecked = true;
                    continue;
                }
                if (!str_starts_with($variable, '$tab_') && !in_array($variable, $allowed, true)) {
                    throw new RuntimeException(basename($file).': unexpected SQL interpolation '.$variable);
                }
            }
        } elseif ($inside) {
            $text .= is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_VARIABLE) $variables[] = $token[1];
        }
    }
}
if (!$emailProjectionChecked) throw new RuntimeException('Email projection audit not exercised');
echo "$checked executable SQL templates: only reviewed table/column/sort fragments remain interpolated.\n";
