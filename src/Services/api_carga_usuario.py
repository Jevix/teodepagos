import pandas as pd
import requests

# Función para leer el archivo Excel y preparar los datos de usuarios
def leer_usuarios_excel(archivo_excel):
    # Leer el archivo Excel y asegurar que la columna 'password' sea leída como texto
    df = pd.read_excel(archivo_excel, dtype={'password': str})

    usuarios = []
    for index, row in df.iterrows():
        # Validar y manejar valores faltantes o incorrectos
        password = str(row['password']) if pd.notna(row['password']) else "default_password"  # Usar "default_password" si no hay valor
        saldo = float(row['saldo']) if pd.notna(row['saldo']) else 0  # Convertir el saldo a float, 0 si está vacío

        # Convertir id_entidad a número si es posible, y manejar valores NaN
        try:
            id_entidad = int(float(row['id_entidad'])) if pd.notna(row['id_entidad']) else None
        except ValueError:
            id_entidad = None  # Si no puede convertirse a número, se maneja como None

        # Asegurarse que el tipo de usuario es 'Usuario' o 'Miembro', de lo contrario usar 'Usuario'
        tipo_usuario = row['tipo_usuario'] if row['tipo_usuario'] in ['Usuario', 'Miembro'] else 'Usuario'

        # Crear un diccionario con los datos del usuario
        usuario = {
            'nombre_apellido': row['nombre_apellido'],  # No separar el nombre y apellido
            'dni': str(row['dni']),  # Asegurarse de que el DNI sea una cadena
            'password': password,  # Usar el valor leído o el valor predeterminado
            'tipo_usuario': tipo_usuario,  # Asegurarse que sea 'Usuario' o 'Miembro'
            'id_entidad': id_entidad,  # Dejar el id_entidad como None si es un usuario sin entidad
            'saldo': saldo  # Asignar el saldo leído o 0 si está vacío
        }
        usuarios.append(usuario)  # Añadir cada usuario a la lista de usuarios

    return usuarios  # Devolver la lista de usuarios

# Función para enviar los datos de cada usuario a la API PHP
def enviar_usuarios_api(usuarios):
    url_php = "http://localhost/teodepagos/src/Services/api_carga_usuario.php"  # Cambiar la URL según tu configuración
    headers = {'Content-Type': 'application/json; charset=utf-8'}  # Definir los encabezados para la solicitud POST

    for usuario in usuarios:
        # Imprimir el usuario que estamos enviando para verificar que los datos sean correctos
        print(f"Enviando usuario: {usuario}")
        try:
            # Hacer la solicitud POST a la API PHP
            response = requests.post(url_php, json=usuario, headers=headers)

            # Comprobar la respuesta del servidor
            if response.status_code == 200:
                print(f"Usuario {usuario['nombre_apellido']} enviado correctamente. Respuesta: {response.text}")
            else:
                print(f"Error al enviar el usuario {usuario['nombre_apellido']}. Respuesta: {response.text}")
        except requests.exceptions.RequestException as e:
            # Manejar errores de conexión o solicitudes fallidas
            print(f"Error al conectar con la API: {e}")

if __name__ == '__main__':
    archivo_excel = 'usuarios.xlsx'  # Cambiar la ruta al archivo Excel según tu entorno
    usuarios = leer_usuarios_excel(archivo_excel)  # Leer los usuarios desde el archivo Excel
    enviar_usuarios_api(usuarios)  # Enviar los usuarios a la API
