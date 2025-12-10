$(function () {
  console.log("id sucursal: ", sucursalId);

  const sucursales = {
    10: "Corporativo",
    11: "Condesa",
    12: "Polanco",
    13: "Santa Fe",
    14: "Perisur",
    15: "Zona Rosa",
    16: "Roma",
    17: "San Ángel",
    18: "Del Valle",
  };
  const nombreSucursal = sucursales[parseInt(sucursalId)] || "PRUEBAS";

  $(".nombre_sucursal")
    .html(nombreSucursal)
    .css({ display: "inline-block", margin: "0", float: "left" });

  window.history.pushState({}, document.title, "/sucursal/index.php");

  var cola = [];

  var audio = document.getElementById("plyr");
  audio.volume = 0.01;
  audio.autoplay = false;

  var idCurrentSong = 0;

  var tiempoRestante;

  var url_local = "backend/local.php";

  function getQueue() {
    $.ajax({
      url: url,
      type: "GET",
      dataType: "json",
      crossDomain: true,
      data: {
        accion: "get_queue",
        sucursal_id: sucursalId,
      },
      success: function (queue) {
        if (queue.length) {
          if (queue[0] === idCurrentSong) {
            queue.shift();
          }
          getSongsInQueue(queue);
        } else {
          playRandomSong();
        }
      },
      error: function (e, a) {
        playRandomSong();
      },
    });
  }

  function getSongsInQueue(queue) {
    $.ajax({
      url: url_local,
      type: "GET",
      dataType: "json",
      crossDomain: true,
      data: {
        accion: "get_songs_in_queue",
        songs: queue,
      },
      success: function (songs) {
        if (songs.length) {
          console.log("SONGS: ", songs, songs.length);
          // traer la info de la canción de la db local
          for (var s of songs) {
            cola.push(s);
          }
          fillWithSongs(cola, "queue");
          audio.src = cola[0].path;
          playSong(cola[0], cola[0].id);
        } else {
          playRandomSong();
          console.log(
            "No hay canciones en cola; poniendo una canción aleatoria."
          );
        }
      },
      error: function (response, c) {
        console.log(response, c);
      },
    });
  }

  async function playRandomSong() {
    try {
      const response = await fetch(`${url_local}?accion=get_random_song`, {
        method: "GET",
        headers: {
          "Content-Type": "appliction/json",
        },
      });

      if (!response.ok) {
        throw new Error("Error: ", response.status);
      }

      const data = await response.json();

      console.log(`${url_local}?accion=get_song_from_id&id=${data}`);
      const randomSongData = await fetch(
        `${url_local}?accion=get_song_from_id&id=${data}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (!randomSongData.ok) {
        throw new Error("Error: ", response.status);
      }

      const songData = await randomSongData.json();

      fillWithSongs([songData], "queue");
    } catch (error) {
      console.log("Error: ", error);
    }
  }

  function fillWithSongs(songs, lista) {
    var canciones = "";
    for (const [index, s] of songs.entries()) {
      canciones += Song(s, index);
    }

    switch (lista) {
      case "songs":
        $("#canciones-disponibles-lista").append(canciones);
        break;

      case "queue":
        $("#cola").append(canciones);
        break;
    }
  }

  function removeSongFromQueue(songId) {
    $.ajax({
      url: url,
      type: "POST",
      dataType: "json",
      data: {
        accion: "remove_from_queue",
        sucursal_id: sucursalId,
        song_id: songId,
      },
    });
  }

  function playSong(cancion, id) {
    idCurrentSong = id;

    var segundosParaRefrescarStatus = 3;
    audio.src = cancion.path;
    audio.load();
    setTimeout(function () {
      audio.play();
    }, 150);

    removeSongFromQueue(id);

    audio.onloadedmetadata = function () {
      var duracion = Math.floor(audio.duration);
      var timero = document.getElementById("tiempo-restante");

      // Envía el estatus de la canción que ahora suena
      postSongStatus(cancion.id, cancion.titulo, cancion.artista, {
        duracion: audio.duration,
        currentTime: audio.currentTime,
      });

      var segundosTranscurridos = 0;

      tiempoRestante = setInterval(function () {
        if (segundosTranscurridos > segundosParaRefrescarStatus) {
          postSongStatus(cancion.id, cancion.titulo, cancion.artista, {
            duracion: audio.duration,
            currentTime: audio.currentTime,
          });

          // resetea el conteo para enviar info
          segundosTranscurridos = 0;
        }

        var tiempo = new Date(null);
        tiempo.setSeconds(audio.duration - audio.currentTime);
        var falta = tiempo.toISOString().substr(11, 8);
        timero.innerHTML = falta;

        segundosTranscurridos++;
      }, 1000);
    };
  }

  function postSongStatus(cancionId, titulo, artista, tiempo) {
    $.ajax({
      url: url,
      type: "POST",
      dataType: "json",
      crossDomain: true,
      data: {
        accion: "update_current_song_status",
        sucursal_id: sucursalId,
        cancion_id: cancionId,
        titulo_cancion: titulo,
        artista: artista,
        tiempo: {
          duracion: tiempo.duracion,
          tiempo_transcurrido: tiempo.currentTime,
        },
      },
    });
  }

  function playedAt(cancionId) {
    $.ajax({
      url: url_local,
      type: "POST",
      dataType: "json",
      crossDomain: true,
      data: {
        accion: "update_last_played",
        cancion_id: cancionId,
        sucursal_id: sucursalId,
      },
    });
  }

  function Song(info, index) {
    var altRow = "";
    if (index % 2 === 0) {
      altRow = "alt-row";
    }
    return `<li class="cancion ${altRow}" data-cancion_id="${info.id}" data-path="${info.path}">
                <span>${info.titulo}</span><span>${info.artista}</span><span>${info.duracion}</span>
            </li>`;
  }

  function checkQueueStatus() {
    $.ajax({
      url: url,
      type: "POST",
      dataType: "json",
      crossDomain: true,
      data: {
        accion: "update_status_canciones_pedidas",
        sucursal_id: sucursalId,
      },
      success: function (response) {
        console.log("=> ", response);
      },
      error: function (error, err) {
        console.log("ERROR: " + JSON.stringify(error), "ERR: " + err);
      },
    });
  }

  // AQUÍ INICIA TODO

  window.onload = init();

  function init() {
    if (localStorage.playerIsOpen == "true") {
      $(".actualizando").css({ display: "block" });
      $(".actualizando_mensaje").html("Hay otro reproductor abierto.");
    } else {
      setInterval(function () {
        localStorage.playerIsOpen = "true";
      }, 1000);
      checkQueueStatus();
      getQueue();
    }
  }

  window.addEventListener("beforeunload", function (e) {
    localStorage.playerIsOpen = "false";
  });

  window.onunload = function (e) {
    e.preventDefault();
    localStorage.playerIsOpen = "false";
  };

  $("#actualiza-catalogo").on("click", function () {
    if (confirm("¿Actualizar el catálogo?")) {
      actualizaCatalogo();
    }
  });

  $("#panic").on("click", function () {
    if (confirm("Reiniciar el reproductor?")) {
      pushPanicButton();
    }
  });

  function actualizaCatalogo() {
    $(".actualizando").css({ display: "block" });
    $.ajax({
      url: url_local,
      type: "GET",
      dataType: "json",
      // crossDomain: true,
      data: {
        accion: "update_db",
      },
      success: function (response) {
        location.reload(true);
      },
      error: function (error, dd) {
        console.log(error, dd);
        $(".actualizando").css({ display: "none" });
        alert("Error al actualizar");
      },
    });
  }

  function pushPanicButton() {
    $.ajax({
      url: url_local,
      type: "POST",
      dataType: "json",
      data: {
        accion: "panic_button",
        id_sucursal: sucursalId,
      },
      success: function (response) {
        console.log(response);
        location.reload(true);
      },
      error: function (error, dd) {
        console.log("Error: ", error, dd);
      },
    });
  }

  // La canción termina
  audio.addEventListener("ended", function () {
    // Envía la hora en que se tocó
    playedAt(cola[0].id);

    checkQueueStatus();
    clearInterval(tiempoRestante);
    // si hay elementos en cola
    if (cola.length) {
      // obtenemos el id de la canción
      var nextSong = parseInt(cola[0].id);
    }
    // borra el primer elemento de cola
    cola.shift();

    if (cola.length) {
      playSong(cola[0], cola[0].id);
    } else {
      audio.src = "";
      getQueue();
    }

    $("#cola").find("li:first").css("border", "1px solid").remove();
  });
});
