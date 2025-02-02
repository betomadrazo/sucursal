<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>log</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>

    <div class="detail-mask">
        <div class="auth-contenedor">

            <div class="auth-form">
                <h2>Ingresa</h2>
                <p class="msg_fail" style="color:red; display:none;">Usuario o contraseña incorrectos.</p>
                <form id="formulario_auth" action="" method="post">
                    <input type="hidden" name="accion" value="auth_sucursal">
                    <input name="usuario" type="text" placeholder="usuario">
                    <input name="password" type="password" placeholder="contrase&ntilde;a">
                    <input type="submit" value="Entrar">
                </form>
            </div>
        </div>
    </div>

    <script src="js/jquery.js"></script>
    <script src="js/sucursal.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>