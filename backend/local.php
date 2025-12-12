<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Content-Type: application/json; charset=utf-8');

include "../config.php";

$conn = new mysqli(
    $db_config['host'],
    $db_config['user'],
    $db_config['password'],
    $db_config['database']
) or die("no se pudo conectar.");

$url = $server_url;


if (isset($_GET['accion'])) {
    $action = $_GET['accion'];

    if ($action === 'update_db') {
        echo json_encode(updateLocalDB());
    }
    if ($action === 'get_random_song') {
        echo json_encode(getRandomSong());
    }
    if ($action === 'get_songs_in_queue') {
        if (isset($_GET['songs'])) {
            echo getSongsInQueue($_GET['songs']);
        } else {
            echo json_encode(['msg' => 'No hay cola']);
        }
        exit();
    }
    if ($action === 'get_song_from_id') {
        echo json_encode(getSongFromId($_GET['id']));
    }
}

if (isset($_POST['accion'])) {
    $action = $_POST['accion'];

    if ($action === 'update_colecciones_local') { // Esto ya no se está usando.
        echo json_encode(updateColeccionesLocal($_POST['colecciones'], $_POST['canciones_coleccionadas']));
    }

    if ($action === 'update_last_played') {
        $song = (int) $_POST['cancion_id'];
        $sucursal = (int) $_POST['sucursal_id'];
        playedAt($song, $sucursal);
    }

    if ($action === 'panic_button') {
        echo pushPanicButton($_POST['id_sucursal']);
    }
}


function updateLocalDB()
{
    global $conn, $url;

    $db = fetchFromServer('get_db');

    // Ahora va a guardar la base en el servidor local
    mysqli_autocommit($conn, FALSE);
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($conn, "TRUNCATE TABLE canciones_local") or die(mysqli_error($conn));
    mysqli_query($conn, "ALTER TABLE canciones_local AUTO_INCREMENT = 1") or die(mysqli_error($conn));
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1") or die(mysqli_error($conn));

    foreach ($db as $d) {
        $random_shift = $d['random_shift'] == null ? "NULL" : "\"{$d['random_shift']}\"";
        $q = "INSERT INTO canciones_local(id, titulo, duracion, artista, song_path, random_shift)
              VALUES({$d['id']}, \"{$d['titulo']}\", \"{$d['duracion']}\", \"{$d['artista']}\", \"{$d['song_path']}\", $random_shift)";
        mysqli_query($conn, $q) or die(mysqli_error($conn));
    }

    mysqli_commit($conn);

    // Actualiza las colecciones
    $result = fetchFromServer('get_colecciones_para_sucursales');

    // Obtiene la base de datos en formato json
    if ($result !== null) {
        updateColeccionesLocal($result['colecciones'], $result['canciones_coleccionadas']);
    }
}

function fetchFromServer($endpoint)
{
    global $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url?accion=$endpoint");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // echo "URL-> " . "$url?accion=$endpoint" . "\n";

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "curl ERROR: " . curl_error($ch);
    } else {
        $result = json_decode($result, true);
    }

    curl_close($ch);

    return $result;
}


function updateColeccionesLocal($colecciones, $canciones_coleccionadas)
{
    global $conn;

    mysqli_autocommit($conn, FALSE);
    mysqli_query($conn, "DROP TABLE IF EXISTS canciones_coleccionadas_local");

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS canciones_coleccionadas_local (
        coleccion_id INT UNSIGNED,
        cancion_id INT UNSIGNED,
        FOREIGN KEY (coleccion_id) REFERENCES colecciones_local(id) ON DELETE CASCADE,
        FOREIGN KEY (cancion_id) REFERENCES canciones_local(id) ON DELETE CASCADE,
        PRIMARY KEY(coleccion_id, cancion_id))"
    );


    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($conn, "TRUNCATE TABLE colecciones_local");

    foreach ($colecciones as $c) {
        $q = "INSERT INTO colecciones_local(id, hora_inicio, hora_fin, activa)
              VALUES({$c['id']}, \"{$c['hora_inicio']}\", \"{$c['hora_fin']}\", {$c['activa']})";
        mysqli_query($conn, $q);
    }

    foreach ($canciones_coleccionadas as $d) {
        $q = "INSERT INTO canciones_coleccionadas_local(coleccion_id, cancion_id)
              VALUES({$d['coleccion_id']},{$d['cancion_id']})";

        mysqli_query($conn, $q);
    }

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    if (mysqli_commit($conn)) {
        return ["colecciones_actualizadas" => true];
    }

    return ["colecciones_actualizadas" => false];
}


