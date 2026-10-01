<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('administrador');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM asignaciones WHERE formulario_id = ?');
    $stmt->execute([$id]);
    $asignado = (int)$stmt->fetchColumn();

    if ($asignado > 0) {
        flash('error', 'No se puede eliminar: el formulario ya fue asignado.');
    } else {
        $stmt = $pdo->prepare('DELETE FROM formularios WHERE id = ?');
        $stmt->execute([$id]);
        flash('ok', 'Formulario eliminado.');
    }

    header('Location: ' . BASE_URL . '/admin/formularios.php');
    exit;
}

$formularios = $pdo->query(
    'SELECT f.id, f.titulo, f.descripcion, f.tiempo_limite_minutos, f.creado_en,
            (SELECT COUNT(*) FROM preguntas p WHERE p.formulario_id = f.id) AS total_preguntas
     FROM formularios f
     ORDER BY f.id DESC'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1>Formularios</h1>
<a class="btn" href="<?= BASE_URL ?>/admin/formulario_form.php">Crear formulario</a>
<br><br>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Tiempo</th>
                <th>Preguntas</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($formularios as $f): ?>
            <tr>
                <td><?= (int)$f['id'] ?></td>
                <td><?= h($f['titulo']) ?></td>
                <td><?= $f['tiempo_limite_minutos'] ? (int)$f['tiempo_limite_minutos'] . ' min' : 'Sin límite' ?></td>
                <td><?= (int)$f['total_preguntas'] ?></td>
                <td>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/preguntas.php?formulario_id=<?= (int)$f['id'] ?>">Preguntas</a>
                    <a class="btn" href="<?= BASE_URL ?>/admin/formulario_form.php?id=<?= (int)$f['id'] ?>">Editar</a>
                    <a class="btn btn-danger" href="?delete=<?= (int)$f['id'] ?>"
                       onclick="return confirm('¿Eliminar formulario?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>