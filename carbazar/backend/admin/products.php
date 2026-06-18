<?php
$admin_page = 'products';
require_once 'includes/auth_guard.php';

$msg = '';
$msg_type = 'success';

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        if ($action === 'deactivate') {
            $conn->query("UPDATE products SET is_active = 0 WHERE id = $product_id");
            $msg = 'Product deactivated.';
            $msg_type = 'warning';
        } elseif ($action === 'activate') {
            $conn->query("UPDATE products SET is_active = 1 WHERE id = $product_id");
            $msg = 'Product activated.';
        } elseif ($action === 'delete') {
            $conn->query("DELETE FROM products WHERE id = $product_id");
            $msg = 'Product permanently deleted.';
            $msg_type = 'danger';
        }
    }
}

// ---- Filters ----
$filter_cat    = (int)($_GET['cat']    ?? 0);
$filter_status = trim($_GET['status'] ?? 'active');
$filter_search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($filter_status === 'active')   { $where[] = "p.is_active = 1"; }
elseif ($filter_status === 'inactive') { $where[] = "p.is_active = 0"; }

if ($filter_cat > 0) { $where[] = "p.category_id = ?"; $params[] = $filter_cat; $types .= 'i'; }
if ($filter_search) {
    $like = '%' . $filter_search . '%';
    $where[] = "(p.name LIKE ? OR p.brand LIKE ?)";
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$st = $conn->prepare("SELECT COUNT(*) AS c FROM products p $where_sql");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
$total_pages = (int)ceil($total / $limit);
$st->close();

// Fetch
$st = $conn->prepare("
    SELECT p.*, c.name AS cat_name, u.name AS seller_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    $where_sql
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$products = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-cogs me-2 text-success"></i>Spare Parts Management</div>
        <div class="adm-topbar-right">
            <div class="adm-user-badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
            <a href="<?= BASE_URL ?>backend/auth/logout.php" class="adm-logout-btn"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
        </div>
    </div>

    <div class="adm-content">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" style="border-radius:10px">
            <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Status Tabs -->
        <div class="d-flex gap-2 mb-3">
            <?php
            $tabs = ['active' => ['Active', '#d1fae5', '#065f46'], 'inactive' => ['Inactive', '#fee2e2', '#991b1b'], '' => ['All', '#f3f4f6', '#374151']];
            foreach ($tabs as $val => [$label, $bg, $color]):
            ?>
            <a href="?status=<?= $val ?>&cat=<?= $filter_cat ?>&search=<?= urlencode($filter_search) ?>"
               class="btn btn-sm" style="background:<?= $filter_status === $val ? $bg : '#fff' ?>;color:<?= $color ?>;border:1px solid <?= $bg ?>;border-radius:8px;font-weight:600">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="stat-card mb-4" style="border-left-color:#10b981">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                <div class="col-12 col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Product name ya brand..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <div class="col-6 col-md-3">
                    <select name="cat" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filter_cat === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-3 col-md-2">
                    <button type="submit" class="btn w-100" style="background:#10b981;color:#fff;border-radius:8px;font-weight:600">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-3 col-md-2">
                    <a href="products.php" class="btn btn-outline-secondary w-100" style="border-radius:8px">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="adm-table">
            <div class="p-3 border-bottom fw-bold" style="color:#1a1a2e">
                <i class="fas fa-cogs me-2 text-success"></i><?= number_format($total) ?> Products Found
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-5">Koi products nahi mile</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td>
                                <?php if ($p['image'] && $p['image'] !== 'default.jpg'): ?>
                                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($p['image']) ?>"
                                     style="width:46px;height:40px;object-fit:cover;border-radius:6px" alt="">
                                <?php else: ?>
                                <div style="width:46px;height:40px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-cog text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    <?= htmlspecialchars($p['name']) ?>
                                </div>
                                <?php if ($p['brand']): ?>
                                <div style="font-size:.75rem;color:#6b7280"><?= htmlspecialchars($p['brand']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.82rem"><?= htmlspecialchars($p['cat_name'] ?? '-') ?></td>
                            <td>
                                <?php if ($p['discount_price']): ?>
                                <div style="font-weight:600;color:#065f46;font-size:.88rem">Rs <?= number_format($p['discount_price'], 0) ?></div>
                                <div style="text-decoration:line-through;color:#9ca3af;font-size:.75rem">Rs <?= number_format($p['price'], 0) ?></div>
                                <?php else: ?>
                                <div style="font-weight:600;color:#065f46;font-size:.88rem">Rs <?= number_format($p['price'], 0) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['stock'] <= 0): ?>
                                    <span style="color:#991b1b;font-weight:600;font-size:.82rem">Out of Stock</span>
                                <?php elseif ($p['stock'] < 10): ?>
                                    <span style="color:#92400e;font-weight:600;font-size:.82rem">Low: <?= $p['stock'] ?></span>
                                <?php else: ?>
                                    <span style="color:#065f46;font-weight:600;font-size:.82rem"><?= $p['stock'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.82rem"><?= htmlspecialchars($p['seller_name']) ?></td>
                            <td>
                                <?php if ($p['is_active']): ?>
                                    <span class="badge badge-active" style="border-radius:6px;padding:4px 8px;font-size:.72rem">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-blocked" style="border-radius:6px;padding:4px 8px;font-size:.72rem">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <?php if ($p['is_active']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="deactivate">
                                        <button class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Deactivate">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button class="btn btn-sm" style="background:#d1fae5;color:#065f46;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Activate">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this product?')">`
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&cat=<?= $filter_cat ?>&search=<?= urlencode($filter_search) ?>">
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