function getSongsInQueue($queue)
{
    global $conn;

    $queue = implode(",", $queue);

    $q = "SELECT * FROM canciones_local WHERE id IN($queue) ORDER BY FIELD(id, {$queue})";

    $res = mysqli_query($conn, $q);

    $canciones = [];
    if (mysqli_num_rows($res)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $cancion = [
                'id' => $row['id'],
                'titulo' => ($row['titulo']),
                'duracion' => $row['duracion'],
                'path' => ($row['song_path']),
                'artista' => ($row['artista'])
            ];
            array_push($canciones, $cancion);
        }
        return json_encode($canciones);
    }
    return json_encode(['queue' => false]);
}


function getRandomSong()
{
    global $conn;
    $current_time = getHora();
    $current_shift = ($current_time >= "08:00" && $current_time < "17:00")
        ? "morning" : "evening";

    $clausula_horas = "(last_played IS NULL OR TIMEDIFF(CURTIME(), last_played) >= '3:00:00')";

    // Mejor caso
    $q = "SELECT id FROM canciones_local WHERE random_shift='$current_shift' AND $clausula_horas";
    $q .= "ORDER BY RAND() LIMIT 1";

    $r = mysqli_query($conn, $q);

    if ($r && mysqli_num_rows($r)) {
        $random_song_id = mysqli_fetch_row($r)[0];
        return $random_song_id;
    }

    // Si se agotaron las canciones del turno, repite alguna canción
    $q = "SELECT id FROM canciones_local WHERE random_shift='$current_shift'";
    $q .= "ORDER BY RAND() LIMIT 1";

    $r = mysqli_query($conn, $q);

    if ($r && mysqli_num_rows($r)) {
        $random_song_id = mysqli_fetch_row($r)[0];
        return $random_song_id;
    }

    $q = "SELECT id FROM canciones_local ORDER BY RAND() LIMIT 1";

    $r = mysqli_query($conn, $q);

    if ($r && mysqli_num_rows($r)) {
        return mysqli_fetch_row($r)[0];
    }

    return 42;
}

function getHora()
{
    $date = new DateTime("now", new DateTimeZone('America/Mexico_City'));
    return $date->format("H:i");
}

function playedAt($song_id, $sucursal_id)
{
    global $conn;

    $q = "UPDATE canciones_local SET last_played=NOW() WHERE id={$song_id} LIMIT 1";
    mysqli_query($conn, $q);

    postCancionesDesactivadasEnSucursal($sucursal_id);
}

function postCancionesDesactivadasEnSucursal($sucursal_id)
{
    global $conn, $url;

    $canciones = [
        'accion' => 'post_canciones_desactivadas_en_sucursal',
        'id_sucursal' => $sucursal_id,
        'ids_canciones' => []
    ];

    // Obtener los ids de las canciones que tengan menos de 3 horas de ser tocadas
    $q = "SELECT id from canciones_local WHERE TIMEDIFF(NOW(), last_played) < '3:00:00'";
    $res = mysqli_query($conn, $q);

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
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

    curl_close($ch);
}

function pushPanicButton($id_sucursal)
{
    global $conn, $url;

    // SET canciones_pedidas field to NULL
    $q = "UPDATE canciones_local SET last_played=NULL";
    mysqli_query($conn, $q);

    $sucursal = [
        'accion' => 'delete_songs_from_sucursal',
        'id_sucursal' => $id_sucursal
    ];

    $panic_button_action = http_build_query($sucursal);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $panic_button_action);

    // Receive server response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec($ch);
    curl_close($ch);

    return json_encode(['processed' => true]);
}

function getSongFromId($id) {
    global $conn, $url;

    $q = "SELECT * FROM canciones_local WHERE id=$id LIMIT 1";
    $res = mysqli_query($conn, $q);
    if ($res && mysqli_num_rows($res)) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

// $canciones = [];
// if (mysqli_num_rows($res)) {
//     while ($row = mysqli_fetch_assoc($res)) {
//         $cancion = [
//             'id' => $row['id'],
//             'titulo' => ($row['titulo']),
//             'duracion' => $row['duracion'],
//             'path' => ($row['song_path']),
//             'artista' => ($row['artista'])
//         ];
//         array_push($canciones, $cancion);
//     }
// }