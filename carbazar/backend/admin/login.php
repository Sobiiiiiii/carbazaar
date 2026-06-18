<?php
require_once '../config/db.php';

// Already logged in admin
if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $st = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ? AND user_type = 'admin' LIMIT 1");
        $st->bind_param("s", $email);
        $st->execute();
        $admin = $st->get_result()->fetch_assoc();
        $st->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id']   = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_type'] = 'admin';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email ya password galat hai, ya admin account nahi hai.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --navy: #1a1a2e; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 60%, #16213e 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 24px 60px rgba(0,0,0,.5);
        }
        .login-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #f0c040, #e0a800);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: #1a1a2e;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(240,192,64,.4);
        }
        .login-title { font-size: 1.5rem; font-weight: 800; color: #1a1a2e; text-align: center; }
        .login-sub   { font-size: .85rem; color: #6b7280; text-align: center; margin-bottom: 28px; }
        .form-label  { font-weight: 600; font-size: .85rem; color: #374151; }
        .form-control {
            border: 2px solid #e5e7eb; border-radius: 10px;
            padding: 11px 14px; font-size: .9rem;
            transition: border-color .2s;
        }
        .form-control:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(240,192,64,.15); }
        .btn-login {
            background: linear-gradient(135deg, #f0c040, #e0a800);
            color: #1a1a2e; font-weight: 700; font-size: .95rem;
            border: none; border-radius: 10px; padding: 12px;
            width: 100%; transition: all .2s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(240,192,64,.4); }
        .input-group-text { background: #f9fafb; border: 2px solid #e5e7eb; border-right: none; border-radius: 10px 0 0 10px; }
        .input-group .form-control { border-left: none; border-radius: 0 10px 10px 0; }
        .input-group .form-control:focus { border-color: var(--gold); }
        .input-group:focus-within .input-group-text { border-color: var(--gold); }
        .back-link { text-align: center; margin-top: 20px; font-size: .82rem; color: #6b7280; }
        .back-link a { color: var(--navy); font-weight: 600; text-decoration: none; }
        .back-link a:hover { color: #e0a800; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo"><i class="fas fa-shield-alt"></i></div>
    <div class="login-title">Admin Login</div>
    <div class="login-sub">CarBazar Admin Panel mein khush aamdeed</div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3" style="border-radius:10px;font-size:.85rem">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control"
                       placeholder="admin@carbazar.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt me-2"></i>Login to Admin Panel
        </button>
    </form>

    <div class="back-link">
        <a href="<?= BASE_URL ?>index.php"><i class="fas fa-arrow-left me-1"></i>Website par wapas jao</a>
    </div>
</div>
</body>
</html>
