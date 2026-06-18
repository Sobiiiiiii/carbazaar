<?php
$admin_page = 'orders';
require_once 'includes/auth_guard.php';

$msg = '';
$msg_type = 'success';

// ---- Status Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';
    $allowed  = ['pending','processing','shipped','delivered','cancelled'];
    if ($order_id > 0 && in_array($status, $allowed)) {
        $st = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $st->bind_param("si", $status, $order_id);
        $st->execute();
        $st->close();
        $msg = "Order #$order_id status updated to '$status'.";
    }
}

// ---- Filters ----
$filter_status = trim($_GET['status'] ?? '');
$filter_search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($filter_status && in_array($filter_status, ['pending','processing','shipped','delivered','cancelled'])) {
    $where[] = "o.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_search) {
    $like = '%' . $filter_search . '%';
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$st = $conn->prepare("SELECT COUNT(*) AS c FROM orders o JOIN users u ON o.user_id = u.id $where_sql");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
$total_pages = (int)ceil($total / $limit);
$st->close();

// Fetch
$st = $conn->prepare("
    SELECT o.*, u.name AS buyer_name, u.email AS buyer_email, u.phone AS buyer_phone,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $where_sql
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
");
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// View single order detail
$view_order = null;
$view_items = [];
$view_id = (int)($_GET['view'] ?? 0);
if ($view_id > 0) {
    $st = $conn->prepare("SELECT o.*, u.name AS buyer_name, u.email AS buyer_email, u.phone AS buyer_phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $st->bind_param("i", $view_id);
    $st->execute();
    $view_order = $st->get_result()->fetch_assoc();
    $st->close();

    if ($view_order) {
        $st = $conn->prepare("
            SELECT oi.quantity, oi.price, p.name, p.image, p.brand, c.name AS cat_name, u.name AS seller_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            JOIN users u ON oi.seller_id = u.id
            WHERE oi.order_id = ?
        ");
        $st->bind_param("i", $view_id);
        $st->execute();
        $view_items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-shopping-bag me-2 text-danger"></i>Orders Management</div>
        <div class="adm-topbar-right">
            <div class="adm-user-badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
            <a href="<?= BASE_URL ?>backend/auth/logout.php" class="adm-logout-btn"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
    </div>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" style="border-radius:10px">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Order Detail Modal -->
        <?php if ($view_order): ?>
        <div class="stat-card mb-4" style="border-left-color:#6366f1">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0" style="color:#1a1a2e"><i class="fas fa-receipt me-2 text-primary"></i>Order #<?= $view_order['id'] ?> Detail</h5>
                <a href="orders.php" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                    <i class="fas fa-times me-1"></i>Close
                </a>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div style="background:#f8f9fa;border-radius:10px;padding:14px">
                        <div class="fw-bold mb-2" style="font-size:.85rem;color:#6b7280">BUYER INFO</div>
                        <div style="font-weight:600"><?= htmlspecialchars($view_order['buyer_name']) ?></div>
                        <div style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($view_order['buyer_email']) ?></div>
                        <div style="font-size:.82rem;color:#6b7280"><?= htmlspecialchars($view_order['buyer_phone'] ?? '-') ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#f8f9fa;border-radius:10px;padding:14px">
                        <div class="fw-bold mb-2" style="font-size:.85rem;color:#6b7280">ORDER INFO</div>
                        <div style="font-size:.85rem">Amount: <strong>Rs <?= number_format($view_order['total_amount'], 0) ?></strong></div>
                        <div style="font-size:.85rem">Payment: <strong><?= strtoupper($view_order['payment_method']) ?></strong></div>
                        <div style="font-size:.85rem">Date: <?= date('d M Y, h:i A', strtotime($view_order['order_date'])) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background:#f8f9fa;border-radius:10px;padding:14px">
                        <div class="fw-bold mb-2" style="font-size:.85rem;color:#6b7280">UPDATE STATUS</div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="order_id" value="<?= $view_order['id'] ?>">
                            <select name="status" class="form-select form-select-sm" style="border-radius:8px">
                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $view_order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm" style="background:#1a1a2e;color:#f0c040;border-radius:8px;white-space:nowrap">Update</button>
                        </form>
                    </div>
                </div>
            </div>
            <div style="font-size:.82rem;color:#6b7280;margin-bottom:10px">
                <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($view_order['shipping_address']) ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="border-radius:10px;overflow:hidden">
                    <thead style="background:#f3f4f6">
                        <tr><th>Product</th><th>Category</th><th>Seller</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($view_items as $item): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if ($item['brand']): ?>
                            <div style="font-size:.75rem;color:#6b7280"><?= htmlspecialchars($item['brand']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.82rem"><?= htmlspecialchars($item['cat_name'] ?? '-') ?></td>
                        <td style="font-size:.82rem"><?= htmlspecialchars($item['seller_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>Rs <?= number_format($item['price'], 0) ?></td>
                        <td><strong>Rs <?= number_format($item['price'] * $item['quantity'], 0) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <?php
            $tabs = ['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing',
                     'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
            $tab_colors = ['' => ['#f3f4f6','#374151'], 'pending' => ['#fef3c7','#92400e'],
                           'processing' => ['#dbeafe','#1e40af'], 'shipped' => ['#e0e7ff','#3730a3'],
                           'delivered' => ['#d1fae5','#065f46'], 'cancelled' => ['#fee2e2','#991b1b']];
            foreach ($tabs as $val => $label):
                [$bg, $color] = $tab_colors[$val];
            ?>
            <a href="?status=<?= $val ?>&search=<?= urlencode($filter_search) ?>"
               class="btn btn-sm" style="background:<?= $filter_status === $val ? $bg : '#fff' ?>;color:<?= $color ?>;border:1px solid <?= $bg ?>;border-radius:8px;font-weight:600">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <div class="stat-card mb-4" style="border-left-color:#ef4444">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                <div class="col-12 col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Buyer name, email ya order ID..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn w-100" style="background:#ef4444;color:#fff;border-radius:8px;font-weight:600">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                </div>
                <div class="col-6 col-md-2">
                    <a href="orders.php" class="btn btn-outline-secondary w-100" style="border-radius:8px">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="adm-table">
            <div class="p-3 border-bottom fw-bold" style="color:#1a1a2e">
                <i class="fas fa-shopping-bag me-2 text-danger"></i><?= number_format($total) ?> Orders Found
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Koi orders nahi mile</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?= $o['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($o['buyer_name']) ?></div>
                                <div style="font-size:.75rem;color:#6b7280"><?= htmlspecialchars($o['buyer_email']) ?></div>
                            </td>
                            <td style="font-weight:600;color:#065f46">Rs <?= number_format($o['total_amount'], 0) ?></td>
                            <td><?= $o['item_count'] ?> items</td>
                            <td>
                                <span style="font-size:.78rem;font-weight:600;background:#f3f4f6;padding:3px 8px;border-radius:6px">
                                    <?= strtoupper($o['payment_method']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $o['status'] ?>" style="border-radius:6px;padding:4px 10px;font-size:.75rem">
                                    <?= ucfirst($o['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:.78rem;color:#6b7280">
                                <?= date('d M Y', strtotime($o['order_date'])) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 align-items-center">
                                    <a href="?view=<?= $o['id'] ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>"
                                       class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:6px;padding:3px 8px;font-size:.75rem" title="View Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" class="form-select form-select-sm d-inline-block"
                                                style="width:auto;border-radius:6px;font-size:.75rem;padding:3px 6px"
                                                onchange="this.form.submit()">
                                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center p-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
