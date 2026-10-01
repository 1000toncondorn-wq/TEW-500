<?php
require_once __DIR__ . '/auth.php';
$u = current_user();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TEW-500</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<nav class="nav">
    <a class="brand" href="<?= BASE_URL ?>/index.php">TEW-500</a>
    <div class="nav-links">
        <?php if ($u): ?>
            <?php if ($u['rol'] === 'administrador'): ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a>
                <a href="<?= BASE_URL ?>/admin/usuarios.php">Usuarios</a>
                <a href="<?= BASE_URL ?>/admin/formularios.php">Formularios</a>
            <?php elseif ($u['rol'] === 'docente'): ?>
                <a href="<?= BASE_URL ?>/docente/dashboard.php">Docente</a>
                <a href="<?= BASE_URL ?>/docente/asignar.php">Asignar</a>
                <a href="<?= BASE_URL ?>/docente/revisar.php">Revisar</a>
            <?php elseif ($u['rol'] === 'estudiante'): ?>
                <a href="<?= BASE_URL ?>/estudiante/dashboard.php">Mis exámenes</a>
            <?php endif; ?>
            <span class="user"><?= h($u['nombre']) ?> (<?= h($u['rol']) ?>)</span>
            <a href="<?= BASE_URL ?>/logout.php">Salir</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>
<main class="container">
<?php if ($m = flash('ok')): ?>
    <div class="alert ok"><?= h($m) ?></div>
<?php endif; ?>
<?php if ($m = flash('error')): ?>
    <div class="alert error"><?= h($m) ?></div>
<?php endif; ?>