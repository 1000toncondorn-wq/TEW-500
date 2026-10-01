<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('administrador');

$id = (int)($_GET['id'] ?? 0);
$titulo = '';
$descripcion = '';
$tiempo = '';
$error = '';

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM formularios WHERE id = ?');
    $stmt->execute([$id]);
    $f = $stmt->fetch();
    if (!$f) {
        exit('Formulario no encontrado');
    }
    $titulo = $f['titulo'];
    $descripcion = $f['descripcion'];
    $tiempo = $f['tiempo_limite_minutos'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tiempoRaw = trim($_POST['tiempo_limite_minutos'] ?? '');
    $tiempo = $tiempoRaw === '' ? null : (int)$tiempoRaw;

    if ($titulo === '') {
        $error = 'El título es obligatorio.';
    } elseif ($tiempo !== null && $tiempo < 1) {
        $error = 'El tiempo debe ser mayor a 0 o dejarse vacío.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE formularios SET titulo = ?, descripcion = ?, tiempo_limite_minutos = ? WHERE id = ?'
            );
            $stmt->execute([$titulo, $descripcion, $tiempo, $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO formularios (titulo, descripcion, tiempo_limite_minutos, creador_id)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$titulo, $descripcion, $tiempo, current_user()['id']]);
            $id = (int)$pdo->lastInsertId();
        }

        flash('ok', 'Formulario guardado.');
        header('Location: ' . BASE_URL . '/admin/formularios.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1><?= $id ? 'Editar' : 'Crear' ?> formulario</h1>
<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>
<div class="card">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label>Título</label>
        <input name="titulo" value="<?= h($titulo) ?>" required>
        <label>Descripción</label>
        <textarea name="descripcion" rows="4"><?= h($descripcion) ?></textarea>
        <label>Tiempo límite en minutos (opcional)</label>
        <input type="number" name="tiempo_limite_minutos" min="1" value="<?= h((string)$tiempo) ?>">
        <button type="submit">Guardar</button>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/formularios.php">Volver</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>