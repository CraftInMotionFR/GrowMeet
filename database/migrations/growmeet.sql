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

