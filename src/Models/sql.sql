DROP TABLE IF EXISTS movimientos_saldo;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_usuario VARCHAR(20) NOT NULL DEFAULT 'Usuario',
    saldo DECIMAL(10,2) DEFAULT 0.00,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE movimientos_saldo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo_movimiento ENUM('Ingreso', 'Egreso') NOT NULL,
    descripcion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
