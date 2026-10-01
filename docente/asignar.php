<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('docente');

$error = '';

$formularios = $pdo->query('SELECT id, titulo FROM formularios ORDER BY id DESC')->fetchAll();
$estudiantes = $pdo->query(
    "SELECT u.id, u.nombre, u.email
     FROM usuarios u
     JOIN roles r ON r.id = u.rol_id
     WHERE r.nombre = 'estudiante'
     ORDER BY u.nombre"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $formulario_id = (int)($_POST['formulario_id'] ?? 0);
    $estudiantesSel = $_POST['estudiantes'] ?? [];
    $fechaLimite = $_POST['fecha_limite'] ?? '';

    if (!$formulario_id || !is_array($estudiantesSel) || count($estudiantesSel) === 0 || $fechaLimite === '') {
        $error = 'Seleccione formulario, al menos un estudiante y fecha límite.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $fechaLimite);
        if (!$dt) {
            $error = 'Fecha límite inválida.';
        } else {
            $fechaSql = $dt->format('Y-m-d H:i:s');

            $stmt = $pdo->prepare(
                'INSERT INTO asignaciones (formulario_id, estudiante_id, docente_id, fecha_limite)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    docente_id = VALUES(docente_id),
                    fecha_limite = VALUES(fecha_limite)'
            );

            foreach ($estudiantesSel as $estId) {
                $stmt->execute([$formulario_id, (int)$estId, current_user()['id'], $fechaSql]);
            }

            flash('ok', 'Formulario asignado correctamente.');
            header('Location: ' . BASE_URL . '/docente/revisar.php');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Asignar formulario</h1>
<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>
<div class="card">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <label>Formulario</label>
        <select name="formulario_id" required>
            <option value="">Seleccione...</option>
            <?php foreach ($formularios as $f): ?>
                <option value="<?= (int)$f['id'] ?>"><?= h($f['titulo']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Fecha límite</label>
        <input type="datetime-local" name="fecha_limite" required>

        <label>Estudiantes</label>
        <?php foreach ($estudiantes as $e): ?>
            <label style="display:block">
                <input type="checkbox" name="estudiantes[]" value="<?= (int)$e['id'] ?>" style="width:auto">
                <?= h($e['nombre']) ?> — <?= h($e['email']) ?>
            </label>
        <?php endforeach; ?>

        <br>
        <button type="submit">Asignar</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>