CREATE DATABASE IF NOT EXISTS growmeet
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE growmeet;

CREATE TABLE users (
    id_user       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    last_name     VARCHAR(100)    NOT NULL,
    first_name    VARCHAR(100)    NOT NULL,
    address       VARCHAR(255)        NULL,
    email         VARCHAR(255)    NOT NULL,
    phone         VARCHAR(20)         NULL,
    password_hash VARCHAR(255)    NOT NULL,
    created_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB;

CREATE TABLE coach (
    id_user       INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_user),
    CONSTRAINT fk_coach_user
        FOREIGN KEY (id_user) REFERENCES users (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE administrator (
    id_user       INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_user),
    CONSTRAINT fk_administrator_user
        FOREIGN KEY (id_user) REFERENCES users (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE member (
    id_user       INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_user),
    CONSTRAINT fk_member_user
        FOREIGN KEY (id_user) REFERENCES users (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE breed (
    id_breed      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    PRIMARY KEY (id_breed)
) ENGINE=InnoDB;

CREATE TABLE dog (
    id_dog          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)    NOT NULL,
    gender          ENUM('male','female','unknown')
                                    NOT NULL DEFAULT 'unknown',
    birth_date      DATE                NULL,
    weight          DECIMAL(5,2)        NULL COMMENT 'kg',
    medical_record  TEXT                NULL,
    image           VARCHAR(255)        NULL,
    id_user         INT UNSIGNED    NOT NULL COMMENT 'FK → member',
    id_breed        INT UNSIGNED        NULL,
    PRIMARY KEY (id_dog),
    CONSTRAINT fk_dog_member
        FOREIGN KEY (id_user) REFERENCES member (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dog_breed
        FOREIGN KEY (id_breed) REFERENCES breed (id_breed)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_type (
    id_course_type  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150)    NOT NULL,
    description     TEXT                NULL,
    min_age  DATE   NOT NULL,
    max_age  DATE   NULL COMMENT 'maximum age (NULL = no limit)',
    id_user         INT UNSIGNED    NOT NULL COMMENT 'FK → administrator who created it',
    PRIMARY KEY (id_course_type),
    CONSTRAINT fk_course_type_admin
        FOREIGN KEY (id_user) REFERENCES administrator (id_user)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE skill (
    id_skill      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name          VARCHAR(150)    NOT NULL,
    level         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=beginner … 5=expert',
    PRIMARY KEY (id_skill)
) ENGINE=InnoDB;

CREATE TABLE coach_course_type (
    id_user         INT UNSIGNED    NOT NULL COMMENT 'FK → coach',
    id_course_type  INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_user, id_course_type),
    CONSTRAINT fk_cct_coach
        FOREIGN KEY (id_user) REFERENCES coach (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cct_course_type
        FOREIGN KEY (id_course_type) REFERENCES course_type (id_course_type)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_type_skill (
    id_course_type  INT UNSIGNED    NOT NULL,
    id_skill        INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_course_type, id_skill),
    CONSTRAINT fk_cts_course_type
        FOREIGN KEY (id_course_type) REFERENCES course_type (id_course_type)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cts_skill
        FOREIGN KEY (id_skill) REFERENCES skill (id_skill)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE dog_skill (
    id_dog        INT UNSIGNED    NOT NULL,
    id_skill      INT UNSIGNED    NOT NULL,
    acquired_at   DATE                NULL,
    PRIMARY KEY (id_dog, id_skill),
    CONSTRAINT fk_ds_dog
        FOREIGN KEY (id_dog) REFERENCES dog (id_dog)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ds_skill
        FOREIGN KEY (id_skill) REFERENCES skill (id_skill)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course (
    id_course       INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150)    NOT NULL,
    date            DATE            NOT NULL,
    start_time      TIME            NOT NULL,
    end_time        TIME            NOT NULL,
    min_participants TINYINT UNSIGNED NOT NULL DEFAULT 1,
    max_participants TINYINT UNSIGNED NOT NULL DEFAULT 10,
    status          ENUM('open','full','cancelled','completed')
                                    NOT NULL DEFAULT 'open',
    id_user         INT UNSIGNED    NOT NULL COMMENT 'FK → coach',
    id_course_type  INT UNSIGNED    NOT NULL,
    PRIMARY KEY (id_course),
    CONSTRAINT fk_course_coach
        FOREIGN KEY (id_user) REFERENCES coach (id_user)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_course_type
        FOREIGN KEY (id_course_type) REFERENCES course_type (id_course_type)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE booking (
    id_dog          INT UNSIGNED    NOT NULL,
    id_course       INT UNSIGNED    NOT NULL,
    booking_date    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('pending','confirmed','cancelled','waiting')
                                    NOT NULL DEFAULT 'pending',
    review          TEXT                NULL,
    PRIMARY KEY (id_dog, id_course),
    CONSTRAINT fk_booking_dog
        FOREIGN KEY (id_dog) REFERENCES dog (id_dog)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_booking_course
        FOREIGN KEY (id_course) REFERENCES course (id_course)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE message (
    id_message    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    content       TEXT            NOT NULL,
    sent_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_sender     INT UNSIGNED    NOT NULL COMMENT 'FK → user (envoyer)',
    id_receiver   INT UNSIGNED    NOT NULL COMMENT 'FK → user (recevoir)',
    PRIMARY KEY (id_message),
    CONSTRAINT fk_message_sender
        FOREIGN KEY (id_sender) REFERENCES users (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_receiver
        FOREIGN KEY (id_receiver) REFERENCES users (id_user)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_course_date         ON course  (date);
CREATE INDEX idx_course_status       ON course  (status);
CREATE INDEX idx_booking_status      ON booking (status);
CREATE INDEX idx_dog_owner           ON dog     (id_user);
CREATE INDEX idx_message_sender      ON message (id_sender);
CREATE INDEX idx_message_receiver    ON message (id_receiver);

INSERT INTO breed (name) VALUES
    ('Golden Retriever'),
    ('Labrador Retriever'),
    ('Border Collie'),
    ('Berger Allemand'),
    ('Chihuahua'),
    ('Welsh Corgi'),
    ('Berger Australien'),
    ('Jack Russel Terrier'),
    ('Cavalier King Charles'),
    ('Caniche'),
    ('Beagle'),
    ('Husky Sibérien'),
    ('Bulldog Français'),
    ('Bulldog Anglais'),
    ('Boxer'),
    ('Shih Tzu'),
    ('Carlin'),
    ('Cocker Spaniel'),
    ('Teckel'),
    ('Dalmatien'),
    ('Rottweiler'),
    ('Staffordshire Bull Terrier'),
    ('Dogue Allemand'),
    ('Bull Terrier'),
    ('Cane Corso'),
    ('Dobermann'),
    ('Bouvier Bernois'),
    ('Bichon'),
    ('Épagneul'),
    ('Braque de Weimar'),
    ('Beauceron'),
    ('Akita Inu'),
    ('Shiba Inu'),
    ('Lévrier'),
    ('Malinois'),
    ('Spitz'),
    ('Yorkshire Terrier'),
    ('Autre');

-- Compte administrateur de démo (email: admin@growmeet.com / mot de passe: Admin1234!)
INSERT INTO users (last_name, first_name, email, password_hash) VALUES
    ('Admin', 'GrowMeet', 'admin@growmeet.com', '$2y$10$zV1/LC/LUQkJzdRE1XFXd.xHo/u1UkBaHoYXsbAXcwcoHubEImp2.');
INSERT INTO administrator (id_user) VALUES (LAST_INSERT_ID());