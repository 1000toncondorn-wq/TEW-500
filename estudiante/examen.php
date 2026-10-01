<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('estudiante');

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT a.*, f.titulo, f.descripcion, f.tiempo_limite_minutos
     FROM asignaciones a
     JOIN formularios f ON f.id = a.formulario_id
     WHERE a.id = ? AND a.estudiante_id = ?'
);
$stmt->execute([$id, current_user()['id']]);
$asignacion = $stmt->fetch();

if (!$asignacion) {
    exit('Examen no encontrado.');
}

if ((int)$asignacion['completado'] === 1) {
    exit('Este examen ya fue respondido.');
}

if (strtotime($asignacion['fecha_limite']) < time()) {
    exit('La fecha límite ya venció.');
}

$keyStart = 'examen_start_' . $id;
if (!empty($asignacion['tiempo_limite_minutos'])) {
    if (!isset($_SESSION[$keyStart])) {
        $_SESSION[$keyStart] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (!empty($asignacion['tiempo_limite_minutos'])) {
        $limite = (int)$asignacion['tiempo_limite_minutos'] * 60;
        if (time() - (int)$_SESSION[$keyStart] > $limite) {
            exit('El tiempo límite del examen ha expirado.');
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM preguntas WHERE formulario_id = ? ORDER BY id');
    $stmt->execute([$asignacion['formulario_id']]);
    $preguntas = $stmt->fetchAll();

    $pdo->beginTransaction();

    try {
        foreach ($preguntas as $p) {
            $name = 'pregunta_' . $p['id'];

            if ($p['tipo'] === 'multiple') {
                $opcion_id = (int)($_POST[$name] ?? 0);

                $stmt = $pdo->prepare(
                    'SELECT id, es_correcta FROM opciones WHERE id = ? AND pregunta_id = ?'
                );
                $stmt->execute([$opcion_id, $p['id']]);
                $op = $stmt->fetch();

                if (!$op) {
                    throw new Exception('Debe seleccionar una opción válida en todas las preguntas de selección múltiple.');
                }

                $puntaje = ((int)$op['es_correcta'] === 1) ? (float)$p['puntaje'] : 0;

                $stmt = $pdo->prepare(
                    'INSERT INTO respuestas (asignacion_id, pregunta_id, opcion_id, puntaje_obtenido, calificada)
                     VALUES (?, ?, ?, ?, 1)'
                );
                $stmt->execute([$id, $p['id'], $opcion_id, $puntaje]);
            } else {
                $texto = trim($_POST[$name] ?? '');
                if ($texto === '') {
                    throw new Exception('Debe responder todas las preguntas abiertas.');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO respuestas (asignacion_id, pregunta_id, texto_respuesta, puntaje_obtenido, calificada)
                     VALUES (?, ?, ?, NULL, 0)'
                );
                $stmt->execute([$id, $p['id'], $texto]);
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE asignaciones SET completado = 1, fecha_envio = NOW() WHERE id = ?'
        );
        $stmt->execute([$id]);

        $pdo->commit();

        unset($_SESSION[$keyStart]);
        flash('ok', 'Examen enviado correctamente.');
        header('Location: ' . BASE_URL . '/estudiante/resultado.php?id=' . $id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$stmt = $pdo->prepare('SELECT * FROM preguntas WHERE formulario_id = ? ORDER BY id');
$stmt->execute([$asignacion['formulario_id']]);
$preguntas = $stmt->fetchAll();

$opcionesPorPregunta = [];
foreach ($preguntas as $p) {
    if ($p['tipo'] === 'multiple') {
        $stmt = $pdo->prepare('SELECT * FROM opciones WHERE pregunta_id = ? ORDER BY id');
        $stmt->execute([$p['id']]);
        $opcionesPorPregunta[$p['id']] = $stmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1><?= h($asignacion['titulo']) ?></h1>
<p><?= nl2br(h($asignacion['descripcion'] ?? '')) ?></p>
<p><strong>Fecha límite:</strong> <?= h($asignacion['fecha_limite']) ?></p>
<?php if (!empty($asignacion['tiempo_limite_minutos'])): ?>
    <p><strong>Tiempo límite:</strong> <?= (int)$asignacion['tiempo_limite_minutos'] ?> minutos.</p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <?php foreach ($preguntas as $p): ?>
        <div class="card">
            <h3><?= h($p['enunciado']) ?></h3>
            <p><span class="pill"><?= h($p['tipo']) ?></span> Puntaje: <?= h((string)$p['puntaje']) ?></p>

            <?php if ($p['tipo'] === 'multiple'): ?>
                <?php foreach ($opcionesPorPregunta[$p['id']] as $op): ?>
                    <label style="display:block">
                        <input type="radio" name="pregunta_<?= (int)$p['id'] ?>" value="<?= (int)$op['id'] ?>" required style="width:auto">
                        <?= h($op['texto']) ?>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <textarea name="pregunta_<?= (int)$p['id'] ?>" rows="5" required></textarea>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit">Enviar examen</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>