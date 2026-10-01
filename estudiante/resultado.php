<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('estudiante');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT a.*, f.titulo,
            (SELECT SUM(r.puntaje_obtenido) FROM respuestas r WHERE r.asignacion_id = a.id) AS nota,
            (SELECT COUNT(*) FROM respuestas r WHERE r.asignacion_id = a.id AND r.calificada = 0) AS pendientes
     FROM asignaciones a
     JOIN formularios f ON f.id = a.formulario_id
     WHERE a.id = ? AND a.estudiante_id = ?'
);
$stmt->execute([$id, current_user()['id']]);
$a = $stmt->fetch();

if (!$a) {
    exit('Resultado no encontrado.');
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Resultado: <?= h($a['titulo']) ?></h1>
<div class="card">
    <?php if (!$a['completado']): ?>
        <p>Aún no ha respondido este examen.</p>
    <?php elseif ((int)$a['pendientes'] > 0): ?>
        <p>El docente aún no ha calificado todas las preguntas abiertas.</p>
    <?php else: ?>
        <p><strong>Nota final:</strong> <?= h((string)$a['nota']) ?></p>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/estudiante/dashboard.php">Volver</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>