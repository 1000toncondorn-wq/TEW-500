<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: ' . redirect_dashboard());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || $password === '') {
        $error = 'Complete todos los campos.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.email, u.password_hash, r.nombre AS rol
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.email = ?'
        );
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'     => (int)$u['id'],
                'nombre' => $u['nombre'],
                'email'  => $u['email'],
                'rol'    => $u['rol'],
            ];
            header('Location: ' . redirect_dashboard());
            exit;
        }

        $error = 'Credenciales inválidas.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;margin:3rem auto;">
    <h1>Iniciar sesión</h1>
    <?php if ($error): ?>
        <div class="alert error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Contraseña</label>
        <input type="password" name="password" required>
        <button type="submit">Entrar</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>