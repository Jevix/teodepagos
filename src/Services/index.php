<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejecutar Comandos SQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        .container h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ejecutar Comandos SQL</h2>
        <form id="sqlForm">
            <div class="form-group">
                <label for="password">Contraseña de seguridad:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Ejecutar Comandos SQL</button>
            </div>
        </form>

        <!-- Botones para cargar entidades y usuarios -->
        <form id="cargarEntidadesForm">
            <button type="button" id="cargarEntidadesBtn">Cargar Entidades</button>
        </form>
        
        <form id="cargarUsuariosForm" method="get" action="api_carga_usuario.php">
    <input type="hidden" name="accion" value="cargar_usuarios">
    <button type="submit" id="cargarUsuariosBtn">Cargar Usuarios</button>
</form>

        <div id="resultado"></div>
    </div>

    <script>
        // Función para enviar datos en formato JSON usando fetch API
        function enviarDatosJSON(url, data) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.text())
            .then(result => {
                document.getElementById('resultado').innerHTML = result;
            })
            .catch(error => console.error('Error:', error));
        }

        // Manejador del formulario de SQL
        document.getElementById('sqlForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const password = document.getElementById('password').value;
            const data = { password: password };
            enviarDatosJSON('procesar_sql.php', data);
        });

        // Manejador para cargar entidades
        document.getElementById('cargarEntidadesBtn').addEventListener('click', function() {
            fetch('api_carga_entidades.php')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('resultado').innerHTML = result;
                })
                .catch(error => console.error('Error:', error));
        });

        // Manejador para cargar usuarios
        document.getElementById('cargarUsuariosBtn').addEventListener('click', function() {
            fetch('api_carga_usuario.php')
                .then(response => response.text())
                .then(result => {
                    document.getElementById('resultado').innerHTML = result;
                })
                .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>
