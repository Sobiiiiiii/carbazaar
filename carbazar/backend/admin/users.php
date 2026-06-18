<?php
$admin_page = 'users';
require_once 'includes/auth_guard.php';

$msg = '';
$msg_type = 'success';

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        if ($action === 'block') {
            $conn->query("UPDATE users SET is_blocked = 1 WHERE id = $user_id AND user_type != 'admin'");
            $msg = 'User blocked.';
        } elseif ($action === 'unblock') {
            $conn->query("UPDATE users SET is_blocked = 0 WHERE id = $user_id");
            $msg = 'User unblocked.';
        } elseif ($action === 'delete') {
            $conn->query("DELETE FROM users WHERE id = $user_id AND user_type != 'admin'");
            $msg = 'User deleted.';
            $msg_type = 'danger';
        } elseif ($action === 'make_seller') {
            $conn->query("UPDATE users SET user_type = 'seller' WHERE id = $user_id AND user_type = 'buyer'");
            $msg = 'User ko seller bana diya.';
        } elseif ($action === 'make_buyer') {
            $conn->query("UPDATE users SET user_type = 'buyer' WHERE id = $user_id AND user_type = 'seller'");
            $msg = 'User ko buyer bana diya.';
        }
    }
}

// ---- Filters ----
$filter_type   = trim($_GET['type']   ?? '');
$filter_search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where  = ["user_type != 'admin'"];
$params = [];
$types  = '';

if ($filter_type && in_array($filter_type, ['buyer','seller'])) {
    $where[] = "user_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}
if ($filter_search) {
    $like = '%' . $filter_search . '%';
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = implode(' AND ', $where);

// Total
$st = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE $where_sql");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$total = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
$total_pages = (int)ceil($total / $limit);
$st->close();

// Fetch
$st = $conn->prepare("SELECT * FROM users WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$users = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-users me-2 text-primary"></i>Users Management</div>
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

        <!-- Filters -->
        <div class="stat-card mb-4" style="border-left-color:#6366f1">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-600 small">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, email ya phone..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-600 small">User Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="buyer"  <?= $filter_type === 'buyer'  ? 'selected' : '' ?>>Buyers</option>
                        <option value="seller" <?= $filter_type === 'seller' ? 'selected' : '' ?>>Sellers</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn w-100" style="background:#6366f1;color:#fff;border-radius:8px;font-weight:600">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
                <div class="col-6 col-md-2">
                    <a href="users.php" class="btn btn-outline-secondary w-100" style="border-radius:8px">Reset</a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="adm-table">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                <div class="fw-bold" style="color:#1a1a2e">
                    <i class="fas fa-list me-2 text-primary"></i>
                    <?= number_format($total) ?> Users Found
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">Koi users nahi mile</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong>#<?= $u['id'] ?></strong></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($u['name']) ?></div>
                            </td>
                            <td style="font-size:.85rem"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="font-size:.85rem"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $u['user_type'] ?>" style="border-radius:6px;padding:4px 10px;font-size:.75rem">
                                    <?= ucfirst($u['user_type']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($u['is_blocked'])): ?>
                                    <span class="badge badge-blocked" style="border-radius:6px;padding:4px 10px;font-size:.75rem">Blocked</span>
                                <?php else: ?>
                                    <span class="badge badge-active" style="border-radius:6px;padding:4px 10px;font-size:.75rem">Active</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.78rem;color:#6b7280">
                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php if (empty($u['is_blocked'])): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Is user ko block karna chahte ho?')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="block">
                                        <button class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:6px;font-size:.75rem;padding:3px 8px" title="Block">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="unblock">
                                        <button class="btn btn-sm" style="background:#d1fae5;color:#065f46;border-radius:6px;font-size:.75rem;padding:3px 8px" title="Unblock">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($u['user_type'] === 'buyer'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="make_seller">
                                        <button class="btn btn-sm" style="background:#dbeafe;color:#1e40af;border-radius:6px;font-size:.75rem;padding:3px 8px" title="Make Seller">
                                            <i class="fas fa-store"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="make_buyer">
                                        <button class="btn btn-sm" style="background:#f3f4f6;color:#374151;border-radius:6px;font-size:.75rem;padding:3px 8px" title="Make Buyer">
                                            <i class="fas fa-user"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this user? This action cannot be undone!')">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border-radius:6px;font-size:.75rem;padding:3px 8px" title="Delete">
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
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= urlencode($filter_type) ?>&search=<?= urlencode($filter_search) ?>">
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
