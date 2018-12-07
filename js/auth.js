
var url = (DEBUG) ? '/rocola/consola/controllers/controller_musica.php' 
				  				: 'http://rocola.pendulo.com.mx/rocola/consola/controllers/controller_musica.php';
				  				// : 'http://www.betomad.com/rocola/consola/controllers/controller_musica.php';

$(function() {

	$('#formulario_auth').submit(function(event) {
		event.preventDefault();

		var formulario = $(this).serialize();
		
		$.ajax({
			// url: 'http://www.betomad.com/rocola/consola/controllers/controller_musica.php',
			url: url,
			type: 'POST',
			dataType: 'json',
			crossDomain: true,
			data: formulario,
			success: function(response) {
				console.log(response);
				if(response.auth) {
					window.location.replace(`/sucursal/index.php?usuario=${response.usuario}`);
				} else $('.msg_fail').css({'display':'block'});
			},
			error: function(error, dd) {
				console.log(error, dd);
			}
		});
	});

});