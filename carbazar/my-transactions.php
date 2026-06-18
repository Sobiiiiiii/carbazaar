<?php
require_once 'backend/config/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user's transactions
if ($user_type === 'seller') {
    // Seller view - transactions where they are the seller
    $transactions_query = $conn->prepare("
        SELECT t.*,
               b.name AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone,
               CASE 
                   WHEN t.transaction_type = 'car_sale' THEN c.title
                   WHEN t.transaction_type = 'product_sale' THEN p.name
               END AS item_name
        FROM transactions t
        LEFT JOIN users b ON t.buyer_id = b.id
        LEFT JOIN cars c ON t.transaction_type = 'car_sale' AND t.reference_id = c.id
        LEFT JOIN products p ON t.transaction_type = 'product_sale' AND EXISTS (
            SELECT 1 FROM order_items oi WHERE oi.order_id = t.reference_id AND oi.product_id = p.id LIMIT 1
        )
        WHERE t.seller_id = ?
        ORDER BY t.created_at DESC
    ");
    $transactions_query->bind_param("i", $user_id);
    
    // Calculate seller stats
    $stats_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_sales,
            SUM(seller_amount) as total_earnings,
            SUM(CASE WHEN seller_paid = 1 THEN seller_amount ELSE 0 END) as received_amount,
            SUM(CASE WHEN seller_paid = 0 AND payment_status = 'completed' THEN seller_amount ELSE 0 END) as pending_amount
        FROM transactions
        WHERE seller_id = ?
    ");
    $stats_query->bind_param("i", $user_id);
    $stats_query->execute();
    $stats = $stats_query->fetch_assoc();
    
} else {
    // Buyer view - transactions where they are the buyer
    $transactions_query = $conn->prepare("
        SELECT t.*,
               s.name AS seller_name, s.email AS seller_email, s.phone AS seller_phone,
               CASE 
                   WHEN t.transaction_type = 'car_sale' THEN c.title
                   WHEN t.transaction_type = 'product_sale' THEN 'Order Items'
               END AS item_name
        FROM transactions t
        LEFT JOIN users s ON t.seller_id = s.id
        LEFT JOIN cars c ON t.transaction_type = 'car_sale' AND t.reference_id = c.id
        WHERE t.buyer_id = ?
        ORDER BY t.created_at DESC
    ");
    $transactions_query->bind_param("i", $user_id);
    
    // Calculate buyer stats
    $stats_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_purchases,
            SUM(total_amount) as total_spent
        FROM transactions
        WHERE buyer_id = ?
    ");
    $stats_query->bind_param("i", $user_id);
    $stats_query->execute();
    $stats = $stats_query->fetch_assoc();
}

$transactions_query->execute();
$transactions = $transactions_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - CarBazar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --dark: #1a1a2e; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .page-header { background: linear-gradient(135deg, var(--dark) 0%, #0f3460 100%); color: #fff; padding: 40px 0; margin-bottom: 30px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.08); margin-bottom: 20px; }
        .stat-card .icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 12px; }
        .stat-card .value { font-size: 2rem; font-weight: 800; color: var(--dark); }
        .stat-card .label { color: #6b7280; font-size: .9rem; }
        .transaction-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .2s; }
        .transaction-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.12); }
        .badge-paid { background: #4caf50; color: #fff; }
        .badge-pending { background: #ff9800; color: #fff; }
        .badge-completed { background: #2196f3; color: #fff; }
        .badge-failed { background: #f44336; color: #fff; }
    </style>
</head>
<body>

<?php $active_page = 'transactions'; require_once 'includes/navbar.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="mb-2"><i class="fas fa-receipt me-3"></i>My Transactions</h1>
        <p class="mb-0 opacity-75">View all your <?= $user_type === 'seller' ? 'sales and earnings' : 'purchases' ?></p>
    </div>
</div>

<div class="container pb-5">
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php if ($user_type === 'seller'): ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background:#e3f2fd;color:#1976d2;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="value"><?= number_format($stats['total_sales'] ?? 0) ?></div>
                    <div class="label">Total Sales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background:#e8f5e9;color:#388e3c;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="value text-success">PKR <?= number_format($stats['total_earnings'] ?? 0) ?></div>
                    <div class="label">Total Earnings</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background:#f3e5f5;color:#7b1fa2;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="value text-purple">PKR <?= number_format($stats['received_amount'] ?? 0) ?></div>
                    <div class="label">Received</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon" style="background:#fff3e0;color:#f57c00;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="value text-warning">PKR <?= number_format($stats['pending_amount'] ?? 0) ?></div>
                    <div class="label">Pending Payout</div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon" style="background:#e3f2fd;color:#1976d2;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="value"><?= number_format($stats['total_purchases'] ?? 0) ?></div>
                    <div class="label">Total Purchases</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="icon" style="background:#e8f5e9;color:#388e3c;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="value text-success">PKR <?= number_format($stats['total_spent'] ?? 0) ?></div>
                    <div class="label">Total Spent</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info Alert for Sellers -->
    <?php if ($user_type === 'seller' && ($stats['pending_amount'] ?? 0) > 0): ?>
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>
            <strong>Pending Payout: PKR <?= number_format($stats['pending_amount']) ?></strong><br>
            <small>Your earnings will be transferred to your account within 3-5 business days after order completion.</small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transactions List -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Transaction History</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($transactions->num_rows > 0): ?>
                <?php while ($t = $transactions->fetch_assoc()): ?>
                <div class="transaction-card">
                    <div class="row align-items-center">
                        <div class="col-md-1 text-center">
                            <?php if ($t['transaction_type'] === 'car_sale'): ?>
                                <div class="icon" style="background:#e3f2fd;color:#1976d2;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <i class="fas fa-car"></i>
                                </div>
                            <?php else: ?>
                                <div class="icon" style="background:#e8f5e9;color:#388e3c;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <i class="fas fa-cog"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="fw-bold"><?= htmlspecialchars($t['item_name'] ?? 'N/A') ?></div>
                            <small class="text-muted">
                                <?= $t['transaction_type'] === 'car_sale' ? 'Car Sale' : 'Spare Parts' ?>
                                &bull; #<?= $t['id'] ?>
                            </small>
                        </div>

                        <div class="col-md-2">
                            <?php if ($user_type === 'seller'): ?>
                                <div class="small text-muted">Buyer</div>
                                <div class="fw-semibold"><?= htmlspecialchars($t['buyer_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($t['buyer_email']) ?></small>
                            <?php else: ?>
                                <div class="small text-muted">Seller</div>
                                <div class="fw-semibold"><?= htmlspecialchars($t['seller_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($t['seller_email']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-2">
                            <?php if ($user_type === 'seller'): ?>
                                <div class="small text-muted">Your Earning</div>
                                <div class="fs-5 fw-bold text-success">PKR <?= number_format($t['seller_amount']) ?></div>
                                <small class="text-muted">
                                    Total: PKR <?= number_format($t['total_amount']) ?>
                                    <br>Commission: <?= $t['commission_percent'] ?>%
                                </small>
                            <?php else: ?>
                                <div class="small text-muted">Amount Paid</div>
                                <div class="fs-5 fw-bold text-primary">PKR <?= number_format($t['total_amount']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted mb-1">Payment Status</div>
                            <?php
                            $status_badges = [
                                'pending' => 'secondary',
                                'paid' => 'info',
                                'processing' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'dark'
                            ];
                            $badge_class = $status_badges[$t['payment_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge_class ?> mb-1">
                                <?= ucfirst($t['payment_status']) ?>
                            </span>
                            <br>
                            <?php if ($user_type === 'seller'): ?>
                                <?php if ($t['seller_paid']): ?>
                                    <span class="badge badge-paid">
                                        <i class="fas fa-check me-1"></i>Paid to You
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-pending">
                                        <i class="fas fa-clock me-1"></i>Payout Pending
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-2 text-end">
                            <div class="small text-muted mb-2">
                                <i class="fas fa-calendar me-1"></i>
                                <?= date('d M Y', strtotime($t['created_at'])) ?>
                            </div>
                            <div class="small">
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-credit-card me-1"></i>
                                    <?= ucfirst(str_replace('_', ' ', $t['payment_method'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No transactions yet</h5>
                    <p class="text-muted">
                        <?= $user_type === 'seller' ? 'Start selling cars and spare parts to see your earnings here.' : 'Start shopping to see your purchase history here.' ?>
                    </p>
                    <?php if ($user_type === 'seller'): ?>
                        <a href="sell.php" class="btn btn-warning mt-2">
                            <i class="fas fa-plus me-2"></i>List a Car
                        </a>
                    <?php else: ?>
                        <a href="index.php#cars" class="btn btn-warning mt-2">
                            <i class="fas fa-shopping-cart me-2"></i>Start Shopping
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Footer -->
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
