import pandas as pd
import requests

# Leer el archivo Excel
def leer_entidades_excel(archivo_excel):
    # Lee el archivo Excel, asumiendo que la primera hoja contiene los datos
    df = pd.read_excel(archivo_excel)
    
    # Asegúrate de que las columnas del archivo Excel coincidan con los nombres esperados
    entidades = []
    for index, row in df.iterrows():
        entidad = {
            'nombre_entidad': row['nombre_entidad'],
            'cuit': row['cuit'],
            'tipo_entidad': row['tipo_entidad'],
            'saldo': row['saldo']
        }
        entidades.append(entidad)
    return entidades

# Enviar cada entidad a la API
def enviar_entidades_api(entidades):
    url_php = "http://localhost/teodepagos/src/Services/api_carga_entidades.php"  # Cambia esta URL a la correcta
    headers = {'Content-Type': 'application/json; charset=utf-8'}  # Encabezado para JSON en UTF-8
    
    for entidad in entidades:
        try:
            response = requests.post(url_php, json=entidad, headers=headers)
            
            # Mostrar la respuesta del servidor PHP para verificar problemas
            if response.status_code == 200:
                print(f"Entidad {entidad['nombre_entidad']} enviada correctamente. Respuesta: {response.text}")
            else:
                print(f"Error al enviar la entidad {entidad['nombre_entidad']}. Status Code: {response.status_code}. Respuesta: {response.text}")
        
        except requests.exceptions.RequestException as e:
            print(f"Error de conexión al enviar la entidad {entidad['nombre_entidad']}: {e}")

if __name__ == '__main__':
    archivo_excel = 'empresa.xlsx'  # Ruta a tu archivo Excel
    entidades = leer_entidades_excel(archivo_excel)
    
    if entidades:
        enviar_entidades_api(entidades)
    else:
        print("No se encontraron entidades para enviar.")
