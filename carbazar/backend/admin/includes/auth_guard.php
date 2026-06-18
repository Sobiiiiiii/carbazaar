<?php
// Admin Auth Guard - Include at the top of every admin page
require_once __DIR__ . '/../../config/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . 'backend/admin/login.php');
    exit;
}
