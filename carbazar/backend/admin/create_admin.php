<?php
/**
 * Admin Account Creator - CarBazar
 * Run once, then DELETE or rename this file
 * URL: http://localhost/carbazar/backend/admin/create_admin.php
 */
require_once '../config/db.php';

$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? 'Admin');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $msg = 'Email and password are required.';
        $msg_type = 'danger';
    } elseif (strlen($password) < 6) {
        $msg = 'Password kam az kam 6 characters ka hona chahiye.';
        $msg_type = 'danger';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Check if email exists
        $st = $conn->prepare("SELECT id, user_type FROM users WHERE email = ?");
        $st->bind_param("s", $email);
        $st->execute();
        $existing = $st->get_result()->fetch_assoc();
        $st->close();

        if ($existing) {
            // Update to admin
            $st = $conn->prepare("UPDATE users SET name=?, password=?, user_type='admin', phone=? WHERE email=?");
            $st->bind_param("ssss", $name, $hash, $phone, $email);
            $st->execute();
            $st->close();
            $msg = "User '$email' ko admin bana diya gaya! Ab is file ko delete kar do.";
        } else {
            // Insert new admin
            $st = $conn->prepare("INSERT INTO users (name, email, phone, password, user_type) VALUES (?, ?, ?, ?, 'admin')");
            $st->bind_param("ssss", $name, $email, $phone, $hash);
            $st->execute();
            $st->close();
            $msg = "Admin account '$email' ban gaya! Ab is file ko delete kar do.";
        }
    }
}

// Also alter table if needed
$conn->query("ALTER TABLE users MODIFY COLUMN user_type ENUM('buyer','seller','admin') NOT NULL DEFAULT 'buyer'");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg,#0f0f1a,#1a1a2e); min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'Segoe UI',sans-serif; }
        .card { max-width:440px; width:100%; border-radius:20px; border:none; box-shadow:0 24px 60px rgba(0,0,0,.5); }
        .card-header { background:linear-gradient(135deg,#f0c040,#e0a800); border-radius:20px 20px 0 0; padding:28px; text-align:center; }
        .card-header h4 { color:#1a1a2e; font-weight:800; margin:0; }
        .card-body { padding:28px; }
        .btn-create { background:linear-gradient(135deg,#f0c040,#e0a800); color:#1a1a2e; font-weight:700; border:none; border-radius:10px; padding:12px; width:100%; }
        .warning-box { background:#fff3cd; border:1px solid #ffc107; border-radius:10px; padding:12px 16px; font-size:.85rem; color:#856404; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <i class="fas fa-shield-alt fa-2x mb-2" style="color:#1a1a2e"></i>
        <h4>Admin Account Setup</h4>
        <small style="color:#1a1a2e;opacity:.7">CarBazar Admin Panel</small>
    </div>
    <div class="card-body">

        <div class="warning-box mb-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Security Warning:</strong> Yeh file use karne ke baad <strong>delete kar do</strong>!
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> mb-3" style="border-radius:10px">
            <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($msg) ?>
            <?php if ($msg_type === 'success'): ?>
            <div class="mt-2">
                <a href="login.php" class="btn btn-sm btn-dark" style="border-radius:8px">
                    <i class="fas fa-sign-in-alt me-1"></i>Admin Login
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-600 small">Admin Name</label>
                <input type="text" name="name" class="form-control" value="Admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600 small">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="admin@carbazar.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-600 small">Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="0300-0000000">
            </div>
            <div class="mb-4">
                <label class="form-label fw-600 small">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
            </div>
            <button type="submit" class="btn-create">
                <i class="fas fa-user-shield me-2"></i>Create Admin Account
            </button>
        </form>
    </div>
</div>
</body>
</html>
