<?php
require_once 'backend/config/db.php';

$wishlist_items = [];
$wl_count       = 0;
$success        = '';
$error          = '';

if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];

    // Auto-migrate: add car_id column if missing
    $col_check = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'car_id'");
    if ($col_check && $col_check->num_rows === 0) {
        $conn->query('ALTER TABLE wishlist MODIFY product_id INT DEFAULT NULL');
        $conn->query('ALTER TABLE wishlist ADD COLUMN car_id INT DEFAULT NULL AFTER product_id');
        $conn->query('ALTER TABLE wishlist ADD UNIQUE KEY unique_wishlist_car (user_id, car_id)');
        $conn->query('ALTER TABLE wishlist ADD FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE');
    }

    // ============================================================
    // POST: Remove from wishlist
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'remove') {
            $pid = (int)($_POST['product_id'] ?? 0);
            $cid = (int)($_POST['car_id']     ?? 0);
            if ($cid) {
                $st = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND car_id = ?");
                $st->bind_param("ii", $uid, $cid);
            } else {
                $st = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $st->bind_param("ii", $uid, $pid);
            }
            $st->execute(); $st->close();
            $success = 'Item removed from wishlist.';

        } elseif ($action === 'clear') {
            $st = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $st->bind_param("i", $uid);
            $st->execute();
            $st->close();
            $success = 'Wishlist cleared.';

        } elseif ($action === 'move_to_cart') {
            $pid = (int)($_POST['product_id'] ?? 0);
            // Check stock
            $st = $conn->prepare("SELECT id, stock FROM products WHERE id = ? AND is_active = 1");
            $st->bind_param("i", $pid);
            $st->execute();
            $prod = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$prod) {
                $error = 'Product not found.';
            } elseif ($prod['stock'] < 1) {
                $error = 'This product is currently out of stock.';
            } else {
                $st = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                $st->bind_param("ii", $uid, $pid);
                $st->execute();
                $exists = $st->get_result()->num_rows > 0;
                $st->close();

                if ($exists) {
                    $st = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
                    $st->bind_param("ii", $uid, $pid);
                } else {
                    $st = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                    $st->bind_param("ii", $uid, $pid);
                }
                $st->execute(); $st->close();

                $st = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $st->bind_param("ii", $uid, $pid);
                $st->execute(); $st->close();

                $success = 'Product moved to cart!';
            }
        }
    }

    // ============================================================
    // Fetch wishlist — products
    // ============================================================
    $st = $conn->prepare("
        SELECT w.id AS wl_id, w.added_at,
               p.id AS product_id, p.name, p.price, p.discount_price,
               p.image, p.stock, p.brand, p.rating, p.reviews_count,
               cat.name AS cat_name,
               'product' AS item_type
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN categories cat ON p.category_id = cat.id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ");
    $st->bind_param("i", $uid);
    $st->execute();
    $wishlist_items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    // ============================================================
    // Fetch wishlist — cars
    // ============================================================
    // Check if car_id column exists first
    $col_check = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'car_id'");
    if ($col_check && $col_check->num_rows > 0) {
        $st = $conn->prepare("
            SELECT w.id AS wl_id, w.added_at,
                   c.id AS car_id, c.title AS name, c.price, NULL AS discount_price,
                   c.image, 1 AS stock, c.brand, 4.5 AS rating, 0 AS reviews_count,
                   CONCAT(c.year, ' • ', c.city) AS cat_name,
                   'car' AS item_type,
                   c.year, c.city, c.mileage, c.fuel_type, c.transmission, c.condition_type
            FROM wishlist w
            JOIN cars c ON w.car_id = c.id
            WHERE w.user_id = ? AND c.is_active = 1
            ORDER BY w.added_at DESC
        ");
        $st->bind_param("i", $uid);
        $st->execute();
        $car_items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        $wishlist_items = array_merge($wishlist_items, $car_items);
    }

    $wl_count = count($wishlist_items);

    // Cart count for navbar badge
    $st2 = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id = ?");
    $st2->bind_param("i", $uid);
    $st2->execute();
    $cart_count = (int)($st2->get_result()->fetch_assoc()['c'] ?? 0);
    $st2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Wishlist - CarBazar</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #f0c040; --dark-navy: #1a1a2e; }
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

/* Hero */
.wl-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 40px 0 30px;
    color: #fff;
}

/* Product Card */
.wl-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transition: transform .25s, box-shadow .25s;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.wl-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 32px rgba(0,0,0,.13);
}
.wl-img-wrap {
    position: relative;
    overflow: hidden;
    height: 180px;
    background: #e2e8f0;
    flex-shrink: 0;
}
.wl-img {
    width: 100%; height: 180px;
    object-fit: cover;
    transition: transform .35s;
}
.wl-card:hover .wl-img { transform: scale(1.06); }

/* Out of stock overlay */
.oos-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.55);
    display: flex; align-items: center; justify-content: center;
}

