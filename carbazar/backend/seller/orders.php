<?php
require_once '../config/db.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../../index.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $status = in_array($_POST['status'], $allowed_statuses) ? $_POST['status'] : 'pending';

    // Only update if this seller has items in this order
    $check = $conn->prepare("SELECT id FROM order_items WHERE order_id = ? AND seller_id = ?");
    $check->bind_param("ii", $order_id, $seller_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $upd->bind_param("si", $status, $order_id);
        $upd->execute();
    }
    header('Location: orders.php?msg=updated');
    exit;
}

// Pagination
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT DISTINCT o.id, o.total_amount, o.status, o.order_date, o.shipping_address,
           u.name as buyer_name, u.email as buyer_email,
           COUNT(oi.id) as item_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    WHERE oi.seller_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $seller_id, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$count_stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) as total FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE oi.seller_id = ?");
$count_stmt->bind_param("i", $seller_id);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$pages = ceil($total / $limit);

$status_colors = [
    'pending'    => 'warning',
    'processing' => 'info',
    'shipped'    => 'primary',
    'delivered'  => 'success',
    'cancelled'  => 'danger'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - CarBazar Seller</title>
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
                <li class="mb-3"><a href="orders.php" class="text-white text-decoration-none fw-bold"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="mb-3"><a href="settings.php" class="text-white text-decoration-none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="mb-3"><a href="../auth/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 p-4">
            <h2>Orders</h2>
            <hr>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
                <div class="alert alert-success">Order status updated.</div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No orders yet</h5>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Buyer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['buyer_email']); ?></small>
                                    </td>
                                    <td><?php echo $order['item_count']; ?> item(s)</td>
                                    <td>PKR <?php echo number_format($order['total_amount'], 0); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_colors[$order['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width:130px">
                                                <?php foreach ($status_colors as $s => $c): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($s); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
