<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('docente');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT a.*, f.titulo, u.nombre AS estudiante
     FROM asignaciones a
     JOIN formularios f ON f.id = a.formulario_id
     JOIN usuarios u ON u.id = a.estudiante_id
     WHERE a.id = ? AND a.docente_id = ?'
);
$stmt->execute([$id, current_user()['id']]);
$asignacion = $stmt->fetch();

if (!$asignacion) {
    exit('Asignación no encontrada o no le pertenece.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (($_POST['puntajes'] ?? []) as $respuesta_id => $puntaje) {
        $respuesta_id = (int)$respuesta_id;
        $puntaje = (float)$puntaje;

        $stmt = $pdo->prepare(
            'SELECT r.id, p.puntaje AS maximo
             FROM respuestas r
             JOIN preguntas p ON p.id = r.pregunta_id
             WHERE r.id = ? AND r.asignacion_id = ? AND p.tipo = "abierta"'
        );
        $stmt->execute([$respuesta_id, $id]);
        $r = $stmt->fetch();

        if ($r && $puntaje >= 0 && $puntaje <= (float)$r['maximo']) {
            $stmt = $pdo->prepare(
                'UPDATE respuestas SET puntaje_obtenido = ?, calificada = 1 WHERE id = ?'
            );
            $stmt->execute([$puntaje, $respuesta_id]);
        }
    }

    flash('ok', 'Calificaciones guardadas.');
    header('Location: ' . BASE_URL . '/docente/calificar.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT r.*, p.enunciado, p.tipo, p.puntaje AS puntaje_maximo,
            o.texto AS opcion_texto,
            (SELECT texto FROM opciones WHERE pregunta_id = p.id AND es_correcta = 1 LIMIT 1) AS correcta_texto
     FROM respuestas r
     JOIN preguntas p ON p.id = r.pregunta_id
     LEFT JOIN opciones o ON o.id = r.opcion_id
     WHERE r.asignacion_id = ?
     ORDER BY p.id'
);
$stmt->execute([$id]);
$respuestas = $stmt->fetchAll();

$nota = 0;
$pendientes = 0;
foreach ($respuestas as $r) {
    $nota += (float)($r['puntaje_obtenido'] ?? 0);
    if ((int)$r['calificada'] === 0) {
        $pendientes++;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Calificar: <?= h($asignacion['titulo']) ?></h1>
<p><strong>Estudiante:</strong> <?= h($asignacion['estudiante']) ?></p>
<p><strong>Nota actual:</strong> <?= h((string)$nota) ?> · <strong>Pendientes:</strong> <?= (int)$pendientes ?></p>

<form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <?php foreach ($respuestas as $r): ?>
        <div class="card">
            <h3><?= h($r['enunciado']) ?></h3>
            <p><span class="pill"><?= h($r['tipo']) ?></span> Puntaje máximo: <?= h((string)$r['puntaje_maximo']) ?></p>

            <?php if ($r['tipo'] === 'multiple'): ?>
                <p><strong>Respuesta:</strong> <?= h($r['opcion_texto'] ?? 'Sin respuesta') ?></p>
                <p><strong>Correcta:</strong> <?= h($r['correcta_texto'] ?? '') ?></p>
                <p><strong>Puntaje obtenido:</strong> <?= h((string)$r['puntaje_obtenido']) ?></p>
            <?php else: ?>
                <p><strong>Respuesta del estudiante:</strong></p>
                <blockquote><?= nl2br(h($r['texto_respuesta'] ?? '')) ?></blockquote>
                <label>Puntaje asignado (0 a <?= h((string)$r['puntaje_maximo']) ?>)</label>
                <input type="number" step="0.01" min="0" max="<?= h((string)$r['puntaje_maximo']) ?>"
                       name="puntajes[<?= (int)$r['id'] ?>]"
                       value="<?= h((string)($r['puntaje_obtenido'] ?? '')) ?>">
                <p>Estado: <?= $r['calificada'] ? 'Calificada' : 'Pendiente' ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit">Guardar calificaciones</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>