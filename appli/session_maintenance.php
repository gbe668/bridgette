<?php
// One persistent marker per shared sessions table, independent of clubs and host aliases.
function bridgetteMonthlySessionGc($dbh, string $database, ?int $now = null): ?int
{
    $now = $now ?? time();
    $month = gmdate('Y-m', $now);
    $marker = 'gc_monthly';
    $read = $dbh->prepare('SELECT data FROM sessions WHERE id = ?');
    if (!$read->execute([$marker])) throw new RuntimeException('GC marker read failed');
    if ($read->fetchColumn() === $month) return null;

    $lockName = 'bridgette-supervisor-'.substr(hash('sha256', $database), 0, 32);
    $lock = $dbh->prepare('SELECT GET_LOCK(?, 0)');
    if (!$lock->execute([$lockName]) || (int)$lock->fetchColumn() !== 1) return null;
    try {
        // Another request may have completed the purge after our first read.
        if (!$read->execute([$marker])) throw new RuntimeException('GC marker read failed');
        if ($read->fetchColumn() === $month) return null;
        $delete = $dbh->prepare('DELETE FROM sessions WHERE access < ? AND id <> ?');
        if (!$delete->execute([$now - 100 * 86400, $marker])) throw new RuntimeException('GC delete failed');
        $count = $delete->rowCount();
        // Record success only after DELETE; failures are safe to retry at a later visit.
        $save = $dbh->prepare('REPLACE INTO sessions (id, access, data) VALUES (?, ?, ?)');
        if (!$save->execute([$marker, $now, $month])) throw new RuntimeException('GC marker write failed');
        return $count;
    } finally {
        $release = $dbh->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}
