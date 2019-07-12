<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('content-type: application/json; charset=utf-8');

include "../secrets.php";

$database = 'rocola';

$protocolo = 'https://';

$conn = new mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database) or die("no se pudo conectar.");

$DEBUG = false;

// $web_server = 'www.betomad.com';
$web_server = 'rocola.pendulo.com.mx';

$server = ($DEBUG) ? $_SERVER['SERVER_NAME'] : $web_server;

$url = $protocolo.$server.'/rocola/consola/controllers/controller_musica.php';


if(isset($_GET['accion']) && $_GET['accion'] === 'update_db') {
	echo json_encode(updateLocalDB());
}


if(isset($_POST['accion']) && $_POST['accion'] === 'update_colecciones_local') {
	echo json_encode(updateColeccionesLocal($_POST['colecciones'], $_POST['canciones_coleccionadas']));
}


if(isset($_POST['accion']) && $_POST['accion'] === 'update_last_played') {
	playedAt((int)$_POST['cancion_id'], (int)$_POST['sucursal_id']);
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

  	// Ahora va a guardar la base en el servidor local
	mysqli_autocommit($conn, FALSE);
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
	mysqli_query($conn, "TRUNCATE TABLE canciones_local") or die(mysqli_error($conn));
	mysqli_query($conn, "ALTER TABLE canciones_local AUTO_INCREMENT = 1") or die(mysqli_error($conn));
	mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1") or die(mysqli_error($conn));

	foreach($db as $d) {
		$q = "INSERT INTO canciones_local(id, titulo, duracion, artista, song_path) 
			  VALUES({$d['id']}, \"{$d['titulo']}\", \"{$d['duracion']}\", \"{$d['artista']}\", \"{$d['song_path']}\")";
		mysqli_query($conn, $q) or die(mysqli_error($conn));
	}

	mysqli_commit($conn);

	// Actualiza las colecciones
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url."?accion=get_colecciones_para_sucursales");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);

	curl_close($ch);

	// Obtiene la base de datos en formato json
  	$colecciones = json_decode($result, true);

  	updateColeccionesLocal($colecciones['colecciones'], $colecciones['canciones_coleccionadas']);
}


