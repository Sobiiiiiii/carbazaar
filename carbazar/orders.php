<?php
require_once 'backend/config/db.php';

$orders      = [];
$order_detail = null;
$total_orders = 0;
$cart_count   = 0;
$wl_count     = 0;

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 8;
$offset = ($page - 1) * $limit;

// View single order detail
$view_id = (int)($_GET['view'] ?? 0);

if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];

    // ---- Navbar counts ----
    $st = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id=?");
    $st->bind_param("i", $uid); $st->execute();
    $cart_count = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();

    $st = $conn->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id=?");
    $st->bind_param("i", $uid); $st->execute();
    $wl_count = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();

    // ---- Single order detail ----
    if ($view_id > 0) {
        $st = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
        $st->bind_param("ii", $view_id, $uid);
        $st->execute();
        $order_detail = $st->get_result()->fetch_assoc();
        $st->close();

        if ($order_detail) {
            $st = $conn->prepare("
                SELECT oi.quantity, oi.price,
                       p.name, p.image, p.brand,
                       cat.name AS cat_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE oi.order_id = ?
            ");
            $st->bind_param("i", $view_id);
            $st->execute();
            $order_detail['items'] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
            $st->close();
        }
    }

    // ---- Orders list ----
    $st = $conn->prepare("
        SELECT o.id, o.total_amount, o.status, o.payment_method,
               o.shipping_address, o.order_date,
               COUNT(oi.id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?
    ");
    $st->bind_param("iii", $uid, $limit, $offset);
    $st->execute();
    $orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    $st = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE user_id=?");
    $st->bind_param("i", $uid); $st->execute();
    $total_orders = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
}

$total_pages = (int)ceil($total_orders / $limit);

// Status config
$status_cfg = [
    'pending'    => ['bg-warning text-dark', 'fas fa-clock',          'Pending'],
    'processing' => ['bg-info text-white',   'fas fa-cog fa-spin',    'Processing'],
    'shipped'    => ['bg-primary text-white','fas fa-shipping-fast',  'Shipped'],
    'delivered'  => ['bg-success text-white','fas fa-check-circle',   'Delivered'],
    'cancelled'  => ['bg-danger text-white', 'fas fa-times-circle',   'Cancelled'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - CarBazar</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #f0c040; --dark-navy: #1a1a2e; }
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

/* Hero */
.orders-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 36px 0 28px; color: #fff;
}

/* Order Card */
.order-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    margin-bottom: 14px; overflow: hidden;
    transition: box-shadow .2s;
    border-left: 4px solid transparent;
}
.order-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); }
.order-card.status-pending    { border-left-color: #ffc107; }
.order-card.status-processing { border-left-color: #0dcaf0; }
.order-card.status-shipped    { border-left-color: #0d6efd; }
.order-card.status-delivered  { border-left-color: #198754; }
.order-card.status-cancelled  { border-left-color: #dc3545; }

.order-header {
    padding: 16px 20px;
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}
.order-body { padding: 16px 20px; }
.order-id { font-weight: 800; font-size: 1rem; color: #0f172a; }
.order-date { font-size: .78rem; color: #64748b; }
.order-total { font-size: 1.1rem; font-weight: 800; color: #e67e22; }

/* Product thumb row */
.prod-thumb {
    width: 48px; height: 48px; object-fit: cover;
    border-radius: 8px; border: 1.5px solid #e2e8f0;
}

/* Detail view */
.detail-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden;
}
.detail-header {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    color: #fff; padding: 24px 28px;
}
.detail-body { padding: 28px; }

/* Timeline */
.timeline { position: relative; padding-left: 28px; }
.timeline::before {
    content: ''; position: absolute; left: 10px; top: 0; bottom: 0;
    width: 2px; background: #e2e8f0;
}
.tl-item { position: relative; margin-bottom: 20px; }
.tl-dot {
    position: absolute; left: -24px; top: 2px;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; color: #fff;
}
.tl-dot.done  { background: #198754; }
.tl-dot.active{ background: #0d6efd; }
.tl-dot.wait  { background: #dee2e6; }
.tl-label { font-weight: 600; font-size: .88rem; color: #0f172a; }
.tl-sub   { font-size: .75rem; color: #64748b; }

/* Empty / Login */
.empty-orders { text-align: center; padding: 80px 20px; }
.login-card {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    border-radius: 20px; color: #fff; padding: 50px 30px; text-align: center;
}

/* Pagination */
.page-link { color: var(--dark-navy); }
.page-item.active .page-link { background: var(--gold); border-color: var(--gold); color: #1a1a2e; }

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
</style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<?php $active_page = ''; require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="orders-hero">
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-box text-warning me-2"></i>My Orders</h2>
            <p class="mb-0" style="opacity:.75;">All your orders and their delivery status</p>
        </div>
        <?php if (isLoggedIn() && $total_orders > 0): ?>
        <span class="badge bg-warning text-dark px-3 py-2" style="font-size:.9rem;border-radius:20px;">
            <i class="fas fa-box me-1"></i><?= $total_orders ?> Total Orders
        </span>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- BREADCRUMB -->
<div class="bg-white border-bottom py-2">
<div class="container">
    <nav><ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" style="color:var(--gold);text-decoration:none;">Home</a></li>
        <?php if ($order_detail): ?>
        <li class="breadcrumb-item"><a href="orders.php" style="color:var(--gold);text-decoration:none;">My Orders</a></li>
        <li class="breadcrumb-item active text-muted">Order #<?= $order_detail['id'] ?></li>
        <?php else: ?>
        <li class="breadcrumb-item active text-muted">My Orders</li>
        <?php endif; ?>
    </ol></nav>
</div>
</div>

<!-- ===== MAIN ===== -->
<div class="container py-4">

<?php if (!isLoggedIn()): ?>
<!-- NOT LOGGED IN -->
<div class="row justify-content-center mt-4">
<div class="col-lg-5 col-md-7">
    <div class="login-card">
        <i class="fas fa-box fa-4x mb-3 d-block" style="color:var(--gold)"></i>
        <h3 class="fw-bold mb-2">Login Required</h3>
        <p style="opacity:.8" class="mb-4">Please login to view your orders.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="login.php" class="btn btn-warning btn-lg fw-bold px-5"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
            <a href="register.php" class="btn btn-outline-light btn-lg px-5"><i class="fas fa-user-plus me-2"></i>Register</a>
        </div>
    </div>
</div>
</div>

<?php elseif ($order_detail): ?>
<!-- ===== SINGLE ORDER DETAIL VIEW ===== -->
<div class="mb-3">
    <a href="orders.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-2"></i>All Orders
    </a>
</div>

<?php
$cfg = $status_cfg[$order_detail['status']] ?? ['bg-secondary text-white','fas fa-circle','Unknown'];
$steps = ['pending','processing','shipped','delivered'];
$cur_step = array_search($order_detail['status'], $steps);
?>

<div class="row g-4">

    <!-- Left: Order Items -->
    <div class="col-lg-8">
        <div class="detail-card mb-4">
            <div class="detail-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">Order #<?= $order_detail['id'] ?></h4>
                        <div style="opacity:.75;font-size:.85rem;">
                            <i class="fas fa-calendar me-1"></i>
                            <?= date('d M Y, h:i A', strtotime($order_detail['order_date'])) ?>
                        </div>
                    </div>
                    <span class="badge <?= $cfg[0] ?> px-3 py-2" style="font-size:.85rem;border-radius:20px;">
                        <i class="<?= $cfg[1] ?> me-1"></i><?= $cfg[2] ?>
                    </span>
                </div>
            </div>
            <div class="detail-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-box-open text-warning me-2"></i>Order Items</h6>
                <?php foreach ($order_detail['items'] as $it):
                    $img = (!empty($it['image']) && $it['image'] !== 'default.jpg')
                           ? 'uploads/' . htmlspecialchars($it['image'])
                           : 'https://via.placeholder.com/60x60/e2e8f0/475569?text=P';
                ?>
                <div class="d-flex align-items-center gap-3 py-3 border-bottom">
                    <img src="<?= $img ?>" class="prod-thumb"
                         alt="<?= htmlspecialchars($it['name']) ?>"
                         onerror="this.src='https://via.placeholder.com/48x48/e2e8f0/475569?text=P'">
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:.92rem;"><?= htmlspecialchars($it['name']) ?></div>
                        <div class="d-flex gap-2 mt-1 flex-wrap">
                            <?php if ($it['brand']): ?>
                            <span class="badge bg-light text-dark border" style="font-size:.68rem;"><?= htmlspecialchars($it['brand']) ?></span>
                            <?php endif; ?>
                            <?php if ($it['cat_name']): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.68rem;"><?= htmlspecialchars($it['cat_name']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="color:#e67e22;">PKR <?= number_format($it['price']) ?></div>
                        <small class="text-muted">Qty: <?= $it['quantity'] ?></small>
                    </div>
                    <div class="text-end" style="min-width:90px;">
                        <div class="fw-bold" style="color:#1a1a2e;">PKR <?= number_format($it['price'] * $it['quantity']) ?></div>
                        <small class="text-muted">Subtotal</small>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Total -->
                <div class="d-flex justify-content-between align-items-center pt-3">
                    <span class="fw-bold fs-5">Total</span>
                    <span style="font-size:1.4rem;font-weight:800;color:var(--gold);">
                        PKR <?= number_format($order_detail['total_amount']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Status + Info -->
    <div class="col-lg-4">

        <!-- Order Status Timeline -->
        <div class="detail-card mb-4">
            <div class="p-4">
                <h6 class="fw-bold mb-4"><i class="fas fa-route text-warning me-2"></i>Order Status</h6>
                <div class="timeline">
                    <?php
                    $tl_steps = [
                        ['pending',    'fas fa-clock',         'Order Placed',   'Your order has been received'],
                        ['processing', 'fas fa-cog',           'Processing',     'Order is being prepared'],
                        ['shipped',    'fas fa-shipping-fast', 'Shipped',        'Order is on the way'],
                        ['delivered',  'fas fa-check-circle',  'Delivered',      'Order has been delivered'],
                    ];
                    $cur_idx = array_search($order_detail['status'], array_column($tl_steps, 0));
                    if ($order_detail['status'] === 'cancelled') $cur_idx = -1;
                    foreach ($tl_steps as $i => $step):
                        if ($cur_idx === false) $cur_idx = 0;
                        $dot_class = ($i < $cur_idx) ? 'done' : (($i === $cur_idx) ? 'active' : 'wait');
                        $label_color = ($i <= $cur_idx) ? '#0f172a' : '#94a3b8';
                    ?>
                    <div class="tl-item">
                        <div class="tl-dot <?= $dot_class ?>">
                            <i class="<?= $step[1] ?>"></i>
                        </div>
                        <div class="tl-label" style="color:<?= $label_color ?>;"><?= $step[2] ?></div>
                        <div class="tl-sub"><?= $step[3] ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($order_detail['status'] === 'cancelled'): ?>
                    <div class="tl-item">
                        <div class="tl-dot" style="background:#dc3545;">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="tl-label text-danger">Cancelled</div>
                        <div class="tl-sub">Order has been cancelled</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Shipping + Payment Info -->
        <div class="detail-card">
            <div class="p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-warning me-2"></i>Order Info</h6>
                <div class="mb-3">
                    <div class="text-muted small mb-1">Shipping Address</div>
                    <div class="fw-semibold" style="font-size:.9rem;">
                        <?= nl2br(htmlspecialchars($order_detail['shipping_address'])) ?>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small mb-1">Payment Method</div>
                    <div class="fw-semibold">
                        <?php if ($order_detail['payment_method'] === 'cod'): ?>
                        <i class="fas fa-money-bill-wave text-success me-1"></i>Cash on Delivery
                        <?php else: ?>
                        <i class="fas fa-credit-card text-primary me-1"></i>Online Payment
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="text-muted small mb-1">Order Date</div>
                    <div class="fw-semibold" style="font-size:.9rem;">
                        <?= date('d M Y, h:i A', strtotime($order_detail['order_date'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif (empty($orders)): ?>
<!-- EMPTY ORDERS -->
<div class="empty-orders">
    <i class="fas fa-box-open fa-5x text-muted mb-4 d-block"></i>
    <h3 class="fw-bold text-muted mb-2">No Orders Yet</h3>
    <p class="text-muted mb-4">You haven't placed any orders yet. Browse spare parts!</p>
    <a href="index.php#products" class="btn btn-warning btn-lg fw-bold px-5">
        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
    </a>
</div>

<?php else: ?>
<!-- ===== ORDERS LIST ===== -->

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $stat_counts = ['pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0];
    $st = $conn->prepare("SELECT status, COUNT(*) AS c FROM orders WHERE user_id=? GROUP BY status");
    $st->bind_param("i", $uid); $st->execute();
    $stat_rows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    foreach ($stat_rows as $sr) $stat_counts[$sr['status']] = $sr['c'];
    $stat_display = [
        ['All',        $total_orders, 'fas fa-box',          'bg-dark',    'text-white'],
        ['Pending',    $stat_counts['pending'],    'fas fa-clock',         'bg-warning', 'text-dark'],
        ['Processing', $stat_counts['processing'], 'fas fa-cog',           'bg-info',    'text-white'],
        ['Shipped',    $stat_counts['shipped'],    'fas fa-shipping-fast', 'bg-primary', 'text-white'],
        ['Delivered',  $stat_counts['delivered'],  'fas fa-check-circle',  'bg-success', 'text-white'],
    ];
    foreach ($stat_display as $sd):
    ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="<?= $sd[3] ?> <?= $sd[4] ?> rounded-3 p-3 text-center">
            <i class="<?= $sd[2] ?> fa-lg mb-1 d-block"></i>
            <div style="font-size:1.3rem;font-weight:800;"><?= $sd[1] ?></div>
            <div style="font-size:.72rem;opacity:.85;"><?= $sd[0] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Orders List -->
<?php foreach ($orders as $order):
    $cfg = $status_cfg[$order['status']] ?? ['bg-secondary text-white','fas fa-circle','Unknown'];
?>
<div class="order-card status-<?= $order['status'] ?>">
    <!-- Card Header -->
    <div class="order-header">
        <div>
            <span class="order-id"><i class="fas fa-hashtag text-muted me-1"></i>Order <?= $order['id'] ?></span>
            <div class="order-date mt-1">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="badge <?= $cfg[0] ?> px-3 py-2" style="font-size:.8rem;border-radius:20px;">
                <i class="<?= $cfg[1] ?> me-1"></i><?= $cfg[2] ?>
            </span>
            <span class="order-total">PKR <?= number_format($order['total_amount']) ?></span>
        </div>
    </div>

    <!-- Card Body -->
    <div class="order-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Item count -->
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:38px;height:38px;background:#f1f5f9;flex-shrink:0;">
                        <i class="fas fa-box text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.88rem;"><?= $order['item_count'] ?> Item(s)</div>
                        <div class="text-muted" style="font-size:.72rem;">Spare Parts</div>
                    </div>
                </div>
                <!-- Payment -->
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:38px;height:38px;background:#f1f5f9;flex-shrink:0;">
                        <?php if ($order['payment_method'] === 'cod'): ?>
                        <i class="fas fa-money-bill-wave text-success"></i>
                        <?php else: ?>
                        <i class="fas fa-credit-card text-primary"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.88rem;">
                            <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment' ?>
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">Payment Method</div>
                    </div>
                </div>
                <!-- Address preview -->
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:38px;height:38px;background:#f1f5f9;flex-shrink:0;">
                        <i class="fas fa-map-marker-alt text-danger"></i>
                    </div>
                    <div>
                        <div class="fw-semibold" style="font-size:.88rem;">
                            <?= htmlspecialchars(substr($order['shipping_address'], 0, 30)) ?>
                            <?= strlen($order['shipping_address']) > 30 ? '...' : '' ?>
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">Delivery Address</div>
                    </div>
                </div>
            </div>
            <!-- View Detail Button -->
            <a href="orders.php?view=<?= $order['id'] ?>" class="btn btn-warning btn-sm fw-bold px-4">
                <i class="fas fa-eye me-1"></i>View Details
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page - 1 ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?page=<?= $page + 1 ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>
</div><!-- /container -->

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 mt-5">
<div class="container text-center">
    <p class="mb-1">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="fas fa-car text-warning me-2"></i>CarBazar
        </a>
    </p>
    <p class="text-muted small mb-0">&copy; 2026 CarBazar. Pakistan's #1 Auto Marketplace.</p>
</div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
