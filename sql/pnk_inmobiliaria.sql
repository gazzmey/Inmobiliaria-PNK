-- ============================================================
-- PNK Inmobiliaria — Base de Datos
-- Plataforma inmobiliaria Región de Coquimbo
-- ============================================================

CREATE DATABASE IF NOT EXISTS pnk_inmobiliaria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pnk_inmobiliaria;

-- ------------------------------------------------------------
-- TABLA: usuarios
-- Administradores, Propietarios y Gestores Freelance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  rut             VARCHAR(15)  NOT NULL UNIQUE,
  nombres         VARCHAR(100) NOT NULL,
  apellido_paterno VARCHAR(100) NOT NULL,
  apellido_materno VARCHAR(100) DEFAULT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  telefono        VARCHAR(20)  DEFAULT NULL,
  fecha_nacimiento DATE        DEFAULT NULL,
  sexo            ENUM('M','F','O') DEFAULT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('admin','propietario','gestor') NOT NULL DEFAULT 'propietario',
  estado          ENUM('activo','pendiente','inactivo') NOT NULL DEFAULT 'pendiente',
  -- Campos específicos por rol
  nro_bienes_raices VARCHAR(50) DEFAULT NULL COMMENT 'Solo propietarios',
  penka_id          VARCHAR(20) DEFAULT NULL COMMENT 'Solo gestores — asignado por admin',
  certificado_path  VARCHAR(255) DEFAULT NULL COMMENT 'Ruta al certificado de antecedentes del gestor',
  -- Auditoría
  fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: propiedades
