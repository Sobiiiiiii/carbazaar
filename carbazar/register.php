<?php
require_once 'backend/config/db.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $type     = in_array($_POST['user_type'] ?? '', ['buyer','seller']) ? $_POST['user_type'] : 'buyer';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, user_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $hash, $type);
            if ($stmt->execute()) {
                $success = 'Account created successfully! Please sign in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CarBazar</title>
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
            padding: 30px 20px;
        }
        .card {
            width: 100%;
            max-width: 480px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; text-align: center;
            padding: 28px 20px; border: none;
        }
        .card-header h2 { font-size: 1.7rem; font-weight: 800; margin: 0 0 4px; }
        .card-header p  { margin: 0; opacity: 0.85; font-size: 0.88rem; }
        .card-body { padding: 28px 30px; }
        .form-label { font-weight: 600; font-size: 0.87rem; color: #444; }
        .input-group-text { background: #f5f5f5; border-right: none; }
        .form-control { border-left: none; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.2); }
        .input-group:focus-within .input-group-text { border-color: #667eea; }

        /* Account type selector */
        .type-wrap { display: flex; gap: 10px; margin-bottom: 18px; }
        .type-box {
            flex: 1; border: 2px solid #ddd; border-radius: 12px;
            padding: 14px 8px; text-align: center; cursor: pointer;
            transition: all 0.2s;
        }
        .type-box:hover { border-color: #667eea; background: #f5f7ff; }
        .type-box.on  { border-color: #667eea; background: #eef1ff; }
        .type-box i   { font-size: 1.5rem; color: #bbb; display: block; margin-bottom: 5px; }
        .type-box.on i { color: #667eea; }
        .type-box b   { font-size: 0.88rem; color: #333; }
        .type-box small { display: block; color: #999; font-size: 0.75rem; }

        .btn-main {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; width: 100%;
            padding: 12px; font-size: 1rem; font-weight: 600;
            border-radius: 10px; cursor: pointer; margin-top: 4px;
        }
        .btn-main:hover { opacity: 0.9; }
        .toggle-eye {
            background: #f5f5f5; border: 1px solid #ced4da;
            border-left: none; cursor: pointer; padding: 0 14px; color: #888;
        }
        .alert-danger  { background: #fff0f0; border: 1px solid #f5c6cb; color: #c0392b; border-radius: 10px; }
        .alert-success { background: #f0fff4; border: 1px solid #b2dfdb; color: #1a7a4a; border-radius: 10px; }
        .links { text-align: center; margin-top: 18px; font-size: 0.9rem; color: #666; }
        .links a { color: #667eea; font-weight: 600; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-car"></i> CarBazar</h2>
        <p>Create your free account — it's quick & easy!</p>
    </div>
    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success mb-3">
                <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
                &nbsp;<a href="login.php" style="color:#1a7a4a;font-weight:700;">Sign In &rarr;</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="regForm">

            <!-- Account Type -->
            <div class="mb-3">
                <label class="form-label">I want to:</label>
                <div class="type-wrap">
                    <div class="type-box on" id="boxBuyer" onclick="pickType('buyer')">
                        <i class="fas fa-shopping-cart"></i>
                        <b>Buy Parts</b>
                        <small>Buyer account</small>
                    </div>
                    <div class="type-box" id="boxSeller" onclick="pickType('seller')">
                        <i class="fas fa-store"></i>
                        <b>Sell Parts</b>
                        <small>Seller account</small>
                    </div>
                </div>
                <input type="hidden" name="user_type" id="utype" value="buyer">
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="name" class="form-control"
                           placeholder="Enter your full name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="email@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone <small class="text-muted">(optional)</small></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                    <input type="tel" name="phone" class="form-control"
                           placeholder="+92 3XX XXXXXXX"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="p1" class="form-control"
                           placeholder="At least 6 characters" required>
                    <button type="button" class="toggle-eye" onclick="toggleEye('p1','e1')">
                        <i class="fas fa-eye" id="e1"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password *</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="confirm_password" id="p2" class="form-control"
                           placeholder="Re-enter your password" required>
                    <button type="button" class="toggle-eye" onclick="toggleEye('p2','e2')">
                        <i class="fas fa-eye" id="e2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-main">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>

        <div class="links">
            <p class="mt-3 mb-1">Already have an account? <a href="login.php">Sign In</a></p>
            <a href="index.php" class="text-muted" style="font-size:0.85rem;">
                <i class="fas fa-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>
</div>

<script>
function pickType(t) {
    document.getElementById('utype').value = t;
    document.getElementById('boxBuyer').classList.toggle('on', t === 'buyer');
    document.getElementById('boxSeller').classList.toggle('on', t === 'seller');
}
function toggleEye(fid, iid) {
    var f = document.getElementById(fid);
    var i = document.getElementById(iid);
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}
document.getElementById('regForm').onsubmit = function(e) {
    if (document.getElementById('p1').value !== document.getElementById('p2').value) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
};
</script>
</body>
</html>
