CREATE TABLE course (
    id_course INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_participants INT NOT NULL DEFAULT 10
);

INSERT INTO course (name, date, start_time, end_time, max_participants) VALUES
    ('Éducation chiot', '2026-06-06', '10:00:00', '11:00:00', 8),
    ('Obéissance de base', '2026-06-08', '18:00:00', '19:00:00', 10),
    ('Agility débutant', '2026-06-13', '14:00:00', '15:30:00', 6);
