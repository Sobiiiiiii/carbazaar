<?php
require_once 'includes/auth_guard.php';

// Get commission settings
$settings_query = $conn->query("SELECT * FROM platform_settings WHERE setting_key LIKE '%commission%'");
$settings = [];
while ($row = $settings_query->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get all transactions
$transactions_query = $conn->query("
    SELECT t.*,
           b.name AS buyer_name, b.email AS buyer_email,
           s.name AS seller_name, s.email AS seller_email
    FROM transactions t
    JOIN users b ON t.buyer_id = b.id
    JOIN users s ON t.seller_id = s.id
    ORDER BY t.created_at DESC
");

// Calculate totals
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_revenue,
        SUM(commission_amount) as total_commission,
        SUM(seller_amount) as total_seller_amount,
        SUM(CASE WHEN seller_paid = 1 THEN seller_amount ELSE 0 END) as paid_to_sellers,
        SUM(CASE WHEN seller_paid = 0 THEN seller_amount ELSE 0 END) as pending_payouts
    FROM transactions
    WHERE payment_status = 'completed'
");
$stats = $stats_query->fetch_assoc();

// Mark seller as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $stmt = $conn->prepare("UPDATE transactions SET seller_paid = 1, seller_paid_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    header('Location: transactions.php?success=marked_paid');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --dark: #1a1a2e; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: var(--dark); min-height: 100vh; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .badge-paid { background: #4caf50; }
        .badge-pending { background: #ff9800; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar text-white p-0">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="fas fa-money-bill-wave text-success me-2"></i>Transactions & Payouts</h2>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>Seller marked as paid successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#e3f2fd;color:#1976d2;">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Transactions</div>
                                <div class="fs-4 fw-bold"><?= number_format($stats['total_transactions'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#e8f5e9;color:#388e3c;">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Platform Commission</div>
                                <div class="fs-4 fw-bold text-success">PKR <?= number_format($stats['total_commission'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#fff3e0;color:#f57c00;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Pending Payouts</div>
                                <div class="fs-4 fw-bold text-warning">PKR <?= number_format($stats['pending_payouts'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#f3e5f5;color:#7b1fa2;">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Paid to Sellers</div>
                                <div class="fs-4 fw-bold text-purple">PKR <?= number_format($stats['paid_to_sellers'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Settings -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Commission Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-car text-primary me-2"></i>
                                <strong>Car Sales Commission:</strong>
                                <span class="badge bg-primary ms-2"><?= $settings['car_commission_percent'] ?? 5 ?>%</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-cog text-success me-2"></i>
                                <strong>Spare Parts Commission:</strong>
                                <span class="badge bg-success ms-2"><?= $settings['product_commission_percent'] ?? 10 ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Buyer</th>
                                    <th>Seller</th>
                                    <th>Total Amount</th>
                                    <th>Commission</th>
                                    <th>Seller Amount</th>
                                    <th>Payment Status</th>
                                    <th>Seller Paid?</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($transactions_query->num_rows > 0): ?>
                                    <?php while ($t = $transactions_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?= $t['id'] ?></strong></td>
                                        <td>
                                            <?php if ($t['transaction_type'] === 'car_sale'): ?>
                                                <span class="badge bg-primary"><i class="fas fa-car me-1"></i>Car</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><i class="fas fa-cog me-1"></i>Part</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($t['buyer_name']) ?></div>
                                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['buyer_email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($t['seller_name']) ?></div>
                                            <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['seller_email']) ?></div>
                                        </td>
                                        <td><strong>PKR <?= number_format($t['total_amount']) ?></strong></td>
                                        <td>
                                            <span class="text-success fw-bold">PKR <?= number_format($t['commission_amount']) ?></span>
                                            <small class="text-muted">(<?= $t['commission_percent'] ?>%)</small>
                                        </td>
                                        <td><strong>PKR <?= number_format($t['seller_amount']) ?></strong></td>
                                        <td>
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
                                            <span class="badge bg-<?= $badge_class ?>"><?= ucfirst($t['payment_status']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($t['seller_paid']): ?>
                                                <span class="badge badge-paid">
                                                    <i class="fas fa-check me-1"></i>Paid
                                                </span>
                                                <div class="text-muted" style="font-size:.7rem;">
                                                    <?= date('d M Y', strtotime($t['seller_paid_at'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge badge-pending">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                                        <td>
                                            <?php if (!$t['seller_paid'] && $t['payment_status'] === 'completed'): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Mark this seller as paid?')">
                                                <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                                <button type="submit" name="mark_paid" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check me-1"></i>Mark Paid
                                                </button>
                                            </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No transactions yet
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
