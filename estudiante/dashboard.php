<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('estudiante');

$stmt = $pdo->prepare(
    'SELECT a.id, f.titulo, f.descripcion, a.fecha_limite, a.completado,
            (SELECT SUM(r.puntaje_obtenido) FROM respuestas r WHERE r.asignacion_id = a.id) AS nota,
            (SELECT COUNT(*) FROM respuestas r WHERE r.asignacion_id = a.id AND r.calificada = 0) AS pendientes
     FROM asignaciones a
     JOIN formularios f ON f.id = a.formulario_id
     WHERE a.estudiante_id = ?
     ORDER BY a.fecha_limite ASC'
);
$stmt->execute([current_user()['id']]);
$asignaciones = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1>Mis exámenes</h1>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Formulario</th>
                <th>Fecha límite</th>
                <th>Estado</th>
                <th>Nota</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= h($a['titulo']) ?></td>
                <td><?= h($a['fecha_limite']) ?></td>
                <td><?= $a['completado'] ? 'Respondido' : 'Pendiente' ?></td>
                <td>
                    <?php if ($a['completado'] && (int)$a['pendientes'] === 0): ?>
                        <?= h((string)$a['nota']) ?>
                    <?php elseif ($a['completado']): ?>
                        Pendiente de calificación
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$a['completado']): ?>
                        <a class="btn" href="<?= BASE_URL ?>/estudiante/examen.php?id=<?= (int)$a['id'] ?>">Responder</a>
                    <?php else: ?>
                        <a class="btn btn-secondary" href="<?= BASE_URL ?>/estudiante/resultado.php?id=<?= (int)$a['id'] ?>">Ver resultado</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>