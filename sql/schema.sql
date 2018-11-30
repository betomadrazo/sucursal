DROP TABLE canciones_local;
DROP TABLE colecciones_local;
DROP TABLE canciones_coleccionadas_local;

CREATE DATABASE IF NOT EXISTS rocola;

USE rocola;

CREATE TABLE IF NOT EXISTS canciones_local (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR (200),
    duracion VARCHAR(8),
    song_path VARCHAR (200),
    artista VARCHAR (200),
    album VARCHAR (200),
    coleccion_id INT UNSIGNED,
    last_played TIME,
    FOREIGN KEY (coleccion_id) REFERENCES colecciones(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS colecciones_local (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hora_inicio TIME DEFAULT '8:00:00',
    hora_fin TIME DEFAULT '23:00:00',
    activa BIT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS canciones_coleccionadas_local (
    coleccion_id INT UNSIGNED,
    cancion_id INT UNSIGNED,
    FOREIGN KEY (coleccion_id) REFERENCES colecciones_local(id) ON DELETE CASCADE,
    FOREIGN KEY (cancion_id) REFERENCES canciones_local(id) ON DELETE CASCADE,
    PRIMARY KEY(coleccion_id, cancion_id)
);