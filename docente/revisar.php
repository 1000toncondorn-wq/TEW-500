<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('docente');

$stmt = $pdo->prepare(
    'SELECT a.id, f.titulo, u.nombre AS estudiante, a.fecha_limite, a.completado,
            (SELECT SUM(r.puntaje_obtenido) FROM respuestas r WHERE r.asignacion_id = a.id) AS nota,
            (SELECT COUNT(*) FROM respuestas r WHERE r.asignacion_id = a.id AND r.calificada = 0) AS pendientes
     FROM asignaciones a
     JOIN formularios f ON f.id = a.formulario_id
     JOIN usuarios u ON u.id = a.estudiante_id
     WHERE a.docente_id = ?
     ORDER BY a.id DESC'
);
$stmt->execute([current_user()['id']]);
$asignaciones = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1>Revisar asignaciones</h1>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Formulario</th>
                <th>Estudiante</th>
                <th>Fecha límite</th>
                <th>Estado</th>
                <th>Nota</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?= (int)$a['id'] ?></td>
                <td><?= h($a['titulo']) ?></td>
                <td><?= h($a['estudiante']) ?></td>
                <td><?= h($a['fecha_limite']) ?></td>
                <td>
                    <?= $a['completado'] ? 'Respondido' : 'Pendiente' ?>
                    <?php if ((int)$a['pendientes'] > 0): ?>
                        <span class="pill">Por calificar</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['completado'] && (int)$a['pendientes'] === 0): ?>
                        <?= h((string)$a['nota']) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn" href="<?= BASE_URL ?>/docente/calificar.php?id=<?= (int)$a['id'] ?>">Calificar / Ver</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>