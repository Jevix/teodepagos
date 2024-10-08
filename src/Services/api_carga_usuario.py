import pandas as pd
import requests

# Leer el archivo Excel
def leer_usuarios_excel(archivo_excel):
    # Lee el archivo Excel, especificando que la columna 'password' se lea como texto (cadena)
    df = pd.read_excel(archivo_excel, dtype={'password': str})
    
    # Asegúrate de que las columnas del archivo Excel coincidan con los nombres esperados
    usuarios = []
    for index, row in df.iterrows():
        # Validar y manejar valores 'nan' o incorrectos
        password = str(row['password']) if pd.notna(row['password']) else "default_password"  # Mantener como string
        
        # Convertir id_entidad a número si es posible, y manejar valores NaN
        try:
            id_entidad = int(float(row['id_entidad'])) if pd.notna(row['id_entidad']) else None
        except ValueError:
            id_entidad = None  # Si no puede convertirse a número, se maneja como None
        
        # Manejo del tipo de usuario (asegurarse que es 'Usuario' o 'Miembro')
        tipo_usuario = row['tipo_usuario'] if row['tipo_usuario'] in ['Usuario', 'Miembro'] else 'Usuario'

        usuario = {
            'nombre_apellido': row['nombre_apellido'],  # Ya no separamos nombre y apellido
            'dni': str(row['dni']),  # Aseguramos que sea una cadena ya que es varchar en la BD
            'password': password,
            'tipo_usuario': tipo_usuario,
            'id_entidad': id_entidad  # Asegúrate de que id_entidad sea un entero
        }
        usuarios.append(usuario)
    return usuarios

# Enviar cada usuario a la API
def enviar_usuarios_api(usuarios):
    url_php = "http://localhost/teodepagos/src/Services/api_carga_usuario.php"  # Cambia esta URL a la correcta
    headers = {'Content-Type': 'application/json; charset=utf-8'}  # Encabezado para JSON en UTF-8
    for usuario in usuarios:
        # Imprimir los datos antes de enviarlos para ver qué estás enviando
        print(f"Enviando usuario: {usuario}")
        try:
            response = requests.post(url_php, json=usuario, headers=headers)
        
            # Mostrar la respuesta del servidor PHP para verificar problemas
            if response.status_code == 200:
                print(f"Usuario {usuario['nombre_apellido']} enviado correctamente. Respuesta: {response.text}")
            else:
                print(f"Error al enviar el usuario {usuario['nombre_apellido']}. Respuesta: {response.text}")
        except requests.exceptions.RequestException as e:
            print(f"Error al conectar con la API: {e}")

if __name__ == '__main__':
    archivo_excel = 'usuarios.xlsx'  # Ruta a tu archivo Excel
    usuarios = leer_usuarios_excel(archivo_excel)
    enviar_usuarios_api(usuarios)
