<?php
require_once '../config/db.php';

if (!isLoggedIn() || !isSeller()) {
    header('Location: ../../index.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $delete_id, $seller_id);
    $stmt->execute();
    header('Location: products.php?msg=deleted');
    exit;
}

// Pagination
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ? AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $seller_id, $limit, $offset);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Total count
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND is_active = 1");
$count_stmt->bind_param("i", $seller_id);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - CarBazar Seller</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .sidebar { background: #2c3e50; color: white; min-height: 100vh; }
        .stock-low { color: #dc3545; font-weight: bold; }
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
                <li class="mb-3"><a href="products.php" class="text-white text-decoration-none fw-bold"><i class="fas fa-box"></i> My Products</a></li>
                <li class="mb-3"><a href="add-product.php" class="text-white text-decoration-none"><i class="fas fa-plus"></i> Add Product</a></li>
                <li class="mb-3"><a href="orders.php" class="text-white text-decoration-none"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li class="mb-3"><a href="settings.php" class="text-white text-decoration-none"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="mb-3"><a href="../auth/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>My Products</h2>
                <a href="add-product.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Product</a>
            </div>
            <hr>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                <div class="alert alert-success">Product removed successfully.</div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No products yet</h5>
                    <a href="add-product.php" class="btn btn-primary mt-2">Add Your First Product</a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="../../uploads/<?php echo htmlspecialchars($p['image'] ?? 'default.jpg'); ?>"
                                                 onerror="this.src='https://via.placeholder.com/40x40?text=No+Img'"
                                                 width="40" height="40" style="object-fit:cover;border-radius:6px;">
                                            <span><?php echo htmlspecialchars($p['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($p['discount_price']): ?>
                                            <span class="text-decoration-line-through text-muted">PKR <?php echo number_format($p['price'], 0); ?></span>
                                            <span class="text-success fw-bold ms-1">PKR <?php echo number_format($p['discount_price'], 0); ?></span>
                                        <?php else: ?>
                                            PKR <?php echo number_format($p['price'], 0); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $p['stock'] < 10 ? 'stock-low' : ''; ?>">
                                        <?php echo $p['stock']; ?>
                                        <?php if ($p['stock'] < 10): ?><small> ⚠ Low</small><?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove this product?')">
                                            <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
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
