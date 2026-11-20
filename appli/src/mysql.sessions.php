<?php

declare(strict_types=1);

/*
 * Version modifiée de PHP-MySQL-Sessions par Dominick Lee.
 * Source : https://github.com/dominicklee/PHP-MySQL-Sessions
 * Attribution d’origine : code dérivé de « Essential PHP Security »
 * de Chris Shiflett (nom écrit « Chriss Shiflett » dans le fichier amont).
 * Licence : GNU Affero General Public License, version 3 (voir ../COPYING.AGPL).
 * Adaptations par Bruno Coiffier, notamment avec l’aide de l’IA :
 * compatibilité PHP, cycle de connexion, cookies et maintenance des sessions.
 * Mention des adaptations mise à jour le 20 novembre 2026.
 */

class Session implements SessionHandlerInterface
{
    private ?Database $db = null;

    public function __construct()
    {
        // Database cleanup is scheduled monthly by configuration.php, not randomly by PHP.
        ini_set('session.gc_probability', '0');
        ini_set('session.gc_maxlifetime', (string)(100 * 86400));
        session_set_save_handler($this, true);
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
        session_start();
    }

    public function open(string $path, string $name): bool
    {
        // PHP closes and reopens the handler when regenerating a session ID.
        // A Database object whose close() ran no longer owns a PDO connection.
        $this->db ??= new Database();
        return true;
    }

    public function close(): bool
    {
        $closed = $this->db?->close() ?? true;
        $this->db = null;
        return $closed;
    }

    public function read(string $id): string|false
    {
        if (str_starts_with($id, 'adr_') || str_starts_with($id, 'apk_') || str_starts_with($id, 'gc_') || str_starts_with($id, 'pwd_') || str_starts_with($id, 'rlm_')) return '';
        $this->db->query('SELECT data FROM sessions WHERE id = :id');
        $this->db->bind(':id', $id);

        if ($this->db->execute() && $this->db->rowCount() > 0) {
            $row = $this->db->single();
            return $row['data'] ?? '';
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        if (str_starts_with($id, 'adr_') || str_starts_with($id, 'apk_') || str_starts_with($id, 'gc_') || str_starts_with($id, 'pwd_') || str_starts_with($id, 'rlm_')) return false;
        $access = time();

        $this->db->query(
            'REPLACE INTO sessions (id, access, data)
             VALUES (:id, :access, :data)'
        );

        $this->db->bind(':id', $id);
        $this->db->bind(':access', $access);
        $this->db->bind(':data', $data);

        return $this->db->execute();
    }

    public function destroy(string $id): bool
    {
        if (str_starts_with($id, 'adr_') || str_starts_with($id, 'apk_') || str_starts_with($id, 'gc_') || str_starts_with($id, 'pwd_') || str_starts_with($id, 'rlm_')) return true;
        $this->db->query('DELETE FROM sessions WHERE id = :id');
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    public function gc(int $max_lifetime): int|false
    {
        $old = time() - $max_lifetime;

        $this->db->query('DELETE FROM sessions WHERE access < :old');
        $this->db->bind(':old', $old);

        if (!$this->db->execute()) {
            return false;
        }

        return $this->db->rowCount();
    }

    /**
     * Appelée par PHP lorsqu'il veut simplement prolonger
     * la durée de vie d'une session sans modifier son contenu.
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        if (str_starts_with($id, 'adr_') || str_starts_with($id, 'apk_') || str_starts_with($id, 'gc_') || str_starts_with($id, 'pwd_') || str_starts_with($id, 'rlm_')) return false;
        $this->db->query(
            'UPDATE sessions
             SET access = :access
             WHERE id = :id'
        );

        $this->db->bind(':access', time());
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }
}

