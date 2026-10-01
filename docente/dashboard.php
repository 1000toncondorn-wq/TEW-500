<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('docente');
include __DIR__ . '/../includes/header.php';
?>
<h1>Panel Docente</h1>
<div class="grid">
    <div class="card">
        <h2>Asignar formularios</h2>
        <p>Asigne exámenes a estudiantes con fecha límite.</p>
        <a class="btn" href="<?= BASE_URL ?>/docente/asignar.php">Asignar</a>
    </div>
    <div class="card">
        <h2>Revisar y calificar</h2>
        <p>Califique preguntas abiertas y consulte notas.</p>
        <a class="btn" href="<?= BASE_URL ?>/docente/revisar.php">Revisar</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>