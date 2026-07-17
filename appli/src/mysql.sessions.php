<?php

declare(strict_types=1);

class Session implements SessionHandlerInterface
{
    private readonly Database $db;

    public function __construct()
    {
        $this->db = new Database();

        session_set_save_handler($this, true);
        session_start();
    }

    public function open(string $path, string $name): bool
    {
        return isset($this->db);
    }

    public function close(): bool
    {
        return $this->db->close();
    }

    public function read(string $id): string|false
    {
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
