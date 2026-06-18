<?php
require_once '../config/db.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../../index.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats['total_products'] = $stmt->get_result()->fetch_assoc()['count'];

// Total orders
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM order_items
    WHERE seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats['total_orders'] = $stmt->get_result()->fetch_assoc()['count'];

// Total revenue
$stmt = $conn->prepare("
    SELECT SUM(price * quantity) as total FROM order_items
    WHERE seller_id = ?
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['total_revenue'] = $result['total'] ? floatval($result['total']) : 0;

// Low stock products
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM products
    WHERE seller_id = ? AND stock < 10
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats['low_stock'] = $stmt->get_result()->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .sidebar { background: #2c3e50; color: white; min-height: 100vh; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #0d6efd; }
        .stat-card .label { color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar p-4">
                <h3 class="mb-4">
                    <i class="fas fa-store"></i> Seller Panel
                </h3>
                <ul class="list-unstyled">
                    <li class="mb-3"><a href="dashboard.php" class="text-white text-decoration-none"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li class="mb-3"><a href="products.php" class="text-white text-decoration-none"><i class="fas fa-box"></i> My Products</a></li>
                    <li class="mb-3"><a href="add-product.php" class="text-white text-decoration-none"><i class="fas fa-plus"></i> Add Product</a></li>
                    <li class="mb-3"><a href="orders.php" class="text-white text-decoration-none"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                    <li class="mb-3"><a href="settings.php" class="text-white text-decoration-none"><i class="fas fa-cog"></i> Settings</a></li>
                    <li class="mb-3"><a href="../auth/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 p-4">
                <h2>Dashboard</h2>
                <hr>

                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="number"><?php echo $stats['total_products']; ?></div>
                            <div class="label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="number"><?php echo $stats['total_orders']; ?></div>
                            <div class="label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="number">PKR <?php echo number_format($stats['total_revenue'], 0); ?></div>
                            <div class="label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="number"><?php echo $stats['low_stock']; ?></div>
                            <div class="label">Low Stock Items</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>