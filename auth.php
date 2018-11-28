<?php
session_start();

include './secrets.php';

$conn = new mysqli($secrets['host'], $secrets['user'], $secrets['password'], $database);


if(isset($_POST['usuario']) && isset($_POST['password'])) {
	$usuario = $_POST['usuario'];
	$password = $_POST['password'];

	if(authSucursal($usuario, $password)) {
		$_SESSION['usuario'] = $usuario;
		if(isset($_GET['sitio'])) {
			header("Location: ".htmlspecialchars($_GET['sitio']));
		} else {
			header("Location: index.php");
		}
	} else {
		$_SESSION['mensaje'] = "Usuario o contraseña incorrectos.";
		header("Location: auth.php");
	}
}


function authSucursal($usuario, $password) {
	global $conn;

	// obtener usuario
	$usuario_q = "SELECT * FROM usuarios WHERE usuario=\"{$usuario}\" LIMIT 1";
	$res = mysqli_query($conn, $usuario_q);

	if($res) {
		if(mysqli_num_rows($res)) {
			$crypt = mysqli_fetch_assoc($res)['pwd'];
			echo $password." vs ". $crypt;
			// comprobar la contraseña contra el hash que está en la base de datos
			if(password_verify($password, $crypt)) {
				return true;
			}
		}
	}


	return false;
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>log</title>

	<style>

	.detail-mask {
		position: fixed;
		z-index: 9998;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: gray;
		display: table;
	}
			
	.auth-contenedor {
		background-color: gray;
		width:100%;
		height:100%;
		display:table-cell;
		vertical-align: middle;
	}

	.auth-form {
		background-color: black;
		width:500px;
		border-radius: 5px;
		color: #fff;
		text-align: center; 
		margin:auto;
		padding:20px;
		width: 300px;
		box-shadow: 2px 5px 20px #000;
	}

	.auth-form input {
		display: block;
		margin: auto;
		margin:10px auto 10px auto;
		font-size: 20px;
		padding: 5px;
		max-width: 80%;
	}

	.auth-form input[type="submit"] {
		background-color: orange;
		border-radius: 5px;
		border: none;
		width: 80%;
	}

	</style>

</head>
<body>
	

<div class="detail-mask">
	<div class="auth-contenedor">
	
		<div class="auth-form">
			<h2>Ingresa</h2>
			<p style="color:red;"><?php if(isset($_SESSION['mensaje'])) echo $_SESSION['mensaje']; ?></p>
			<form id="formulario_auth" action="" method="post">
				<input type="hidden" name="accion" value="auth_sucursal">
				<input name="usuario" type="text" placeholder="usuario">
				<input name="password" type="password" placeholder="contrase&ntilde;a">
				<input type="submit" value="Entrar">
			</form>
			
		</div>
	</div>
	
</div>


</body>


<script src="js/jquery.js"></script>
<script src="js/auth.js"></script>


</html>
