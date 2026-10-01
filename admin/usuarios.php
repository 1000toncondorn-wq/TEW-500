<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('administrador');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombre = trim($_POST['nombre'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? '';

    if ($nombre === '' || !$email || strlen($password) < 6 || !in_array($rol, ['docente', 'estudiante'], true)) {
        $error = 'Datos inválidos. La contraseña debe tener al menos 6 caracteres.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE nombre = ?');
        $stmt->execute([$rol]);
        $rol_id = $stmt->fetchColumn();

        if ($rol_id) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO usuarios (nombre, email, password_hash, rol_id) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$nombre, $email, $hash, $rol_id]);
                flash('ok', 'Usuario creado correctamente.');
                header('Location: ' . BASE_URL . '/admin/usuarios.php');
                exit;
            } catch (PDOException $e) {
                $error = 'El email ya está registrado.';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)current_user()['id']) {
        try {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND rol_id != 1');
            $stmt->execute([$id]);
            flash('ok', 'Usuario eliminado.');
        } catch (PDOException $e) {
            flash('error', 'No se puede eliminar: tiene registros asociados.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/usuarios.php');
    exit;
}

$usuarios = $pdo->query(
    'SELECT u.id, u.nombre, u.email, r.nombre AS rol
     FROM usuarios u
     JOIN roles r ON r.id = u.rol_id
     ORDER BY u.id DESC'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1>Gestión de usuarios</h1>

<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Crear docente o estudiante</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label>Nombre</label>
        <input name="nombre" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Contraseña</label>
        <input type="password" name="password" required minlength="6">
        <label>Rol</label>
        <select name="rol" required>
            <option value="docente">Docente</option>
            <option value="estudiante">Estudiante</option>
        </select>
        <button type="submit">Crear usuario</button>
    </form>
</div>

<div class="card">
    <h2>Usuarios registrados</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= h($u['nombre']) ?></td>
                <td><?= h($u['email']) ?></td>
                <td><span class="pill"><?= h($u['rol']) ?></span></td>
                <td>
                    <?php if ($u['rol'] !== 'administrador'): ?>
                        <a class="btn btn-danger" href="?delete=<?= (int)$u['id'] ?>"
                           onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>