// Confirmar que el archivo se ha cargado correctamente
console.log('basico.js cargado correctamente');

// Variable de control para evitar múltiples lecturas del mismo QR
let scannerActivo = true;

// Función que se llama cuando se lee un código QR correctamente
function lecturaCorrecta(codigoTexto, codigoObjeto) {
  if (scannerActivo) {
    scannerActivo = false; // Desactivar escáner para evitar múltiples lecturas

    console.log(`Código QR leído: ${codigoTexto}`, codigoObjeto);

    // Validar si el código contiene 8 o 11 números
    if (/^\d{8,11}$/.test(codigoTexto)) {
      let parametro;

      if (codigoTexto.length === 8) {
        // Si el código tiene 8 dígitos, se trata de un DNI
        parametro = `dni=${encodeURIComponent(codigoTexto)}`;
      } else if (codigoTexto.length === 11) {
        // Si el código tiene 11 dígitos, se trata de un CUIT
        parametro = `cuit=${encodeURIComponent(codigoTexto)}`;
      }

      // Redirigir a otra página con el parámetro adecuado
      window.location.href = `../../../home/home_entidad/transferir/procesar_transferencia.php?${parametro}`;
    } else {
      // Mostrar mensaje de error y reactivar el escáner al aceptar
      swal.fire({
        title: "Error",
        text: "El Código QR no contiene entre 8 y 11 números",
        icon: "error",
        confirmButtonText: "Aceptar"
      }).then(() => {
        // Reactivar el escáner después de que el usuario haga clic en "Aceptar"
        scannerActivo = true;
      });
    }
  }
}

// Función para manejar errores en el escaneo
function errorLectura(error) {
  console.error(error);
}

// Inicializar el escáner de QR
let html5QrcodeScanner = new Html5QrcodeScanner(
  "reader", 
  { fps: 10, qrbox: { width: 250, height: 250 } },
  /* verbose= */ false
);

// Renderizar el escáner en el elemento con el id "reader"
html5QrcodeScanner.render(lecturaCorrecta, errorLectura);