-- Inmuebles publicados en la plataforma
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS propiedades (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  codigo          VARCHAR(10)  NOT NULL UNIQUE,
  tipo            ENUM('casa','departamento','terreno') NOT NULL,
  provincia       ENUM('elqui','limari','choapa') NOT NULL,
  comuna          VARCHAR(60)  NOT NULL,
  sector          VARCHAR(100) NOT NULL,
  dormitorios     TINYINT UNSIGNED DEFAULT 0,
  banos           TINYINT UNSIGNED DEFAULT 0,
  area_terreno    DECIMAL(10,2) NOT NULL,
  area_construida DECIMAL(10,2) DEFAULT NULL,
  precio_pesos    BIGINT UNSIGNED NOT NULL,
  precio_uf       DECIMAL(12,2) NOT NULL,
  descripcion     TEXT DEFAULT NULL,
  estado          ENUM('activo','inactivo','vendida') NOT NULL DEFAULT 'activo',
  -- Amenidades
  bodega          TINYINT(1) DEFAULT 0,
  estacionamiento TINYINT(1) DEFAULT 0,
  logia           TINYINT(1) DEFAULT 0,
  cocina_amoblada TINYINT(1) DEFAULT 0,
  antejardin      TINYINT(1) DEFAULT 0,
  patio_trasero   TINYINT(1) DEFAULT 0,
  piscina         TINYINT(1) DEFAULT 0,
  -- Relaciones
  propietario_id  INT DEFAULT NULL,
  -- Auditoría
  fecha_publicacion DATE DEFAULT NULL,
  fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (propietario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: propiedades_fotos
-- Galería de imágenes por propiedad
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS propiedades_fotos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  propiedad_id INT NOT NULL,
  ruta         VARCHAR(255) NOT NULL,
  es_portada   TINYINT(1) DEFAULT 0,
  orden        TINYINT UNSIGNED DEFAULT 0,
  fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (propiedad_id) REFERENCES propiedades(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLA: solicitudes_visita
-- Solicitudes de visita a propiedades
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitudes_visita (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  propiedad_id  INT NOT NULL,
  nombre        VARCHAR(150) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  telefono      VARCHAR(20)  DEFAULT NULL,
  mensaje       TEXT DEFAULT NULL,
  fecha_visita  DATE DEFAULT NULL,
  estado        ENUM('pendiente','confirmada','rechazada','completada') NOT NULL DEFAULT 'pendiente',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (propiedad_id) REFERENCES propiedades(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- DATOS DE EJEMPLO
-- ============================================================

-- Admin por defecto (password: Admin123) — Todos los usuarios usan esta misma clave
INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, fecha_registro) VALUES
('12.345.678-9', 'Juan', 'García', 'Rodríguez', 'admin@pnk.cl', '+56 9 1234 5678', '1985-03-15', 'M',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'admin', 'activo', '2025-01-01 10:00:00');

-- Propietarios
INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, nro_bienes_raices, fecha_registro) VALUES
('23.456.789-0', 'María', 'López', 'Fernández', 'maria@correo.cl', '+56 9 8765 4321', '1990-07-22', 'F',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'propietario', 'activo', 'BR-2025-00123456', '2025-02-15 14:30:00'),
('56.789.012-3', 'Roberto', 'Núñez', 'Vargas', 'roberto@correo.cl', '+56 9 9876 5432', '1978-11-03', 'M',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'propietario', 'inactivo', 'BR-2025-00789012', '2025-01-05 09:15:00'),
('67.890.123-4', 'Ana', 'Martínez', 'Herrera', 'ana@correo.cl', '+56 9 2345 6789', '1995-05-18', 'F',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'propietario', 'pendiente', 'BR-2026-00345678', '2026-04-20 11:00:00');

-- Gestores
INSERT INTO usuarios (rut, nombres, apellido_paterno, apellido_materno, email, telefono, fecha_nacimiento, sexo, password_hash, rol, estado, penka_id, fecha_registro) VALUES
('34.567.890-1', 'Carlos', 'Muñoz', 'Silva', 'carlos@correo.cl', '+56 9 5678 9012', '1988-09-10', 'M',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'gestor', 'activo', 'PENKA-00342', '2025-03-10 16:45:00'),
('45.678.901-2', 'Patricia', 'González', 'Díaz', 'patricia@correo.cl', '+56 9 3456 7890', '1992-01-25', 'F',
 '$2y$10$PgaaCzWS.ZER7WQJ2SWrCOsg0jCRbOu8ilEeoRk3KXRozhlyAjA5u', 'gestor', 'pendiente', NULL, '2026-04-18 08:20:00');

-- Propiedades
INSERT INTO propiedades (codigo, tipo, provincia, comuna, sector, dormitorios, banos, area_terreno, area_construida, precio_pesos, precio_uf, descripcion, estado, bodega, estacionamiento, logia, cocina_amoblada, antejardin, patio_trasero, piscina, propietario_id, fecha_publicacion) VALUES
('C0001', 'casa', 'elqui', 'La Serena', 'El Milagro', 3, 2, 120.00, 110.00, 154000000, 3650.00,
 'Hermosa casa en sector El Milagro con excelente ubicación, cerca de colegios y comercio.', 'activo',
 1, 1, 0, 1, 1, 1, 0, 2, '2026-04-10'),
('C0002', 'departamento', 'elqui', 'La Serena', 'Barrio Inglés', 2, 1, 68.00, 65.00, 89000000, 2110.00,
 'Departamento moderno en el histórico Barrio Inglés, a pasos de la playa.', 'activo',
 0, 1, 1, 1, 0, 0, 0, 2, '2026-04-05'),
('C0003', 'terreno', 'limari', 'Ovalle', 'Centro', 0, 0, 500.00, NULL, 42000000, 998.00,
 'Terreno plano en el centro de Ovalle, ideal para proyecto comercial o residencial.', 'activo',
 0, 0, 0, 0, 0, 0, 0, 3, '2026-03-01'),
('C0004', 'casa', 'elqui', 'La Serena', 'Las Compañías', 4, 3, 180.00, 165.00, 235000000, 5570.00,
 'Amplia casa familiar en Las Compañías con piscina y jardín.', 'vendida',
 1, 1, 1, 1, 1, 1, 1, 2, '2026-01-15'),
('C0005', 'departamento', 'elqui', 'Coquimbo', 'Av. del Mar', 3, 2, 95.00, 90.00, 128000000, 3040.00,
 'Departamento con vista al mar en la Av. del Mar, Coquimbo.', 'activo',
 1, 1, 0, 0, 0, 0, 0, 3, '2026-04-20'),
('C0006', 'terreno', 'limari', 'Ovalle', 'Sector Agrícola', 0, 0, 2500.00, NULL, 75000000, 1780.00,
 'Terreno agrícola con acceso a agua, perfecto para cultivos o proyecto agro.', 'inactivo',
 0, 0, 0, 0, 0, 0, 0, 3, '2026-02-10');

-- Solicitudes de visita de ejemplo
INSERT INTO solicitudes_visita (propiedad_id, nombre, email, telefono, mensaje, fecha_visita, estado) VALUES
(1, 'Pedro Soto Ramírez', 'pedro@correo.cl', '+56 9 1111 2222', 'Me interesa visitar la casa, ¿disponible el sábado?', '2026-05-17', 'pendiente'),
(2, 'Laura Vega Castillo', 'laura@correo.cl', '+56 9 3333 4444', 'Quisiera ver el departamento lo antes posible.', '2026-05-15', 'pendiente'),
(4, 'Roberto Núñez Vargas', 'roberto@correo.cl', '+56 9 9876 5432', 'Interesado en la casa de Las Compañías.', '2026-05-10', 'pendiente');
