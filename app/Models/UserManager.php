<?php

class UserManager extends Manager {
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id_user = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Crée un utilisateur + son entrée dans la table member, retourne l'id_user généré
    public function createMember(array $data): int {
        // 1. Insertion dans users
        $stmt = $this->pdo->prepare('
            INSERT INTO users (first_name, last_name, email, phone, password_hash)
            VALUES (:first_name, :last_name, :email, :phone, :password_hash)
        ');
        $stmt->execute([
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => $data['password'], // déjà hashé en session
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        // 2. Insertion dans member (héritage XT)
        $stmt = $this->pdo->prepare('INSERT INTO member (id_user) VALUES (:id)');
        $stmt->execute(['id' => $userId]);

        return $userId;
    }

    // Crée un utilisateur + son entrée dans la table coach, retourne l'id_user généré
    public function createCoach(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (first_name, last_name, email, password_hash)
            VALUES (:first_name, :last_name, :email, :password_hash)
        ');
        $stmt->execute([
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'email' => $data['email'],
            'password_hash' => $data['password'],
        ]);

        $userId = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare('INSERT INTO coach (id_user) VALUES (:id)');
        $stmt->execute(['id' => $userId]);

        return $userId;
    }

    // Vérifie email + mot de passe, retourne le tableau utilisateur si ok, null sinon
    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    // Retourne 'member', 'coach', ou 'administrator' selon la table de spécialisation
    public function getRole(int $userId): string {
        foreach (['administrator', 'coach', 'member'] as $role) {
            $stmt = $this->pdo->prepare("SELECT id_user FROM {$role} WHERE id_user = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            if ($stmt->fetch()) {
                return $role;
            }
        }
        return 'member'; // fallback
    }
}