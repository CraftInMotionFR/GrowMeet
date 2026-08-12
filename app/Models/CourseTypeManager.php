<?php

class CourseTypeManager extends Manager {
    public function findAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM course_type ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM course_type WHERE id_course_type = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // $data = ['name', 'description', 'min_age', 'max_age']
    public function create(array $data, int $adminId): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO course_type (name, description, min_age, max_age, id_user)
            VALUES (:name, :description, :min_age, :max_age, :id_user)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'min_age' => $data['min_age'],
            'max_age' => $data['max_age'] ?: null,
            'id_user' => $adminId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->pdo->prepare('
            UPDATE course_type
            SET name = :name, description = :description, min_age = :min_age, max_age = :max_age
            WHERE id_course_type = :id
        ');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'min_age' => $data['min_age'],
            'max_age' => $data['max_age'] ?: null,
            'id' => $id,
        ]);
    }

    // Peut lever PDOException (FK RESTRICT) si des séances existent encore pour ce type
    public function delete(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM course_type WHERE id_course_type = :id');
        $stmt->execute(['id' => $id]);
    }

    // Convertit les dates seuils stockées en base en texte lisible (même logique que CourseManager::formatAgeRange)
    public function formatAgeRange(string $minAge, ?string $maxAge): string {
        $now = new DateTime();
        $upperMonths = $this->monthsSince($minAge, $now);
        $lowerMonths = $maxAge ? $this->monthsSince($maxAge, $now) : 0;

        $noUpperLimit = $upperMonths >= 96;

        if ($noUpperLimit && $lowerMonths <= 0) return 'Tous âges';
        if ($noUpperLimit) return 'À partir de ' . $lowerMonths . ' mois';
        return $lowerMonths . ' à ' . $upperMonths . ' mois';
    }

    private function monthsSince(string $date, DateTime $now): int {
        $diff = (new DateTime($date))->diff($now);
        return $diff->y * 12 + $diff->m;
    }
}