function updateColeccionesLocal($colecciones, $canciones_coleccionadas) {
	global $conn;

	mysqli_autocommit($conn, FALSE);
	// mysqli_query($conn, "TRUNCATE TABLE canciones_coleccionadas_local");
	mysqli_query($conn, "DROP TABLE IF EXISTS canciones_coleccionadas_local");

	mysqli_query($conn,
	"CREATE TABLE IF NOT EXISTS canciones_coleccionadas_local (
	    coleccion_id INT UNSIGNED,
	    cancion_id INT UNSIGNED,
	    FOREIGN KEY (coleccion_id) REFERENCES colecciones_local(id) ON DELETE CASCADE,
	    FOREIGN KEY (cancion_id) REFERENCES canciones_local(id) ON DELETE CASCADE,
	    PRIMARY KEY(coleccion_id, cancion_id)
	)"
	);

	mysqli_query($conn, "TRUNCATE TABLE colecciones_local");

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
				'artista'=>($row['artista'])
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
	// $clausula_horas = "(TIMEDIFF(CURTIME(), last_played) >= '3:00:00' OR last_played IS NULL)";
	// $clausula_horas = "SUBDATE(NOW(), INTERVAL 3 HOUR) >= last_played";
	$clausula_horas = "TIMEDIFF(NOW(), last_played) >= '01:00:00'";

	// Obtiene las id de las colecciones activas
	// $activas_q = "SELECT id FROM colecciones_local WHERE activa=1 AND ((CURTIME() >= hora_inicio AND CURTIME() <= hora_fin) OR hora_inicio = hora_fin)";
	$activas_q = "SELECT id FROM colecciones_local WHERE activa=1 AND ((CURTIME() >= hora_inicio AND CURTIME() <= hora_fin) OR hora_inicio = hora_fin)";
	$res = mysqli_query($conn, $activas_q);

	// Si hay canciones en colecciones activas y que lleven más de 3 horas de haber sido tocadas
	if($res && mysqli_num_rows($res)) {
		// if(mysqli_num_rows($res)) {
		$canciones = array();
	
		while($row = mysqli_fetch_assoc($res)) {
			array_push($canciones, $row['id']);
		}
	
		$canciones_coleccionadas_q = "SELECT cancion_id FROM canciones_coleccionadas_local WHERE coleccion_id IN(".implode(",", $canciones).")";
		$res = mysqli_query($conn, $canciones_coleccionadas_q);
	
		$ids_canciones = array();
		while($row = mysqli_fetch_assoc($res)) { 
			array_push($ids_canciones, $row['cancion_id']);
		}
	
		$q =  "SELECT id FROM canciones_local WHERE id IN(".implode(',', $ids_canciones).") AND ".$clausula_horas." ORDER BY RAND() LIMIT 1";

		// echo $q;

		$res = mysqli_query($conn, $q);

		// Aquí regresa un número
		// Si hay elementos, regresa uno aleatorio dentro de la colección
		if($res && mysqli_num_rows($res)) {
			$cancion_random = mysqli_fetch_assoc($res)['id'];
			return json_encode(array($cancion_random));
		} elseif($res && !mysqli_num_rows($res)) {
			$q =  "SELECT id FROM canciones_local WHERE ".$clausula_horas." ORDER BY RAND() LIMIT 1";
			$res = mysqli_query($conn, $q);
			if($res && mysqli_num_rows($res)) {
				$cancion_random = mysqli_fetch_assoc($res)['id'];
				return json_encode(array($cancion_random));	
			} else {
				$q =  "SELECT id FROM canciones_local ORDER BY RAND() LIMIT 1";
				$res = mysqli_query($conn, $q);
				if($res && mysqli_num_rows($res)) {
					$cancion_random = mysqli_fetch_assoc($res)['id'];
					return json_encode(array($cancion_random));	
				} else {
					return json_encode(array("error"=>"No hay canciones!"));
				}
			}
		} else {
			// No hay colecciones activas, tomar una canción de la colección entera.
			$q =  "SELECT id FROM canciones_local ORDER BY RAND() LIMIT 1";
			$res = mysqli_query($conn, $q);
			if($res && mysqli_num_rows($res)) {
				$cancion_random = mysqli_fetch_assoc($res)['id'];
				return json_encode(array($cancion_random));	
			} else {
				return json_encode(array("error"=>"No hay canciones!"));
			}
		}
	
	} else {
		$q =  "SELECT id FROM canciones_local ORDER BY RAND() LIMIT 1";
		$res = mysqli_query($conn, $q);
		if($res && mysqli_num_rows($res)) {
			$cancion_random = mysqli_fetch_assoc($res)['id'];
			return json_encode(array($cancion_random));	
		} else {
			return json_encode(array("error"=>"No hay canciones!"));
		}
	}
}


function getHora() {
	$date = new DateTime("now", new DateTimeZone('America/Mexico_City'));
	return $date->format("H:i");
}

function playedAt($song_id, $sucursal_id) {
	global $conn;

	$q = "UPDATE canciones_local SET last_played=NOW() WHERE id={$song_id} LIMIT 1";
	mysqli_query($conn, $q);

	postCancionesDesactivadasEnSucursal($sucursal_id);
}

function postCancionesDesactivadasEnSucursal($sucursal_id) {
	global $conn, $url;

	$canciones = array(
		'accion'=>'post_canciones_desactivadas_en_sucursal',
		'id_sucursal'=> $sucursal_id,
		'ids_canciones'=> array()
	);

	// Obtener los ids de las canciones que tengan menos de 3 horas de ser tocadas
	$q = "SELECT id from canciones_local WHERE TIMEDIFF(NOW(), last_played) < '3:00:00'";
	$res = mysqli_query($conn, $q);

	if($res) {
		while($row = mysqli_fetch_assoc($res)) {
			array_push($canciones['ids_canciones'], $row['id']);
		}
	}

	$songs = http_build_query($canciones);

	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $songs);
	
	// Receive server response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$server_output = curl_exec($ch);

	curl_close ($ch);
}


?>
