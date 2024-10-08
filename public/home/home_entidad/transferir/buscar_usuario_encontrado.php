<?php
session_start();
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../../login');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buscar usuario</title>
    <link rel="stylesheet" href="../../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
        }
    </style>
</head>
<body>
<section class="buscar-usuario">
    <nav class="navbar">
        <a href="./index.php">
            <img src="../../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
    </nav>
    <div class="container">
        <form id="searchForm">
            <label for="usuario" class="h2">Buscar usuario</label>
            <input
                type="text"
                name="Dni_Nombre"
                id="usuario"
                placeholder="Busca por nombre o dni..."
                value=""
            />
            
            <div id="container-anteriores" class="container-anteriores">
            </div>

            <button type="submit" class="btn-primary submit--on" id="submitButton">
                Buscar cuenta
            </button>
        </form>
    </div>
</section>
<script>
// Función para obtener el valor de un parámetro de la URL
function getParameterByName(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

// Al cargar la página, verifica si 'Dni_Nombre' está en la URL y modifica el input
$(document).ready(function() {
    const dniNombre = getParameterByName('Dni_Nombre');
    if (dniNombre) {
        $("#usuario").val(dniNombre);  // Coloca el valor en el input
        $("#submitButton").prop("disabled", false); // Habilita el botón si tiene valor
        performSearch();  // Realiza la búsqueda automáticamente si viene de otra página con el parámetro
    }

    // Habilita el botón cuando el campo tiene 3 o más caracteres
    $("#usuario").on("input", function() {
        $("#submitButton").prop("disabled", $(this).val().trim().length < 3);
    });
});

function performSearch() {
    const dniNombre = $("#usuario").val().trim();

    // Si el input tiene menos de 3 caracteres, no realiza la búsqueda
    if (dniNombre.length < 3) {
        $("#container-anteriores").html("<p>Ingrese al menos 3 caracteres para buscar.</p>");
        return;
    }

    $.ajax({
        url: "buscar_usuario_logica.php", 
        type: "get",
        data: { Dni_Nombre: dniNombre },
        dataType: "json",
        success: function(data) {
            console.log("Datos recibidos:", data);
            const containerAnteriores = $("#container-anteriores");
            containerAnteriores.empty(); 
            containerAnteriores.append('<p class="h2">Anteriores transferencias</p>');

            if (data.usuarios.length > 0 || data.entidades.length > 0) {
                data.usuarios.forEach(usuario => {
                    const usuarioHtml = `
                        <div class="transferencia corto" onclick="window.location.href='procesar_transferencia.php?dni=${usuario.dni}'">
                        <img src="../../../img/user.svg" alt="Banco" />
                            <div class="left">
                                <div>
                                    <p class="h5">${usuario.nombre_apellido}</p>
                                    <p class="hb">DNI: ${usuario.dni}</p>
                                </div>
                            </div>
                            <div class="right">
                            </div>
                        </div>
                    `;
                    containerAnteriores.append(usuarioHtml);
                });

                data.entidades.forEach(entidad => {
                    const entidadHtml = `
                        <div class="transferencia" onclick="window.location.href='procesar_transferencia.php?cuit=${entidad.cuit}'"> 
                            <div class="left">
                                <img src="../../img/company.svg" alt="" />
                                <div>
                                    <p class="h5">${entidad.nombre_entidad}</p>
                                    <p class="hb">CUIT: ${entidad.cuit}</p>
                                </div>
                            </div>
                            <div class="right">
                            </div>
                        </div>
                    `;
                    containerAnteriores.append(entidadHtml);
                });
            } else {
                containerAnteriores.html("<p>No se encontraron resultados.</p>");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al buscar usuario:", error);
        }
    });
}

$("#searchForm").on("submit", function(e) {
    e.preventDefault();
    performSearch();
});
</script>
</body>
</html>
