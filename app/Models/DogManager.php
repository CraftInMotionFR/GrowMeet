<?php

class DogManager extends Manager {
    // Retourne l'id_breed correspondant au nom, ou le crée si inexistant
    private function resolveBreed(string $breedName): int {
        $stmt = $this->pdo->prepare('SELECT id_breed FROM breed WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $breedName]);
        $row = $stmt->fetch();

        if ($row) {
            return (int) $row['id_breed'];
        }

        $stmt = $this->pdo->prepare('INSERT INTO breed (name) VALUES (:name)');
        $stmt->execute(['name' => $breedName]);
        return (int) $this->pdo->lastInsertId();
    }

    // Retourne la liste des races disponibles, triées par nom
    public function findAllBreeds(): array {
        $stmt = $this->pdo->query('SELECT name FROM breed ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Crée un chien associé à un membre, $dogData = ['dog_name', 'dog_breed', 'gender', 'birth_date']
    public function createDog(int $userId, array $dogData): int {
        $idBreed = null;
        if (!empty($dogData['dog_breed'])) {
            $idBreed = $this->resolveBreed(trim($dogData['dog_breed']));
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO dog (name, gender, id_user, id_breed, birth_date, image)
            VALUES (:name, :gender, :id_user, :id_breed, :birth_date, :image)
        ');
        $stmt->execute([
            'name' => $dogData['dog_name'],
            'gender' => $dogData['gender'] ?? 'unknown',
            'id_user' => $userId,
            'id_breed' => $idBreed,
            'birth_date' => $dogData['birth_date'] ?: null,
            'image' => $dogData['image'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // Retourne un chien s'il appartient bien au membre, sinon null
    public function findById(int $dogId, int $userId): ?array {
        $stmt = $this->pdo->prepare('
            SELECT d.id_dog, d.name, d.gender, d.birth_date, d.image,
                b.name AS breed
            FROM dog d
            LEFT JOIN breed b ON b.id_breed = d.id_breed
            WHERE d.id_dog = :dog AND d.id_user = :user
        ');
        $stmt->execute(['dog' => $dogId, 'user' => $userId]);

        return $stmt->fetch() ?: null;
    }

    // Met à jour un chien du membre, $dogData = ['dog_name', 'dog_breed', 'gender', 'birth_date', 'image']
    public function updateDog(int $dogId, int $userId, array $dogData): void {
        $idBreed = !empty($dogData['dog_breed']) ? $this->resolveBreed(trim($dogData['dog_breed'])) : null;

        $stmt = $this->pdo->prepare('
            UPDATE dog
            SET name = :name, gender = :gender, id_breed = :id_breed,
                birth_date = :birth_date, image = :image
            WHERE id_dog = :id_dog AND id_user = :id_user
        ');
        $stmt->execute([
            'name' => $dogData['dog_name'],
            'gender' => $dogData['gender'],
            'id_breed' => $idBreed,
            'birth_date' => $dogData['birth_date'] ?: null,
            'image' => $dogData['image'] ?? null,
            'id_dog' => $dogId,
            'id_user' => $userId,
        ]);
    }

    // Supprime un chien du membre ; ses réservations sont supprimées en cascade. Retourne false si le chien n'est pas au membre
    public function deleteDog(int $dogId, int $userId): bool {
        $stmt = $this->pdo->prepare('DELETE FROM dog WHERE id_dog = :dog AND id_user = :user');
        $stmt->execute(['dog' => $dogId, 'user' => $userId]);

        return $stmt->rowCount() > 0;
    }

    // Retourne le premier chien d'un membre avec sa race et son âge calculé
    public function findByUser(int $userId): ?array {
        $stmt = $this->pdo->prepare('
            SELECT d.id_dog, d.name, d.gender, d.birth_date, d.image,
                b.name AS breed
            FROM dog d
            LEFT JOIN breed b ON b.id_breed = d.id_breed
            WHERE d.id_user = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $userId]);
        $dog = $stmt->fetch();

        if (!$dog) return null;

        // Calcul de l'âge lisible depuis birth_date
        $dog['age'] = $this->formatAge($dog['birth_date']);

        return $dog;
    }

    // Retourne tous les chiens d'un membre (race + âge calculé), pour l'affichage en liste
    public function findAllByUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT d.id_dog, d.name, d.gender, d.birth_date, d.image,
                b.name AS breed,
                (SELECT COUNT(*) FROM booking bk
                 WHERE bk.id_dog = d.id_dog AND bk.status != \'cancelled\') AS bookings_count
            FROM dog d
            LEFT JOIN breed b ON b.id_breed = d.id_breed
            WHERE d.id_user = :id
            ORDER BY d.id_dog ASC
        ');
        $stmt->execute(['id' => $userId]);
        $dogs = $stmt->fetchAll();

        foreach ($dogs as &$dog) {
            $dog['age'] = $this->formatAge($dog['birth_date']);
        }

        return $dogs;
    }

    private function formatAge(?string $birthDate): string {
        if (!$birthDate) return 'Âge inconnu';

        $birth = new DateTime($birthDate);
        $now = new DateTime();
        $diff = $now->diff($birth);

        if ($diff->y >= 1) {
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        }

        return $diff->m . ' mois';
    }
}