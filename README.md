# TEW-500

Aplicación web en PHP 8 + MySQL/MariaDB para crear, asignar y resolver exámenes en línea.

## Roles
- Administrador
- Docente
- Estudiante

## Instalación local
1. Crear base de datos e importar `sql/schema.sql` (o pegar el código SQL directamente en la base de datos `examen` en phpMyAdmin).
2. Configurar credenciales en `config/db.php` (el nombre de la base de datos ya está configurado como `examen`).
3. Ajustar `BASE_URL` según la carpeta del proyecto (por defecto `/tew500`).
4. Abrir en el navegador: `http://localhost/tew500/login.php`.

## Credenciales de prueba
Contraseña para todos: `password`

| Rol | Email |
|---|---|
| Administrador | admin@tew.test |
| Docente | docente@tew.test |
| Estudiante 1 | estudiante1@tew.test |
| Estudiante 2 | estudiante2@tew.test |
| Estudiante 3 | estudiante3@tew.test |

## Seguridad implementada
- `password_hash()` y `password_verify()`.
- PDO con sentencias preparadas.
- Control de sesión y rol en cada página.
- Validación en servidor.
- Salida con `htmlspecialchars()`.
- Token CSRF en formularios.
- Interfaz responsiva.