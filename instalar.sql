CREATE DATABASE IF NOT EXISTS relevamiento_montadas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE relevamiento_montadas;
CREATE TABLE IF NOT EXISTS montadas_estado (
 id TINYINT NOT NULL PRIMARY KEY,
 revision BIGINT NOT NULL DEFAULT 0,
 vocabulary LONGTEXT NOT NULL,
 actualizado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO montadas_estado (id,revision,vocabulary) VALUES (1,0,'{}');
CREATE TABLE IF NOT EXISTS montadas_fichas (
 identificador VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
 posicion INT NOT NULL,
 contenido LONGTEXT NOT NULL
) ENGINE=InnoDB;
