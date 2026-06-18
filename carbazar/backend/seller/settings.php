<?php
require_once '../config/db.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../../index.php');
    exit;
}

$seller_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            $error = 'Name cannot be empty.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $phone, $seller_id);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $user['name']  = $name;
                $user['phone'] = $phone;
                $success = 'Profile updated successfully.';
            } else {
                $error = 'Failed to update profile.';
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $seller_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!password_verify($current, $row['password'])) {
                $error = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $seller_id);
                if ($stmt->execute()) {
                    $success = 'Password changed successfully.';
                } else {
                    $error = 'Failed to change password.';
                }
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
    <title>Settings - CarBazar Seller</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .sidebar { background: #2c3e50; color: white; min-height: 100vh; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar p-4">
            <h3 class="mb-4"><i class="fas fa-store"></i> Seller Panel</h3>
            <ul class="list-unstyled">
                <li class="mb-3"><a href="dashboard.php" class="text-white text-decoration-none"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="mb-3"><a href="products.php" class="text-white text-decoration-none"><i class="fas fa-box"></i> My Products</a></li>
                <li class="mb-3"><a href="add-product.php" class="text-white text-decoration-none"><i class="fas fa-plus"></i> Add Product</a></li>
                <li class="mb-3"><a href="orders.php" class="text-white text-decoration-none"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="mb-3"><a href="settings.php" class="text-white text-decoration-none fw-bold"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="mb-3"><a href="../auth/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 p-4">
            <h2>Settings</h2>
            <hr>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Profile Update -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-user me-2"></i>Profile Information</div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed.</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="fas fa-lock me-2"></i>Change Password</div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning mt-3">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
