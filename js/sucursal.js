// Modificar dependiendo de la sucursal
//
// Condesa:    11
// Polanco:    12
// Santa Fé:   13
// Perisur:    14
// Zona Rosa:  15
// Roma:       16
// San Ángel:  17
// Del Valle   18
// PRUEBAS:    20

const sucursalId = 20;

// const webLocation = 'rocola.pendulo.com.mx/consola/controllers/controller_musica.php'

// const protocol = 'https://';
// const host = 'rocola.pendulo.com.mx';
// const port = '';

const protocol = "http://";
// const host = "127.0.0.1";
const host = "172.17.0.1"; // Para obtener esta IP de Docker, comando ip addr show docker0
const port = ":8080";

const path = "/consola/controllers/controller_musica.php";

var url = `${protocol}${host}${port}${path}`;
