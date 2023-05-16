// Modificar dependiendo de la sucursal
//
// Condesa:    11
// Polanco:    12
// Santa Fé:   13
// Perisur:    14
// Zona Rosa:  15
// Roma:       16
// San Ángel:  17
// Pruebas:    20


var DEBUG = false;

var sucursalId = 20;


const protocol = 'https:';
const webLocation = 'rocola.pendulo.com.mx/consola/controllers/controller_musica.php' 

var url = (DEBUG) ? '/consola/controllers/controller_musica.php' 
                  : `${protocol}//${webLocation}`;
