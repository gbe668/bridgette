<?php
// Club identifier: exactly three ASCII letters/digits followed by an underscore.
function bridgetteValidClubPrefix($prefix): bool
{
    return is_string($prefix) && preg_match('/\A[A-Za-z0-9]{3}_\z/D', $prefix) === 1;
}
