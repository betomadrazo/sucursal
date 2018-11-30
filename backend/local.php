<?php


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('content-type: application/json; charset=utf-8');

include "../secrets.php";

$database = 'rocola';

$protocolo = 'http://';

$conn = new mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database) or die("no se pudo conectar.");

$DEBUG = true;

$web_server = 'www.betomad.com';
$server = ($DEBUG) ? $_SERVER['SERVER_NAME'] : $web_server;

$url = $protocolo.$server.'/rocola/consola/controllers/controller_musica.php';


if(isset($_GET['accion']) && $_GET['accion'] === 'update_db') {
	echo json_encode(updateLocalDB());
}


if(isset($_POST['accion']) && $_POST['accion'] === 'update_colecciones_local') {
	echo json_encode(updateColeccionesLocal($_POST['colecciones'], $_POST['canciones_coleccionadas']));
}

if(isset($_POST['accion']) && $_POST['accion'] === 'update_last_played') {
	playedAt((int)$_POST['cancion_id']);
}


if(isset($_GET['accion']) && $_GET['accion'] === 'get_songs_in_queue') {
	if(isset($_GET['songs'])) {
		echo getSongsInQueue($_GET['songs']);
	} else {
		echo json_encode(array('msg'=>'No hay cola'));
	}
}


if(isset($_GET['accion']) && $_GET['accion'] === 'get_random_song') {
	echo getRandomSong();
}


function updateLocalDB() {
	global $conn, $url;

	// Envía el GET request con curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url.'?accion=get_db');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($ch);

	curl_close($ch);


	// Obtiene la base de datos en formato json
  	$db = json_decode($result, true);

  	print_r($db);

  	// Ahora va a guardar la base en el servidor local
	mysqli_autocommit($conn, FALSE);
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
	mysqli_query($conn, "TRUNCATE TABLE canciones_local") or die(mysqli_error($conn));
	mysqli_query($conn, "ALTER TABLE canciones_local AUTO_INCREMENT = 1") or die(mysqli_error($conn));
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1") or die(mysqli_error($conn));

	foreach($db as $d) {
		$q = "INSERT INTO canciones_local(id, titulo, duracion, artista, album, song_path) 
			  VALUES({$d['id']}, \"{$d['titulo']}\", \"{$d['duracion']}\", \"{$d['artista']}\", \"{$d['album']}\", \"{$d['song_path']}\")";
		mysqli_query($conn, $q) or die(mysqli_error($conn));

		echo $q."\n";
		if(mysqli_query($conn, $q)) {
			echo "si señor\n";
		} else {
			echo "VALES VERGA, PENDEJO\n";
		}
	}

	mysqli_commit($conn);

	// Actualiza las colecciones
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url.'?accion=get_colecciones_para_sucursales');
	// curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	// curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($ch);

	// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	// Obtiene la base de datos en formato json
  	$colecciones = json_decode($result, true);

  	updateColeccionesLocal($colecciones['colecciones'], $colecciones['canciones_coleccionadas']);
}


function updateColeccionesLocal($colecciones, $canciones_coleccionadas) {
	global $conn;

	mysqli_autocommit($conn, FALSE);
	mysqli_query($conn, "TRUNCATE TABLE colecciones_local");
	mysqli_query($conn, "TRUNCATE TABLE canciones_coleccionadas_local");

	foreach($colecciones as $c) {
		$q = "INSERT INTO colecciones_local(id, hora_inicio, hora_fin, activa) 
			  VALUES({$c['id']}, \"{$c['hora_inicio']}\", \"{$c['hora_fin']}\", {$c['activa']})";
		mysqli_query($conn, $q);
	}

	foreach($canciones_coleccionadas as $d) {
		$q = "INSERT INTO canciones_coleccionadas_local(coleccion_id, cancion_id) 
			  VALUES({$d['coleccion_id']},{$d['cancion_id']})";

		mysqli_query($conn, $q);	
	}

	if(mysqli_commit($conn)) {
		return array("colecciones_actualizadas"=>true);
	}

	return array("colecciones_actualizadas"=>false);
}


function getSongsInQueue($data) {
	global $conn;

	$buena_cola = implode(",", $data);

	$q =  "SELECT * FROM canciones_local WHERE id IN(".implode(',', $data).") ORDER BY FIELD(id, {$buena_cola})";

	$res = mysqli_query($conn, $q);

	$canciones = array();
	if(mysqli_num_rows($res)) {
		while($row = mysqli_fetch_assoc($res)) {
			$cancion = array(
				'id'=>$row['id'],
				'titulo'=>($row['titulo']),
				'duracion'=>$row['duracion'],
				'path'=>($row['song_path']),
				'artista'=>($row['artista']),
				'album'=>($row['album'])
			);
			array_push($canciones, $cancion);
		}

		return json_encode($canciones);
	}

	return json_encode(array('queue'=>false));
}


function getRandomSong() {
	global $conn;
	$hora = getHora();
	$hora_pedida = date("H:i", strtotime("-1 hour America/Mexico_City"));

	// La diferencia de horas que deben pasar para que la canción pueda ser tocada nuevamente
	$clausula_horas = "(TIMEDIFF(CURTIME(), last_played) >= '3:00:00' OR last_played IS NULL)";

	// Obtiene las id de las colecciones activas
	$activas_q = "SELECT id FROM colecciones_local WHERE activa=1 AND ((CURTIME() >= hora_inicio AND CURTIME() <= hora_fin) OR hora_inicio = hora_fin)";
	$res = mysqli_query($conn, $activas_q);

	if($res) {
		if(mysqli_num_rows($res)) {
			$canciones = array();
	
			while($row = mysqli_fetch_assoc($res)) {
				array_push($canciones, $row['id']);
			}

			// TODO: Si ya no hay canciones para tocar(cosa improbable) se queda sin poder tocar nada. Hacer que toque algo.
	
			$canciones_coleccionadas_q = "SELECT cancion_id FROM canciones_coleccionadas_local WHERE coleccion_id IN(".implode(",", $canciones).")";
			$res = mysqli_query($conn, $canciones_coleccionadas_q);
	
			$ids_canciones = array();
			while($row = mysqli_fetch_assoc($res)) { 
				array_push($ids_canciones, $row['cancion_id']);
			}
	
			$q =  "SELECT id FROM canciones_local WHERE id IN(".implode(',', $ids_canciones).") AND ".$clausula_horas." ORDER BY RAND() LIMIT 1";

			$res = mysqli_query($conn, $q);
	
		// No hay colecciones activas, tomar una canción de la colección entera.
		} else {
	
			$q =  "SELECT id FROM canciones_local WHERE ".$clausula_horas." ORDER BY RAND() LIMIT 1";
			$res = mysqli_query($conn, $q);
		}
	
		$canciones = array();
		if(mysqli_num_rows($res)) {
			while($row = mysqli_fetch_assoc($res)) {
				// $cancion = $row['id'];
				array_push($canciones, (int) $row['id']);
			}
			return json_encode($canciones);
		}
	}

	return json_encode(array());
}


function getHora() {
	$date = new DateTime("now", new DateTimeZone('America/Mexico_City'));
	return $date->format("H:i");
}

function playedAt($song_id) {
	global $conn;

	$q = "UPDATE canciones_local SET last_played=CURTIME() WHERE id={$song_id} LIMIT 1";

	mysqli_query($conn, $q);
}


?>