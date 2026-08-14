<?php

class CoachManager extends Manager {
    public function findAll(): array {
        $stmt = $this->pdo->query('
            SELECT u.id_user, u.first_name, u.last_name, u.email
            FROM coach c
            JOIN users u ON u.id_user = c.id_user
            ORDER BY u.last_name ASC, u.first_name ASC
        ');
        return $stmt->fetchAll();
    }

    // Peut lever PDOException (FK RESTRICT) si des séances lui sont encore assignées
    public function delete(int $userId): void {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id_user = :id');
        $stmt->execute(['id' => $userId]);
    }
}
