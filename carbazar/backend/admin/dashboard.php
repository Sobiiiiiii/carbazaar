<?php
$admin_page = 'dashboard';
require_once 'includes/auth_guard.php';

// ---- Stats ----
$stats = [];

// Total users
$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE user_type != 'admin'");
$stats['users'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Buyers
$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE user_type = 'buyer'");
$stats['buyers'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Sellers
$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE user_type = 'seller'");
$stats['sellers'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Total cars
$r = $conn->query("SELECT COUNT(*) AS c FROM cars WHERE is_active = 1");
$stats['cars'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Total parts
$r = $conn->query("SELECT COUNT(*) AS c FROM products WHERE is_active = 1");
$stats['products'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Total orders
$r = $conn->query("SELECT COUNT(*) AS c FROM orders");
$stats['orders'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Total revenue
$r = $conn->query("SELECT SUM(total_amount) AS rev FROM orders WHERE status != 'cancelled'");
$stats['revenue'] = (float)($r->fetch_assoc()['rev'] ?? 0);

// Pending orders
$r = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'");
$stats['pending'] = (int)($r->fetch_assoc()['c'] ?? 0);

// Recent orders (last 8)
$recent_orders = $conn->query("
    SELECT o.id, o.total_amount, o.status, o.order_date,
           u.name AS buyer_name,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Recent users (last 6)
$recent_users = $conn->query("
    SELECT id, name, email, user_type, created_at
    FROM users
    WHERE user_type != 'admin'
    ORDER BY created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Recent cars (last 5)
$recent_cars = $conn->query("
    SELECT c.id, c.title, c.brand, c.price, c.city, c.created_at,
           u.name AS seller_name
    FROM cars c
    JOIN users u ON c.seller_id = u.id
    WHERE c.is_active = 1
    ORDER BY c.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <!-- Top Bar -->
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-tachometer-alt me-2 text-warning"></i>Dashboard</div>
        <div class="adm-topbar-right">
            <div class="adm-user-badge">
                <i class="fas fa-user-shield"></i>
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
            </div>
            <a href="<?= BASE_URL ?>backend/auth/logout.php" class="adm-logout-btn">
                <i class="fas fa-sign-out-alt me-1"></i>Logout
            </a>
        </div>
    </div>

    <div class="adm-content">

        <!-- Stats Row 1 -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card" style="border-left-color:#6366f1">
                    <div class="stat-icon" style="background:#ede9fe;color:#6366f1">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-num"><?= number_format($stats['users']) ?></div>
                    <div class="stat-label">Total Users</div>
                    <small class="text-muted"><?= $stats['buyers'] ?> buyers · <?= $stats['sellers'] ?> sellers</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card" style="border-left-color:#f0c040">
                    <div class="stat-icon" style="background:#fef9c3;color:#b45309">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-num"><?= number_format($stats['cars']) ?></div>
                    <div class="stat-label">Active Cars</div>
                    <small class="text-muted">Listed for sale</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card" style="border-left-color:#10b981">
                    <div class="stat-icon" style="background:#d1fae5;color:#065f46">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-num"><?= number_format($stats['products']) ?></div>
                    <div class="stat-label">Spare Parts</div>
                    <small class="text-muted">Active products</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card" style="border-left-color:#ef4444">
                    <div class="stat-icon" style="background:#fee2e2;color:#991b1b">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-num"><?= number_format($stats['orders']) ?></div>
                    <div class="stat-label">Total Orders</div>
                    <small class="text-muted"><?= $stats['pending'] ?> pending</small>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="stat-card" style="border-left-color:#f0c040;background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff">
                    <div class="stat-icon" style="background:rgba(240,192,64,.2);color:#f0c040">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-num" style="color:#f0c040">
                        Rs <?= number_format($stats['revenue'], 0) ?>
                    </div>
                    <div class="stat-label" style="color:rgba(255,255,255,.6)">Total Revenue</div>
                    <small style="color:rgba(255,255,255,.4)">(Excluding cancelled orders)</small>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <!-- Quick Actions -->
                <div class="stat-card" style="border-left-color:#6366f1">
                    <div class="fw-700 mb-3" style="font-weight:700;color:#1a1a2e">
                        <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="users.php" class="btn btn-sm" style="background:#ede9fe;color:#6366f1;font-weight:600;border-radius:8px">
                            <i class="fas fa-users me-1"></i>Manage Users
                        </a>
                        <a href="cars.php" class="btn btn-sm" style="background:#fef9c3;color:#b45309;font-weight:600;border-radius:8px">
                            <i class="fas fa-car me-1"></i>Manage Cars
                        </a>
                        <a href="orders.php" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;font-weight:600;border-radius:8px">
                            <i class="fas fa-shopping-bag me-1"></i>View Orders
                        </a>
                        <a href="categories.php" class="btn btn-sm" style="background:#d1fae5;color:#065f46;font-weight:600;border-radius:8px">
                            <i class="fas fa-tags me-1"></i>Categories
                        </a>
                        <a href="products.php" class="btn btn-sm" style="background:#dbeafe;color:#1e40af;font-weight:600;border-radius:8px">
                            <i class="fas fa-cogs me-1"></i>Spare Parts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders + Recent Users -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-7">
                <div class="adm-table">
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="fw-bold" style="color:#1a1a2e"><i class="fas fa-shopping-bag me-2 text-warning"></i>Recent Orders</div>
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.78rem">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Buyer</th>
                                    <th>Amount</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recent_orders)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Koi orders nahi hain</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $o): ?>
                                <tr>
                                    <td><strong>#<?= $o['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                                    <td>Rs <?= number_format($o['total_amount'], 0) ?></td>
                                    <td><?= $o['item_count'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $o['status'] ?>" style="border-radius:6px;padding:4px 10px;font-size:.75rem">
                                            <?= ucfirst($o['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.78rem;color:#6b7280">
                                        <?= date('d M Y', strtotime($o['order_date'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="adm-table">
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                        <div class="fw-bold" style="color:#1a1a2e"><i class="fas fa-user-plus me-2 text-primary"></i>New Users</div>
                        <a href="users.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.78rem">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Name</th><th>Type</th><th>Joined</th></tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recent_users)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">Koi users nahi</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_users as $u): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($u['name']) ?></div>
                                        <div style="font-size:.75rem;color:#6b7280"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $u['user_type'] ?>" style="border-radius:6px;padding:4px 10px;font-size:.75rem">
                                            <?= ucfirst($u['user_type']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.78rem;color:#6b7280">
                                        <?= date('d M', strtotime($u['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Cars -->
        <div class="adm-table mb-4">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                <div class="fw-bold" style="color:#1a1a2e"><i class="fas fa-car me-2 text-warning"></i>Latest Car Listings</div>
                <a href="cars.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.78rem">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Title</th><th>Brand</th><th>Price</th><th>City</th><th>Seller</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent_cars)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Koi cars nahi hain</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_cars as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['title']) ?></strong></td>
                            <td><?= htmlspecialchars($c['brand']) ?></td>
                            <td>Rs <?= number_format($c['price'], 0) ?></td>
                            <td><?= htmlspecialchars($c['city']) ?></td>
                            <td><?= htmlspecialchars($c['seller_name']) ?></td>
                            <td style="font-size:.78rem;color:#6b7280"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /adm-content -->
</div><!-- /adm-main -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
