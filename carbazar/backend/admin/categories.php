<?php
$admin_page = 'categories';
require_once 'includes/auth_guard.php';

$msg = '';
$msg_type = 'success';
$edit_cat = null;

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!$name) {
            $msg = 'Category name is required.';
            $msg_type = 'danger';
        } else {
            $st = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $st->bind_param("ss", $name, $desc);
            if ($st->execute()) {
                $msg = "Category '$name' added successfully.";
            } else {
                $msg = 'Error: ' . $conn->error;
                $msg_type = 'danger';
            }
            $st->close();
        }

    } elseif ($action === 'edit') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        if (!$name || !$cat_id) {
            $msg = 'Category name is required.';
            $msg_type = 'danger';
        } else {
            $st = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $st->bind_param("ssi", $name, $desc, $cat_id);
            $st->execute();
            $st->close();
            $msg = "Category updated successfully.";
        }

    } elseif ($action === 'delete') {
        $cat_id = (int)($_POST['cat_id'] ?? 0);
        if ($cat_id > 0) {
            // Check if products exist
            $st = $conn->prepare("SELECT COUNT(*) AS c FROM products WHERE category_id = ?");
            $st->bind_param("i", $cat_id);
            $st->execute();
            $count = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
            $st->close();

            if ($count > 0) {
                $msg = "This category cannot be deleted — it has $count products. Please move or delete the products first.";
                $msg_type = 'danger';
            } else {
                $conn->query("DELETE FROM categories WHERE id = $cat_id");
                $msg = 'Category deleted.';
                $msg_type = 'warning';
            }
        }
    }
}

// Edit mode
$edit_id = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    $st = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $st->bind_param("i", $edit_id);
    $st->execute();
    $edit_cat = $st->get_result()->fetch_assoc();
    $st->close();
}

// Fetch all categories with product count
$categories = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - CarBazar Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-title"><i class="fas fa-tags me-2 text-success"></i>Categories Management</div>
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

        <div class="row g-4">
            <!-- Add / Edit Form -->
            <div class="col-12 col-md-4">
                <div class="stat-card" style="border-left-color:#10b981">
                    <h6 class="fw-bold mb-3" style="color:#1a1a2e">
                        <i class="fas fa-<?= $edit_cat ? 'edit' : 'plus-circle' ?> me-2 text-success"></i>
                        <?= $edit_cat ? 'Edit Category' : 'Add New Category' ?>
                    </h6>
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $edit_cat ? 'edit' : 'add' ?>">
                        <?php if ($edit_cat): ?>
                        <input type="hidden" name="cat_id" value="<?= $edit_cat['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-600 small">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="e.g. Engine Parts"
                                   value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600 small">Description</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Category description..."><?= htmlspecialchars($edit_cat['description'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn flex-fill" style="background:#10b981;color:#fff;border-radius:8px;font-weight:600">
                                <i class="fas fa-<?= $edit_cat ? 'save' : 'plus' ?> me-1"></i>
                                <?= $edit_cat ? 'Update' : 'Add Category' ?>
                            </button>
                            <?php if ($edit_cat): ?>
                            <a href="categories.php" class="btn btn-outline-secondary" style="border-radius:8px">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="col-12 col-md-8">
                <div class="adm-table">
                    <div class="p-3 border-bottom fw-bold" style="color:#1a1a2e">
                        <i class="fas fa-tags me-2 text-success"></i><?= count($categories) ?> Categories
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-5">Koi categories nahi hain</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                <tr <?= $edit_id === (int)$cat['id'] ? 'style="background:#f0fdf4"' : '' ?>>
                                    <td><strong>#<?= $cat['id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight:600"><?= htmlspecialchars($cat['name']) ?></div>
                                    </td>
                                    <td style="font-size:.82rem;color:#6b7280;max-width:180px">
                                        <?= htmlspecialchars($cat['description'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <span style="background:#dbeafe;color:#1e40af;padding:3px 10px;border-radius:10px;font-size:.78rem;font-weight:600">
                                            <?= $cat['product_count'] ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.78rem;color:#6b7280">
                                        <?= date('d M Y', strtotime($cat['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="?edit=<?= $cat['id'] ?>"
                                               class="btn btn-sm" style="background:#fef3c7;color:#92400e;border-radius:6px;padding:3px 8px;font-size:.75rem" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete this category? It cannot be deleted if it has products.')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
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
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
