<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: ' . redirect_dashboard());
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;