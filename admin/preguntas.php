<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('administrador');

$formulario_id = (int)($_GET['formulario_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM formularios WHERE id = ?');
$stmt->execute([$formulario_id]);
$formulario = $stmt->fetch();

if (!$formulario) {
    exit('Formulario no encontrado');
}

$error = '';
$editar_id = (int)($_GET['editar'] ?? 0);

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM preguntas WHERE id = ? AND formulario_id = ?');
    $stmt->execute([$id, $formulario_id]);
    flash('ok', 'Pregunta eliminada.');
    header('Location: ' . BASE_URL . '/admin/preguntas.php?formulario_id=' . $formulario_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $pregunta_id = (int)($_POST['pregunta_id'] ?? 0);
    $enunciado = trim($_POST['enunciado'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $puntaje = (float)($_POST['puntaje'] ?? 0);

    try {
        if ($enunciado === '' || !in_array($tipo, ['multiple', 'abierta'], true) || $puntaje <= 0) {
            throw new Exception('Complete enunciado, tipo y puntaje mayor a 0.');
        }

        $opciones = [];
        $correcta = -1;

        if ($tipo === 'multiple') {
            $opciones = array_values(array_filter(
                array_map('trim', $_POST['opciones'] ?? []),
                fn($x) => $x !== ''
            ));
            $correcta = (int)($_POST['correcta'] ?? -1);

            if (count($opciones) < 3 || $correcta < 0 || $correcta >= count($opciones)) {
                throw new Exception('Selección múltiple requiere al menos 3 opciones y marcar una correcta.');
            }
        }

        $pdo->beginTransaction();

        if ($pregunta_id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE preguntas SET enunciado = ?, tipo = ?, puntaje = ?
                 WHERE id = ? AND formulario_id = ?'
            );
            $stmt->execute([$enunciado, $tipo, $puntaje, $pregunta_id, $formulario_id]);

            $stmt = $pdo->prepare('DELETE FROM opciones WHERE pregunta_id = ?');
            $stmt->execute([$pregunta_id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO preguntas (formulario_id, enunciado, tipo, puntaje) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$formulario_id, $enunciado, $tipo, $puntaje]);
            $pregunta_id = (int)$pdo->lastInsertId();
        }

        if ($tipo === 'multiple') {
            $stmt = $pdo->prepare(
                'INSERT INTO opciones (pregunta_id, texto, es_correcta) VALUES (?, ?, ?)'
            );
            foreach ($opciones as $i => $texto) {
                $stmt->execute([$pregunta_id, $texto, $i === $correcta ? 1 : 0]);
            }
        }

        $pdo->commit();
        flash('ok', 'Pregunta guardada.');
        header('Location: ' . BASE_URL . '/admin/preguntas.php?formulario_id=' . $formulario_id);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$preguntas = $pdo->prepare(
    'SELECT * FROM preguntas WHERE formulario_id = ? ORDER BY id ASC'
);
$preguntas->execute([$formulario_id]);
$preguntas = $preguntas->fetchAll();

$editPregunta = null;
$editOpciones = [];
if ($editar_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM preguntas WHERE id = ? AND formulario_id = ?');
    $stmt->execute([$editar_id, $formulario_id]);
    $editPregunta = $stmt->fetch();

    if ($editPregunta && $editPregunta['tipo'] === 'multiple') {
        $stmt = $pdo->prepare('SELECT * FROM opciones WHERE pregunta_id = ? ORDER BY id ASC');
        $stmt->execute([$editar_id]);
        $editOpciones = $stmt->fetchAll();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<h1>Preguntas de: <?= h($formulario['titulo']) ?></h1>
<a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/formularios.php">Volver a formularios</a>
<br><br>

<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= $editPregunta ? 'Editar' : 'Agregar' ?> pregunta</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="pregunta_id" value="<?= $editPregunta ? (int)$editPregunta['id'] : 0 ?>">

        <label>Enunciado</label>
        <textarea name="enunciado" rows="3" required><?= h($editPregunta['enunciado'] ?? '') ?></textarea>

        <label>Tipo</label>
        <select name="tipo" id="tipo" required onchange="toggleOpciones()">
            <option value="multiple" <?= ($editPregunta['tipo'] ?? '') === 'multiple' ? 'selected' : '' ?>>Selección múltiple</option>
            <option value="abierta" <?= ($editPregunta['tipo'] ?? '') === 'abierta' ? 'selected' : '' ?>>Pregunta abierta</option>
        </select>

        <label>Puntaje</label>
        <input type="number" step="0.01" min="0.01" name="puntaje" required
               value="<?= h((string)($editPregunta['puntaje'] ?? '1')) ?>">

        <div id="bloqueMultiple">
            <p><strong>Opciones (mínimo 3). Marque la correcta.</strong></p>
            <?php
            $valores = [];
            $correctaIdx = -1;
            foreach ($editOpciones as $i => $op) {
                $valores[$i] = $op['texto'];
                if ((int)$op['es_correcta'] === 1) {
                    $correctaIdx = $i;
                }
            }
            for ($i = 0; $i < 5; $i++):
                $texto = $valores[$i] ?? '';
            ?>
                <label>Opción <?= $i + 1 ?></label>
                <input name="opciones[]" value="<?= h($texto) ?>">
                <label>
                    <input type="radio" name="correcta" value="<?= $i ?>"
                        <?= $correctaIdx === $i ? 'checked' : '' ?> style="width:auto">
                    Correcta
                </label>
            <?php endfor; ?>
        </div>

        <button type="submit">Guardar pregunta</button>
        <?php if ($editPregunta): ?>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/preguntas.php?formulario_id=<?= $formulario_id ?>">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Listado</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Enunciado</th>
                <th>Tipo</th>
                <th>Puntaje</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($preguntas as $p): ?>
            <tr>
                <td><?= (int)$p['id'] ?></td>
                <td><?= h($p['enunciado']) ?></td>
                <td><?= h($p['tipo']) ?></td>
                <td><?= h((string)$p['puntaje']) ?></td>
                <td>
                    <a class="btn" href="?formulario_id=<?= $formulario_id ?>&editar=<?= (int)$p['id'] ?>">Editar</a>
                    <a class="btn btn-danger" href="?formulario_id=<?= $formulario_id ?>&delete=<?= (int)$p['id'] ?>"
                       onclick="return confirm('¿Eliminar pregunta?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function toggleOpciones() {
    const tipo = document.getElementById('tipo').value;
    document.getElementById('bloqueMultiple').style.display = tipo === 'multiple' ? 'block' : 'none';
}
toggleOpciones();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>