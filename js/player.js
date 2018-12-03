$(function() {




var sucursal = $('#sucursal_id').val();

console.log(sucursal);

var sucursalId = sucursal;

var nombreSucursal;

// alert(sucursalId);
switch(parseInt(sucursalId)) {
	case 10:
	nombreSucursal = 'Corporativo';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 11:
	nombreSucursal = 'Condesa';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 12:
	nombreSucursal = 'Polanco';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 13:
	nombreSucursal = 'Santa Fé';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 14:
	nombreSucursal = 'Perisur';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 15:
	nombreSucursal = 'Zona Rosa';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 16:
	nombreSucursal = 'Roma';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 17:
	nombreSucursal = 'San Ángel';
	TIPO_SUCURSAL = 'restaurante';
	break;
	case 20:
	nombreSucursal = 'Bar Bukowski';
	TIPO_SUCURSAL = 'bar';
	break;
	case 21:
	nombreSucursal = 'Bar Roma';
	TIPO_SUCURSAL = 'bar';
	break;
}

$('.nombre_sucursal').html(nombreSucursal).css({'display':'inline-block', 'margin':'0', 'float': 'left'});

window.history.pushState({}, document.title, "/sucursal/index.php");
console.log(sucursalId);
console.log(TIPO_SUCURSAL);

const DEBUG = false;

var cola = [];

var audio = document.getElementById('plyr');
audio.volume = 0.5;
audio.autoplay = false;

var songIndex = 0;

var tiempoRestante;

var url = (DEBUG) ? '/rocola/consola/controllers/controller_musica.php' 
				  				: 'http://rockola.pendulo.com.mx/rocola/consola/controllers/controller_musica.php';
				  				// : 'http://www.betomad.com/rocola/consola/controllers/controller_musica.php';

var url_local = 'backend/local.php';

var fakeSongs = {
    coleccion:'coleccion x',
    canciones: []
};


function getQueue() {
	$.ajax({
		url: url,
		type: 'GET',
		dataType: 'json',
		crossDomain: true,
		data: {
				accion: 'get_queue',
				sucursal_id: sucursalId
		},
		success: function(queue) {
			if(queue.length) {
				getSongsInQueue(queue);
			} else {
				playRandomSong();
			}
		},
		error: function(e, a) { console.log(e, a)}
	});
}


function getSongsInQueue(queue) {
	console.log("	wueue: ", queue);
	$.ajax({
		url: url_local,
		type: 'GET',
		dataType: 'json',
		crossDomain: true,
		data: {
			'accion': 'get_songs_in_queue',
			'songs': queue
		},
		success: function(songs) {
			if (songs.length) {	
				// traer la info de la canción de la db local
				for(var s of songs) {
					cola.push(s);
				}
				fillWithSongs(cola, 'queue');
				audio.src = cola[0].path;
				playSong(cola[0], cola[0].id);
			} else {
				playRandomSong();
				console.log("pero qué mamarrachos");
			}
		},
		error: function(response, c) {
			console.log(response, c)
		}
	});
}


function playRandomSong() {
	$.ajax({
	    url: url_local,
	    type: 'GET',
	    dataType: 'json',
	    data: {
	        'accion': 'get_random_song'
	    },
	    success: function(response) {
	    	if(response.length) {
	    		getSongsInQueue(response);
	    	}
	    },
	    error: function(response, p) {
	        console.log(response, p);
	    }
	});
}


function fillWithSongs(fakeSongs, lista) {
	var canciones = '';
	for(const [index, s] of fakeSongs.entries()) {
		canciones += Song(s, index);
	}

	switch(lista) {
		case 'songs':
			$('#canciones-disponibles-lista').append(canciones);
		break;

		case 'queue':
			$('#cola').append(canciones);
		break;
	}
}


function removeSongFromQueue(songId) {
	$.ajax({
		url: url,
		type: 'POST',
		dataType: 'json',
		data: {
			accion: 'remove_from_queue',
			sucursal_id: sucursalId,
			song_id: songId
		},
		success: function(response) {
			console.log("borrada: ", response);
		},
		error: function(response, erre) {
			console.log(response, erre); ///// CAMBIO
		}
	});
}


function playSong(cancion, id) {
	var segundosParaRefrescarStatus = 3;
	audio.src = cancion.path;
	audio.load();
	setTimeout(function() { 
		audio.play();
	}, 150);
	
	removeSongFromQueue(id);

	audio.onloadedmetadata = function() {
		var duracion = Math.floor(audio.duration);
		var timero = document.getElementById('tiempo-restante');

		// Envía el estatus de la canción que ahora suena
		postSongStatus(
			cancion.id, 
			cancion.titulo, 
			cancion.artista, 
			{duracion: audio.duration, currentTime: audio.currentTime}
		);

		var segundosTranscurridos = 0;

		tiempoRestante = setInterval(function() {
			if(segundosTranscurridos > segundosParaRefrescarStatus) {

				postSongStatus(
					cancion.id, 
					cancion.titulo, 
					cancion.artista, 
					{duracion: audio.duration, currentTime: audio.currentTime}
				);

				// resetea el conteo para enviar info
				segundosTranscurridos = 0;
			}

			var tiempo = new Date(null);
			tiempo.setSeconds(audio.duration - audio.currentTime);
			var falta = tiempo.toISOString().substr(11, 8);
			timero.innerHTML = falta;

			segundosTranscurridos++;

		}, 1000);
	}
}


function postSongStatus(cancionId, titulo, artista, tiempo) {
	$.ajax({
		url: url,
		type: 'POST',
		dataType: 'json',
	 crossDomain: true,
		data: {
			accion: 'update_current_song_status',
			sucursal_id: sucursalId,
			cancion_id: cancionId,
			titulo_cancion: titulo,
			artista: artista,
			tiempo: {
				duracion: tiempo.duracion, 
				tiempo_transcurrido: tiempo.currentTime
			}
		},
	});
}


function playedAt(cancionId) {
	$.ajax({
		url: url_local,
		type: 'POST',
		dataType: 'json',
		crossDomain: true,
		data: {
			accion: 'update_last_played',
			cancion_id: cancionId,
			sucursal_id: sucursalId
		},
		success: function(re) {
			console.log(re);
		},
		error: function(a, b) {
			console.log(a, b);
		}
	});
}


function Song(info, index) {
	var altRow = '';
	if(index % 2 === 0) {
		altRow = 'alt-row';
	}
	return `<li class="cancion ${altRow}" data-cancion_id="${info.id}" data-path="${info.path}">
				<span>${info.titulo}</span><span>${info.artista}</span><span>${info.duracion}</span>
			</li>`;
}


function checkQueueStatus() {
	$.ajax({
		url: url,
		type: 'POST',
		dataType: 'json',
		crossDomain: true,
		data: {
			accion: 'update_status_canciones_pedidas',
			sucursal_id: sucursalId,
		},
		// success: function(resp) {
		// 	console.log(resp)
		// },
		// error: function(a, e) {
		// 	console.log(a, e);
		// }
	});
}


// AQUÍ INICIA TODO
checkQueueStatus();
getQueue();


$('#actualiza-catalogo').on('click', function() {
	if(confirm("¿Actualizar el catálogo?")) {
		actualizaCatalogo();
	}
});


function actualizaCatalogo() {
	$.ajax({
		url: url_local,
		type: 'GET',
		dataType: 'json',
		// crossDomain: true,
		data: {
			accion: 'update_db',
			tipo_sucursal: TIPO_SUCURSAL
		},
		success: function(response) {
			// alert(response.msg);
		},
		error: function(error, dd) {
			console.log(error, dd);
		}
	});
}


// La canción termina
audio.addEventListener('ended',function() {

	console.log("COLEEEERAAA ", cola);
	// Envía la hora en que se tocó
	playedAt(cola[0].id);

	checkQueueStatus();
	clearInterval(tiempoRestante);
	// si hay elementos en cola
	if(cola.length) {
		// obtenemos el id de la canción 
		var nextSong = parseInt(cola[0].id);
	}

	// borra el primer elemento de cola
	cola.shift();

	if(cola.length) {
		playSong(cola[0], cola[0].id);
	} else {
		audio.src = '';
		getQueue();
	}

	$('#cola').find('li:first').css('border', '1px solid').remove();
});


});