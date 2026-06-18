<?php
$admin_page = 'cars';
require_once 'includes/auth_guard.php';

$msg = '';
$msg_type = 'success';

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $car_id = (int)($_POST['car_id'] ?? 0);

    if ($car_id > 0) {
        if ($action === 'delete') {
            $conn->query("UPDATE cars SET is_active = 0 WHERE id = $car_id");
            $msg = 'Car listing remove kar di gayi.';
            $msg_type = 'warning';
        } elseif ($action === 'restore') {
            $conn->query("UPDATE cars SET is_active = 1 WHERE id = $car_id");
            $msg = 'Car listing restored.';
        } elseif ($action === 'mark_sold') {
            $conn->query("UPDATE cars SET is_sold = 1 WHERE id = $car_id");
            $msg = 'Car sold mark ho gayi.';
        } elseif ($action === 'mark_unsold') {
            $conn->query("UPDATE cars SET is_sold = 0 WHERE id = $car_id");
            $msg = 'Car unsold mark ho gayi.';
        } elseif ($action === 'hard_delete') {
            $conn->query("DELETE FROM cars WHERE id = $car_id");
            $msg = 'Car permanently delete ho gayi.';
            $msg_type = 'danger';
        }
    }
}

// ---- Filters ----
$filter_brand  = trim($_GET['brand']  ?? '');
$filter_city   = trim($_GET['city']   ?? '');
$filter_status = trim($_GET['status'] ?? 'active');
$filter_search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($filter_status === 'active')   { $where[] = "c.is_active = 1 AND c.is_sold = 0"; }
elseif ($filter_status === 'sold') { $where[] = "c.is_sold = 1"; }
elseif ($filter_status === 'inactive') { $where[] = "c.is_active = 0"; }
else { /* all */ }

if ($filter_brand) { $where[] = "c.brand = ?"; $params[] = $filter_brand; $types .= 's'; }
if ($filter_city)  { $where[] = "c.city = ?";  $params[] = $filter_city;  $types .= 's'; }
if ($filter_search) {
    $like = '%' . $filter_search . '%';
    $where[] = "(c.title LIKE ? OR c.brand LIKE ? OR c.model LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total
$st = $conn->prepare("SELECT COUNT(*) AS c FROM cars c $where_sql");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
$total_pages = (int)ceil($total / $limit);
$st->close();

// Fetch
$st = $conn->prepare("
    SELECT c.*, u.name AS seller_name, u.email AS seller_email
    FROM cars c
    JOIN users u ON c.seller_id = u.id
    $where_sql
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$cars = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Distinct brands for filter
$brands = $conn->query("SELECT DISTINCT brand FROM cars WHERE brand != '' ORDER BY brand")->fetch_all(MYSQLI_ASSOC);
$cities = $conn->query("SELECT DISTINCT city FROM cars WHERE city != '' ORDER BY city")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-car me-2 text-warning"></i>Cars Management</div>
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
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <?php
            $tabs = ['active' => ['Active', '#d1fae5', '#065f46'], 'sold' => ['Sold', '#fef3c7', '#92400e'],
                     'inactive' => ['Removed', '#fee2e2', '#991b1b'], '' => ['All', '#f3f4f6', '#374151']];
            foreach ($tabs as $val => [$label, $bg, $color]):
            ?>
            <a href="?status=<?= $val ?>&brand=<?= urlencode($filter_brand) ?>&city=<?= urlencode($filter_city) ?>&search=<?= urlencode($filter_search) ?>"
               class="btn btn-sm" style="background:<?= $filter_status === $val ? $bg : '#fff' ?>;color:<?= $color ?>;border:1px solid <?= $bg ?>;border-radius:8px;font-weight:600">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="stat-card mb-4" style="border-left-color:#f0c040">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                <div class="col-12 col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Title, brand ya model..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <select name="brand" class="form-select">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $b): ?>
                        <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $filter_brand === $b['brand'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['brand']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="city" class="form-select">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c['city']) ?>" <?= $filter_city === $c['city'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['city']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn w-100" style="background:#f0c040;color:#1a1a2e;border-radius:8px;font-weight:600">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-6 col-md-2">
                    <a href="cars.php" class="btn btn-outline-secondary w-100" style="border-radius:8px">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="adm-table">
            <div class="p-3 border-bottom fw-bold" style="color:#1a1a2e">
                <i class="fas fa-car me-2 text-warning"></i><?= number_format($total) ?> Cars Found
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Brand/Model</th>
                            <th>Price</th>
                            <th>City</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($cars)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-5">Koi cars nahi mili</td></tr>
                    <?php else: ?>
                        <?php foreach ($cars as $c): ?>
                        <tr>
                            <td><strong>#<?= $c['id'] ?></strong></td>
                            <td>
                                <?php if ($c['image'] && $c['image'] !== 'default.jpg'): ?>
                                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($c['image']) ?>"
                                     style="width:50px;height:40px;object-fit:cover;border-radius:6px" alt="">
                                <?php else: ?>
                                <div style="width:50px;height:40px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center">
                                    <i class="fas fa-car text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.88rem;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    <?= htmlspecialchars($c['title']) ?>
                                </div>
                                <div style="font-size:.75rem;color:#6b7280"><?= $c['year'] ?></div>
                            </td>
                            <td style="font-size:.85rem"><?= htmlspecialchars($c['brand']) ?> <?= htmlspecialchars($c['model']) ?></td>
                            <td style="font-weight:600;color:#065f46">Rs <?= number_format($c['price'], 0) ?></td>
                            <td style="font-size:.85rem"><?= htmlspecialchars($c['city']) ?></td>
                            <td style="font-size:.82rem">
                                <div><?= htmlspecialchars($c['seller_name']) ?></div>
                                <div style="color:#6b7280"><?= htmlspecialchars($c['seller_email']) ?></div>
                            </td>
                            <td>
                                <?php if (!$c['is_active']): ?>
                                    <span class="badge badge-blocked" style="border-radius:6px;padding:4px 8px;font-size:.72rem">Removed</span>
                                <?php elseif ($c['is_sold']): ?>
                                    <span class="badge badge-processing" style="border-radius:6px;padding:4px 8px;font-size:.72rem">Sold</span>
                                <?php else: ?>
                                    <span class="badge badge-active" style="border-radius:6px;padding:4px 8px;font-size:.72rem">Active</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.78rem;color:#6b7280"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>car-detail.php?id=<?= $c['id'] ?>" target="_blank"
                                       class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:6px;padding:3px 8px;font-size:.75rem" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($c['is_active'] && !$c['is_sold']): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Is car ko remove karna chahte ho?')">
                                        <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Remove">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="mark_sold">
                                        <button class="btn btn-sm" style="background:#d1fae5;color:#065f46;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Mark Sold">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                    </form>
                                    <?php elseif (!$c['is_active']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="restore">
                                        <button class="btn btn-sm" style="background:#d1fae5;color:#065f46;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <?php elseif ($c['is_sold']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="mark_unsold">
                                        <button class="btn btn-sm" style="background:#f3f4f6;color:#374151;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Mark Unsold">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Car permanently delete karna chahte ho?')">
                                        <input type="hidden" name="car_id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="action" value="hard_delete">
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

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center p-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&brand=<?= urlencode($filter_brand) ?>&city=<?= urlencode($filter_city) ?>&search=<?= urlencode($filter_search) ?>">
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
