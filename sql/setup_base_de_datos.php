<?php

include "../secrets.php";
$database = "rocola";
$conn = new mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database);


mysqli_autocommit($conn, FALSE);


mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS rocola");

mysqli_query($conn, "USE rocola");

mysqli_query($conn, 
	"CREATE TABLE IF NOT EXISTS canciones_local (
    	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    	titulo VARCHAR (200),
    	duracion VARCHAR(8),
    	song_path VARCHAR (200),
    	artista VARCHAR (200),
    	album VARCHAR (200),
    	coleccion_id INT UNSIGNED
	)"
);

mysqli_query($conn, 
	"CREATE TABLE IF NOT EXISTS colecciones_local (
	    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	    hora_inicio TIME DEFAULT '8:00:00',
	    hora_fin TIME DEFAULT '23:00:00',
	    activa BIT DEFAULT 0
	)"
);

mysqli_query($conn, 
	"CREATE TABLE IF NOT EXISTS canciones_coleccionadas_local (
	    coleccion_id INT UNSIGNED,
	    cancion_id INT UNSIGNED,
	    FOREIGN KEY (coleccion_id) REFERENCES colecciones_local(id) ON DELETE CASCADE,
	    FOREIGN KEY (cancion_id) REFERENCES canciones_local(id) ON DELETE CASCADE,
	    PRIMARY KEY(coleccion_id, cancion_id)
	)"
);


if(mysqli_commit($conn)) echo "<h4>Se ha creado la base de datos.</h4>";
else echo "Hubo un error, no se ha podido crear la base de datos.";


?>