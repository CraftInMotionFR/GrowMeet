CREATE TABLE breed (
    id_breed INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE dog (
    id_dog INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    birth_date DATE NULL,
    id_user INT NOT NULL,
    id_breed INT NULL,
    FOREIGN KEY (id_user) REFERENCES users (id_user),
    FOREIGN KEY (id_breed) REFERENCES breed (id_breed)
);

INSERT INTO breed (name) VALUES ('Golden Retriever'), ('Labrador Retriever'), ('Border Collie'), ('Berger Allemand'), ('Autre');
