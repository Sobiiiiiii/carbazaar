<?php
require_once 'backend/config/db.php';

// Already logged in?
if (isLoggedIn()) {
    header('Location: ' . (isSeller() ? 'backend/seller/dashboard.php' : 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Invalid email or password.';
        } else {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type']  = $user['user_type'];

            if ($user['user_type'] === 'seller') {
                header('Location: backend/seller/dashboard.php');
            } else {
                // buyer aur admin dono website par jayein
                header('Location: index.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            border: none;
        }
        .card-header h2 { font-size: 1.8rem; font-weight: 800; margin: 0 0 5px; }
        .card-header p  { margin: 0; opacity: 0.85; font-size: 0.9rem; }
        .card-body { padding: 30px; }
        .form-label { font-weight: 600; font-size: 0.88rem; color: #444; }
        .input-group-text { background: #f5f5f5; border-right: none; }
        .form-control { border-left: none; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.2); }
        .input-group:focus-within .input-group-text { border-color: #667eea; }
        .btn-main {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; width: 100%;
            padding: 12px; font-size: 1rem; font-weight: 600;
            border-radius: 10px; cursor: pointer;
        }
        .btn-main:hover { opacity: 0.9; }
        .toggle-eye {
            background: #f5f5f5; border: 1px solid #ced4da;
            border-left: none; cursor: pointer; padding: 0 14px; color: #888;
        }
        .alert-danger { background: #fff0f0; border: 1px solid #f5c6cb; color: #c0392b; border-radius: 10px; }
        .links { text-align: center; margin-top: 20px; font-size: 0.9rem; color: #666; }
        .links a { color: #667eea; font-weight: 600; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-car"></i> CarBazar</h2>
        <p>Sign in to your CarBazar account</p>
    </div>
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="email@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="pwd" class="form-control"
                           placeholder="Enter your password" required>
                    <button type="button" class="toggle-eye" onclick="
                        var p=document.getElementById('pwd');
                        var i=this.querySelector('i');
                        p.type=p.type==='password'?'text':'password';
                        i.className=p.type==='text'?'fas fa-eye-slash':'fas fa-eye';
                    "><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="btn-main">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>

        <div class="links">
            <p class="mt-3 mb-1">Don't have an account? <a href="register.php">Sign Up</a></p>
            <a href="index.php" class="text-muted" style="font-size:0.85rem;">
                <i class="fas fa-arrow-left me-1"></i>Back to Home
            </a>
            <p class="mt-2 mb-0">
                <a href="backend/admin/login.php" style="font-size:0.82rem;color:#999">
                    <i class="fas fa-shield-alt me-1"></i>Admin Panel
                </a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
