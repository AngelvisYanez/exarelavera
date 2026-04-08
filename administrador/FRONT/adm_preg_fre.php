<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preguntas y Respuestas</title>
    <style>
        /* Estilos aquí */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #3498db;
            color: #fff;
            text-align: center;
            padding: 20px 0;
        }

        section {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2, h3 {
            color: #3498db;
        }

        h2 {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: #fff;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #2980b9;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .modal-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .close {
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            color: #333;
        }

        #listaPreguntas div {
            margin-bottom: 10px;
            cursor: pointer;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        #listaPreguntas div:hover {
            background-color: #f9f9f9;
        }

        #modalRespuestas p {
            margin: 5px 0;
        }

    #buscarContainer {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        #buscador {
            width: calc(100% - 40px);
            padding: 10px;
            box-sizing: border-box;
        }

        #buscarBtn {
            width: 40px;
            padding: 10px;
            box-sizing: border-box;
            background-color: #3498db;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        #buscarBtn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

    <header>
        <h1>Preguntas y Respuestas</h1>
    </header>

    <section>
        <div style="display: flex; gap: 20px; align-items: center;">
            <h2>Buscar Preguntas:</h2>
            <input type="text" id="buscador" oninput="buscarPreguntas()">
            <button onclick="mostrarFormulario()">Añadir Nueva Pregunta</button>
        </div>

        <div id="listaPreguntas">
            <!-- Aquí se mostrarán las preguntas -->
        </div>

        <div id="respuestas" class="modal">
            <div class="modal-content">
                <span class="close" onclick="cerrarRespuestasModal()">&times;</span>
                <h2 id="modalAsunto"></h2>
                <p id="modalMensaje"></p>
                <h3>Respuestas:</h3>
                <div id="modalRespuestas"></div>
                <h3>Responder a la pregunta:</h3>
                <textarea id="modalRespuesta" rows="4" placeholder="Escribe tu respuesta"></textarea><br>
                <button onclick="enviarModalRespuesta()">Enviar Respuesta</button>
            </div>
        </div>

        <div id="formulario" style="display: none;">
            <h2>Nueva Pregunta:</h2>
            <form id="preguntaForm">
                <label for="asunto">Asunto:</label>
                <input type="text" id="asunto" name="asunto" required><br>

                <label for="mensaje">Mensaje:</label>
                <textarea id="mensaje" name="mensaje" rows="4" required></textarea><br>

                <button type="button" onclick="enviarPregunta()">Enviar Pregunta</button>
                <button type="button" onclick="cancelar()">Cancelar</button>
            </form>
        </div>
    </section>

    <script>
        var preguntas = [];

        function buscarPreguntas() {
            var buscador = document.getElementById('buscador').value.toLowerCase();
            var listaPreguntas = document.getElementById('listaPreguntas');

            listaPreguntas.innerHTML = '';

            preguntas.forEach(function(pregunta) {
                if (pregunta.asunto.toLowerCase().includes(buscador) || pregunta.mensaje.toLowerCase().includes(buscador)) {
                    var div = document.createElement('div');
                    div.innerHTML = '<strong>' + pregunta.asunto + ':</strong> <button onclick="mostrarDetalles(' + preguntas.indexOf(pregunta) + ')">Ver Detalles</button>';
                    listaPreguntas.appendChild(div);
                }
            });
        }

        function mostrarFormulario() {
            document.getElementById('formulario').style.display = 'block';
        }

        function cancelar() {
            document.getElementById('formulario').style.display = 'none';
            document.getElementById('preguntaForm').reset();
        }

        function enviarPregunta() {
            var asunto = document.getElementById('asunto').value;
            var mensaje = document.getElementById('mensaje').value;

            var nuevaPregunta = { asunto: asunto, mensaje: mensaje, respuestas: [] };
            preguntas.push(nuevaPregunta);

            buscarPreguntas();
            cancelar();
        }

        function mostrarDetalles(index) {
            var pregunta = preguntas[index];
            var modalAsunto = document.getElementById('modalAsunto');
            var modalMensaje = document.getElementById('modalMensaje');
            var modalRespuestas = document.getElementById('modalRespuestas');

            modalAsunto.textContent = pregunta.asunto;
            modalMensaje.textContent = pregunta.mensaje;

            modalRespuestas.innerHTML = '<h3>Respuestas:</h3>';
            if (pregunta.respuestas.length > 0) {
                pregunta.respuestas.forEach(function(respuesta) {
                    modalRespuestas.innerHTML += '<p>' + respuesta + '</p>';
                });
            } else {
                modalRespuestas.innerHTML += '<p>No hay respuestas aún.</p>';
            }

            document.getElementById('modalRespuesta').value = ''; // Limpiar el campo de respuesta en el modal

            // Mostrar modal
            document.getElementById('respuestas').style.display = 'block';
        }

        function cerrarRespuestasModal() {
            document.getElementById('respuestas').style.display = 'none';
        }

        function enviarModalRespuesta() {
            var modalAsunto = document.getElementById('modalAsunto').textContent;
            var modalRespuesta = document.getElementById('modalRespuesta').value;

            var pregunta = preguntas.find(function(item) {
                return item.asunto === modalAsunto;
            });

            pregunta.respuestas.push(modalRespuesta);
            mostrarDetalles(preguntas.indexOf(pregunta));
        }
    </script>

</body>
</html>
