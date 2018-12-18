<?php 

session_start();

if(isset($_GET['usuario'])) {
    $_SESSION['usuario'] = $_GET['usuario'];
}

$DEBUG = true;

if(!isset($_SESSION['usuario'])) {
    echo urlencode($_SERVER['REQUEST_URI']);
    header("Location: /sucursal/auth.php?sitio=".urlencode("http://localhost/sucursal/index.php")); //urlencode($_SERVER['REQUEST_URI']));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reproductor</title>
    <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

<input type="hidden" id="sucursal_id" value="<?php if(isset($_SESSION['sucursal_id'])) echo $_SESSION['sucursal_id']; else echo ''; ?>">

<div class="fondo">
    <div class="conti">
        <div class="cont-acciones">
            <h3 class="nombre_sucursal"></h3>
            <button class="voton" id="actualiza-catalogo">Actualizar cat&aacute;logo</button>

            <a href="logout.php" style="text-decoration: none; display:inline-block; margin-left:20px; vertical-align:bottom;">
                <span style="display:block; font-size:10px;"><?php if(isset($_SESSION['usuario'])) echo $_SESSION['usuario']; ?></span>
                <span style="display:block;">Salir</span>
            </a>

        </div>
    
        <div class="cancion_row">
            <audio 
                id="plyr" 
                controls
                autoplay="false"
                proload="metadata"
            >
                <source id="audio-src" src="" type="audio/mpeg">
            </audio>
            <span id="tiempo-restante"></span>
        </div>
    
        <div class="contenedor-cola">
            <ul id="cola"></ul>
        </div>
    </div>
</div>

<div class="actualizando">
    <div class="actualizando_cont">
        <div class="actualizando_mensaje">
            Se est&aacute; actualizando el cat&aacute;logo
        </div>
    </div>
</div>
    
</body>

<script src="js/jquery.js"></script>

<script src="js/sucursal.js"></script>
<script src="js/player.js"></script>

</html>


