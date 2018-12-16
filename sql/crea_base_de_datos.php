<?php

include "../secrets.php";
$database = "rocola";
$conn = new mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database);


if(isset($_POST['recrear'])) {
	
	mysqli_autocommit($conn, FALSE);

	mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS rocola");
	mysqli_query($conn, "USE rocola");
	
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
	
	mysqli_query($conn, "DROP TABLE canciones_local");
	mysqli_query($conn, "DROP TABLE colecciones_local");
	mysqli_query($conn, "DROP TABLE canciones_coleccionadas_local");
	
	// Recrea las tablas
	mysqli_query($conn,
	"CREATE TABLE IF NOT EXISTS canciones_local (
	    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	    titulo VARCHAR (200),
	    duracion VARCHAR(8),
	    song_path VARCHAR (200),
	    artista VARCHAR (200),
	    album VARCHAR (200),
	    coleccion_id INT UNSIGNED,
	    last_played TIMESTAMP NULL DEFAULT NULL,
	    FOREIGN KEY (coleccion_id) REFERENCES colecciones(id) ON DELETE CASCADE
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
	
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
	
	
	if(mysqli_commit($conn)) {
		echo "<h3>Se ha re-creado la base de datos.</h3>";
	} else {
		echo "<h3>Hubo problemas al re-crear la base de datos.</h3>";
	}
} else {
?>

<form action="" method="post">
	<p>¿Re-crear la base de datos?</p>
	<input type="submit" name="recrear" value="Aceptar">
</form>

<?php
}


?>