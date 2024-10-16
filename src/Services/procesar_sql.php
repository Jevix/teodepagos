<?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Contraseña de seguridad
            $password_correcta = 'Javilol2018';  // Cambia esta contraseña a algo seguro

            // Comprobación de la contraseña ingresada
            $password = $_POST['password'];

            if ($password === $password_correcta) {
                // Incluir la clase Database
                class Database {
                    private $pdo;

                    public function __construct($host, $dbname, $user, $pass) {
                        try {
                            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                            $this->pdo = new PDO($dsn, $user, $pass);
                            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        } catch (PDOException $e) {
                            die("Error en la conexión a la base de datos: " . $e->getMessage());
                        }
                    }

                    public function getConnection() {
                        return $this->pdo;
                    }
                }

                // Parámetros de conexión a la base de datos
                $host = 'localhost';  // Cambia esto si tu host es diferente
                $dbname = 'teodepagos';  // Cambia esto al nombre de tu base de datos
                $user = 'root';  // Cambia esto a tu usuario de la base de datos
                $pass = '';  // Cambia esto a la contraseña de tu base de datos

                // Crear una instancia de la clase Database
                $db = new Database($host, $dbname, $user, $pass);
                $conn = $db->getConnection();

                // Comandos SQL sin triggers
                $sql_commands = "
                -- Borrar tablas si ya existen
                DROP TABLE IF EXISTS movimientos_saldo;
                DROP TABLE IF EXISTS usuarios;
                DROP TABLE IF EXISTS entidades;

                -- Crear tabla entidades
                CREATE TABLE entidades (
                    id_entidad INT AUTO_INCREMENT PRIMARY KEY,
                    nombre_entidad VARCHAR(50) NOT NULL,
                    cuit VARCHAR(11) NOT NULL UNIQUE,
                    tipo_entidad ENUM('Empresa', 'Banco') NOT NULL,
                    saldo DECIMAL(10,0) DEFAULT 0 CHECK (saldo >= 0)
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

                -- Crear tabla usuarios con referencia a entidades
                CREATE TABLE usuarios (
                    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
                    nombre_apellido VARCHAR(40) NOT NULL,
                    dni VARCHAR(8) UNIQUE NOT NULL,
                    password VARCHAR(8) NOT NULL,
                    tipo_usuario ENUM('Usuario', 'Miembro') NOT NULL,
                    id_entidad INT DEFAULT NULL,
                    saldo DECIMAL(10,0) DEFAULT 0 CHECK (saldo >= 0),
                    CONSTRAINT chk_id_entidad CHECK (
                        (tipo_usuario = 'Usuario' AND id_entidad IS NULL) OR 
                        (tipo_usuario = 'Miembro' AND id_entidad IS NOT NULL)
                    ),
                    FOREIGN KEY (id_entidad) REFERENCES entidades(id_entidad) ON DELETE SET NULL
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

                -- Crear tabla movimientos_saldo
                CREATE TABLE movimientos_saldo (
                    id_transaccion INT AUTO_INCREMENT PRIMARY KEY,
                    id_remitente_usuario INT DEFAULT NULL,
                    id_remitente_entidad INT DEFAULT NULL,
                    monto DECIMAL(10,0) NOT NULL CHECK (monto >= 1),
                    tipo_movimiento ENUM('Ingreso', 'Egreso', 'Prestamo','Recarga','Error') NOT NULL,
                    id_destinatario_usuario INT DEFAULT NULL,
                    id_destinatario_entidad INT DEFAULT NULL,
                    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_remitente_usuario) REFERENCES usuarios(id_usuario),
                    FOREIGN KEY (id_remitente_entidad) REFERENCES entidades(id_entidad),
                    FOREIGN KEY (id_destinatario_usuario) REFERENCES usuarios(id_usuario),
                    FOREIGN KEY (id_destinatario_entidad) REFERENCES entidades(id_entidad)
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

                -- Establecer el valor inicial del AUTO_INCREMENT
                ALTER TABLE movimientos_saldo AUTO_INCREMENT = 31415;
                ";

                try {
                    // Ejecutar los comandos SQL
                    $conn->exec($sql_commands);

                    // Triggers separados (sin DELIMITER)
                    $trigger_cuit = "
                    CREATE TRIGGER validar_cuit
                    BEFORE INSERT ON entidades
                    FOR EACH ROW
                    BEGIN
                        IF CHAR_LENGTH(NEW.cuit) != 11 OR NEW.cuit NOT REGEXP '^[0-9]+$' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'CUIT debe tener 11 dígitos y contener solo números';
                        END IF;
                    END;
                    ";

                    $trigger_dni = "
                    CREATE TRIGGER validar_dni
                    BEFORE INSERT ON usuarios
                    FOR EACH ROW
                    BEGIN
                        IF CHAR_LENGTH(NEW.dni) != 8 OR NEW.dni NOT REGEXP '^[0-9]+$' THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'DNI debe tener 8 dígitos y contener solo números';
                        END IF;
                    END;
                    ";

                    // Ejecutar los triggers
                    $conn->exec($trigger_cuit);
                    $conn->exec($trigger_dni);

                    echo "<p>Comandos SQL y triggers ejecutados correctamente.</p>";
                } catch (PDOException $e) {
                    echo "<p>Error al ejecutar comandos SQL: " . $e->getMessage() . "</p>";
                }

            } else {
                echo "<p>Contraseña incorrecta.</p>";
            }
        }
        ?>