.wl-body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex-grow: 1; }
.wl-name { font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.wl-price { font-size: 1.05rem; font-weight: 800; color: #e67e22; }
.wl-old-price { font-size: .8rem; color: #94a3b8; text-decoration: line-through; }
.wl-rating { font-size: .72rem; background: #fef9c3; color: #a16207; padding: 3px 8px; border-radius: 6px; font-weight: 700; }

/* Buttons */
.btn-move-cart {
    background: var(--gold); border: none; color: #1a1a2e;
    font-weight: 700; border-radius: 8px; padding: 8px 0;
    width: 100%; font-size: .88rem; cursor: pointer;
    transition: opacity .2s;
}
.btn-move-cart:hover { opacity: .85; }
.btn-remove-wl {
    background: none; border: 1.5px solid #dee2e6; color: #dc3545;
    font-weight: 600; border-radius: 8px; padding: 8px 0;
    width: 100%; font-size: .85rem; cursor: pointer;
    transition: all .2s;
}
.btn-remove-wl:hover { background: #fff0f0; border-color: #dc3545; }

/* States */
.empty-wl { text-align: center; padding: 80px 20px; }
.login-card {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    border-radius: 20px; color: #fff; padding: 50px 30px; text-align: center;
}

/* Date badge */
.date-badge { font-size: .68rem; color: #94a3b8; }

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
</style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<?php $active_page = ''; require_once 'includes/navbar.php'; ?>

<!-- ===== HERO ===== -->
<div class="wl-hero">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="fas fa-heart text-danger me-2"></i>My Wishlist
                </h2>
                <p class="mb-0" style="opacity:.75;">
                    Your saved spare parts — keep them for later
                </p>
            </div>
            <?php if (isLoggedIn() && $wl_count > 0): ?>
            <span class="badge bg-warning text-dark px-3 py-2" style="font-size:.9rem;border-radius:20px;">
                <i class="fas fa-heart me-1"></i><?= $wl_count ?> Items
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- BREADCRUMB -->
<div class="bg-white border-bottom py-2">
<div class="container">
    <nav><ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item">
            <a href="index.php" style="color:var(--gold);text-decoration:none;">Home</a>
        </li>
        <li class="breadcrumb-item active text-muted">Wishlist</li>
    </ol></nav>
</div>
</div>

<!-- ===== MAIN ===== -->
<div class="container py-4">

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="fas fa-check-circle"></i>
    <div><?= htmlspecialchars($success) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
    <i class="fas fa-exclamation-circle"></i>
    <div><?= htmlspecialchars($error) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!isLoggedIn()): ?>
<!-- NOT LOGGED IN -->
<div class="row justify-content-center mt-4">
<div class="col-lg-5 col-md-7">
    <div class="login-card">
        <i class="fas fa-heart fa-4x mb-3 d-block text-danger"></i>
        <h3 class="fw-bold mb-2">Login Required</h3>
        <p style="opacity:.8" class="mb-4">Please login or create an account to view your wishlist.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="login.php" class="btn btn-warning btn-lg fw-bold px-5">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            <a href="register.php" class="btn btn-outline-light btn-lg px-5">
                <i class="fas fa-user-plus me-2"></i>Register
            </a>
        </div>
    </div>
</div>
</div>

<?php elseif (empty($wishlist_items)): ?>
<!-- EMPTY WISHLIST -->
<div class="empty-wl">
    <i class="fas fa-heart fa-5x text-muted mb-4 d-block"></i>
    <h3 class="fw-bold text-muted mb-2">Wishlist is Empty</h3>
    <p class="text-muted mb-4">No saved products. Browse spare parts and save your favorites!</p>
    <a href="index.php#products" class="btn btn-warning btn-lg fw-bold px-5">
        <i class="fas fa-shopping-bag me-2"></i>Browse Parts
    </a>
</div>

<?php else: ?>
<!-- WISHLIST ITEMS -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h5 class="fw-bold mb-0">
        <i class="fas fa-heart text-danger me-2"></i>
        Saved Items
        <span class="badge bg-danger ms-1"><?= $wl_count ?></span>
    </h5>
    <div class="d-flex gap-2 flex-wrap">
        <a href="index.php#products" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Add More Parts
        </a>
        <form method="POST" onsubmit="return confirm('Clear entire wishlist?')">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash me-1"></i>Clear All
            </button>
        </form>
    </div>
</div>

<div class="row g-4">
<?php foreach ($wishlist_items as $item):
    $is_car     = ($item['item_type'] === 'car');
    $price      = (float)$item['price'];
    $disc_price = !empty($item['discount_price']) ? (float)$item['discount_price'] : null;
    $show_price = $disc_price ?? $price;
    $in_stock   = (int)$item['stock'] > 0;
    $rating     = number_format((float)($item['rating'] ?? 4.5), 1);

    if ($is_car) {
        $img_src = (!empty($item['image']) && $item['image'] !== 'default.jpg')
                   ? 'uploads/' . htmlspecialchars($item['image'])
                   : 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=400&q=70';
    } else {
        $img_src = (!empty($item['image']) && $item['image'] !== 'default.jpg')
                   ? 'uploads/' . htmlspecialchars($item['image'])
                   : 'https://via.placeholder.com/400x180/e2e8f0/475569?text=' . urlencode($item['name']);
    }
?>
<div class="col-lg-3 col-md-4 col-sm-6">
    <div class="wl-card">

        <!-- Image -->
        <div class="wl-img-wrap">
            <img src="<?= $img_src ?>"
                 class="wl-img"
                 alt="<?= htmlspecialchars($item['name']) ?>"
                 onerror="this.src='https://via.placeholder.com/400x180/e2e8f0/475569?text=No+Image'">

            <!-- Car / Part badge -->
            <span class="badge <?= $is_car ? 'bg-warning text-dark' : 'bg-primary' ?>"
                  style="position:absolute;top:8px;left:8px;font-size:.68rem;">
                <i class="fas <?= $is_car ? 'fa-car' : 'fa-cogs' ?> me-1"></i>
                <?= $is_car ? 'Car' : 'Part' ?>
            </span>

            <!-- Date saved -->
            <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.65rem;padding:3px 8px;border-radius:6px;">
                <i class="fas fa-clock me-1"></i><?= date('d M', strtotime($item['added_at'])) ?>
            </span>

            <?php if ($is_car && !empty($item['condition_type'])): ?>
            <span class="badge bg-success" style="position:absolute;bottom:8px;left:8px;font-size:.65rem;">
                <?= ucfirst($item['condition_type']) ?>
            </span>
            <?php elseif (!$is_car && !$in_stock): ?>
            <div class="oos-overlay">
                <span class="badge bg-danger" style="font-size:.85rem;padding:8px 14px;">Out of Stock</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="wl-body">
            <div class="mb-1">
                <?php if ($item['brand']): ?>
                <span class="badge bg-light text-dark border" style="font-size:.68rem;">
                    <?= htmlspecialchars($item['brand']) ?>
                </span>
                <?php endif; ?>
            </div>

            <h6 class="wl-name"><?= htmlspecialchars($item['name']) ?></h6>

            <?php if ($is_car): ?>
            <!-- Car details -->
            <div class="d-flex flex-wrap gap-1 mb-2" style="font-size:.75rem;color:#64748b;">
                <?php if (!empty($item['year'])): ?>
                <span><i class="fas fa-calendar me-1 text-warning"></i><?= $item['year'] ?></span>
                <?php endif; ?>
                <?php if (!empty($item['city'])): ?>
                <span><i class="fas fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($item['city']) ?></span>
                <?php endif; ?>
                <?php if (!empty($item['mileage'])): ?>
                <span><i class="fas fa-tachometer-alt me-1 text-primary"></i><?= number_format($item['mileage']) ?> km</span>
                <?php endif; ?>
                <?php if (!empty($item['fuel_type'])): ?>
                <span><i class="fas fa-gas-pump me-1 text-success"></i><?= ucfirst($item['fuel_type']) ?></span>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- Part rating -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="wl-rating">
                    <i class="fas fa-star text-warning me-1"></i><?= $rating ?>
                </span>
                <?php if ($item['reviews_count'] > 0): ?>
                <small class="text-muted">(<?= number_format($item['reviews_count']) ?>)</small>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Price -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="wl-price">PKR <?= number_format($show_price) ?></span>
                <?php if (!$is_car && $disc_price && $disc_price < $price): ?>
                <span class="wl-old-price">PKR <?= number_format($price) ?></span>
                <span class="badge bg-success" style="font-size:.65rem;">
                    <?= round((1 - $disc_price/$price)*100) ?>% OFF
                </span>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-column gap-2 mt-auto">
                <?php if ($is_car): ?>
                <!-- View Car Detail -->
                <a href="car-detail.php?id=<?= (int)$item['car_id'] ?>" class="btn-move-cart text-center text-decoration-none">
                    <i class="fas fa-eye me-1"></i>View Car
                </a>
                <!-- Remove Car -->
                <form method="POST" onsubmit="return confirm('Remove from wishlist?')">
                    <input type="hidden" name="action"  value="remove">
                    <input type="hidden" name="car_id"  value="<?= (int)$item['car_id'] ?>">
                    <button type="submit" class="btn-remove-wl">
                        <i class="fas fa-heart-broken me-1"></i>Remove
                    </button>
                </form>
                <?php else: ?>
                <!-- Move Part to Cart -->
                <?php if ($in_stock): ?>
                <form method="POST">
                    <input type="hidden" name="action"     value="move_to_cart">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <button type="submit" class="btn-move-cart">
                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                    </button>
                </form>
                <?php else: ?>
                <button class="btn-move-cart" disabled style="opacity:.5;cursor:not-allowed;">
                    <i class="fas fa-cart-plus me-1"></i>Out of Stock
                </button>
                <?php endif; ?>
                <!-- Remove Part -->
                <form method="POST" onsubmit="return confirm('Remove from wishlist?')">
                    <input type="hidden" name="action"     value="remove">
                    <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                    <button type="submit" class="btn-remove-wl">
                        <i class="fas fa-heart-broken me-1"></i>Remove
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div><!-- /row -->

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
