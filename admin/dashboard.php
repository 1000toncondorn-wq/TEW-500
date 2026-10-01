<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('administrador');
include __DIR__ . '/../includes/header.php';
?>
<h1>Panel Administrador</h1>
<div class="grid">
    <div class="card">
        <h2>Usuarios</h2>
        <p>Registrar docentes y estudiantes.</p>
        <a class="btn" href="<?= BASE_URL ?>/admin/usuarios.php">Gestionar usuarios</a>
    </div>
    <div class="card">
        <h2>Formularios</h2>
        <p>Crear, editar, eliminar formularios y preguntas.</p>
        <a class="btn" href="<?= BASE_URL ?>/admin/formularios.php">Gestionar formularios</a>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>