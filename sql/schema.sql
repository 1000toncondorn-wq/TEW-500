-- NO se incluye CREATE DATABASE ni USE porque ya estás dentro de la base de datos `examen`
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS respuestas, asignaciones, opciones, preguntas, formularios, usuarios, roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (id, nombre) VALUES
(1, 'administrador'),
(2, 'docente'),
(3, 'estudiante');

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_roles
        FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- Todos los usuarios de prueba tienen contraseña: password
INSERT INTO usuarios (id, nombre, email, password_hash, rol_id) VALUES
(1, 'Administrador', 'admin@tew.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 'Docente Prueba', 'docente@tew.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
(3, 'Estudiante Uno', 'estudiante1@tew.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
(4, 'Estudiante Dos', 'estudiante2@tew.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
(5, 'Estudiante Tres', 'estudiante3@tew.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

CREATE TABLE formularios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tiempo_limite_minutos INT NULL,
    creador_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_formularios_creador
        FOREIGN KEY (creador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

INSERT INTO formularios (id, titulo, descripcion, tiempo_limite_minutos, creador_id) VALUES
(1, 'Examen de Tecnología Web II', 'Examen de prueba con preguntas múltiples y abiertas.', 30, 1);

CREATE TABLE preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    enunciado TEXT NOT NULL,
    tipo ENUM('multiple', 'abierta') NOT NULL,
    puntaje DECIMAL(5,2) NOT NULL DEFAULT 1,
    CONSTRAINT fk_preguntas_formulario
        FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO preguntas (id, formulario_id, enunciado, tipo, puntaje) VALUES
(1, 1, '¿Qué lenguaje se ejecuta en el servidor?', 'multiple', 2),
(2, 1, '¿Qué función de PHP se usa para hashear contraseñas?', 'multiple', 2),
(3, 1, '¿Qué técnica evita la inyección SQL?', 'multiple', 2),
(4, 1, 'Explique qué es una sesión en PHP.', 'abierta', 3),
(5, 1, 'Describa la diferencia entre GET y POST.', 'abierta', 3);

CREATE TABLE opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_opciones_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO opciones (pregunta_id, texto, es_correcta) VALUES
(1, 'PHP', 1),
(1, 'HTML', 0),
(1, 'CSS', 0),
(2, 'password_hash()', 1),
(2, 'md5()', 0),
(2, 'sha1()', 0),
(3, 'Sentencias preparadas', 1),
(3, 'Concatenación de strings', 0),
(3, 'Consultas directas', 0);

CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    docente_id INT NOT NULL,
    fecha_limite DATETIME NOT NULL,
    completado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_envio DATETIME NULL,
    UNIQUE KEY uq_formulario_estudiante (formulario_id, estudiante_id),
    CONSTRAINT fk_asignaciones_formulario
        FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE,
    CONSTRAINT fk_asignaciones_estudiante
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_asignaciones_docente
        FOREIGN KEY (docente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

INSERT INTO asignaciones (formulario_id, estudiante_id, docente_id, fecha_limite) VALUES
(1, 3, 2, DATE_ADD(NOW(), INTERVAL 7 DAY)),
(1, 4, 2, DATE_ADD(NOW(), INTERVAL 7 DAY)),
(1, 5, 2, DATE_ADD(NOW(), INTERVAL 7 DAY));

CREATE TABLE respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asignacion_id INT NOT NULL,
    pregunta_id INT NOT NULL,
    opcion_id INT NULL,
    texto_respuesta TEXT NULL,
    puntaje_obtenido DECIMAL(5,2) NULL,
    calificada TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_asignacion_pregunta (asignacion_id, pregunta_id),
    CONSTRAINT fk_respuestas_asignacion
        FOREIGN KEY (asignacion_id) REFERENCES asignaciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_opcion
        FOREIGN KEY (opcion_id) REFERENCES opciones(id) ON DELETE SET NULL
) ENGINE=InnoDB;