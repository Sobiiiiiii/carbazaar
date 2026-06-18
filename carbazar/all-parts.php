<?php
require_once 'backend/config/db.php';

// ============================================================
// Filters
// ============================================================
$filter_cat     = (int)($_GET['cat']     ?? 0);
$filter_catname = trim($_GET['catname'] ?? '');   // category name string
$filter_brand   = trim($_GET['brand']   ?? '');
$filter_min     = (int)($_GET['min']    ?? 0);
$filter_max     = (int)($_GET['max']    ?? 0);
$filter_stock   = trim($_GET['stock']   ?? '');   // 'in' | 'out' | ''
$sort           = trim($_GET['sort']    ?? 'newest');
$search         = trim($_GET['search']  ?? '');

// Resolve catname → cat ID
if ($filter_catname && !$filter_cat) {
    $cn = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $cn->bind_param("s", $filter_catname);
    $cn->execute();
    $cn_row = $cn->get_result()->fetch_assoc();
    $cn->close();
    if ($cn_row) $filter_cat = (int)$cn_row['id'];
}

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

// ============================================================
// Build Query
// ============================================================
$where  = ["p.is_active = 1"];
$params = [];
$types  = '';

if ($filter_cat > 0)   { $where[] = "p.category_id = ?"; $params[] = $filter_cat;   $types .= 'i'; }
if ($filter_brand)     { $where[] = "p.brand = ?";        $params[] = $filter_brand; $types .= 's'; }
if ($filter_min > 0)   { $where[] = "COALESCE(p.discount_price, p.price) >= ?"; $params[] = $filter_min; $types .= 'i'; }
if ($filter_max > 0)   { $where[] = "COALESCE(p.discount_price, p.price) <= ?"; $params[] = $filter_max; $types .= 'i'; }
if ($filter_stock === 'in')  { $where[] = "p.stock > 0"; }
if ($filter_stock === 'out') { $where[] = "p.stock = 0"; }
if ($search) {
    $like = '%' . $search . '%';
    $where[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$where_sql = implode(' AND ', $where);

$order_sql = match($sort) {
    'price_low'  => 'COALESCE(p.discount_price, p.price) ASC',
    'price_high' => 'COALESCE(p.discount_price, p.price) DESC',
    'rating'     => 'p.rating DESC',
    'popular'    => 'p.reviews_count DESC',
    'oldest'     => 'p.created_at ASC',
    default      => 'p.created_at DESC',
};

// Total count
$count_sql = "SELECT COUNT(*) AS total FROM products p WHERE $where_sql";
$st = $conn->prepare($count_sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
$total_parts = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);
$total_pages = (int)ceil($total_parts / $limit);
$st->close();

// Fetch products
$sql = "
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT ? OFFSET ?
";
$st = $conn->prepare($sql);
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$parts = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Categories for filter
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Distinct brands
$brands = $conn->query("SELECT DISTINCT brand FROM products WHERE is_active=1 AND brand != '' ORDER BY brand")->fetch_all(MYSQLI_ASSOC);

// Cart count for navbar
$nc_count = 0; $nw_count = 0;
if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id=?");
    $s->bind_param("i",$uid); $s->execute();
    $nc_count = (int)($s->get_result()->fetch_assoc()['c']??0); $s->close();
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id=?");
    $s->bind_param("i",$uid); $s->execute();
    $nw_count = (int)($s->get_result()->fetch_assoc()['c']??0); $s->close();
}

// Helper: page URL
function partsPageUrl($p) {
    $params = $_GET; $params['page'] = $p;
    return 'all-parts.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Spare Parts - CarBazar</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #f0c040; --dark-navy: #1a1a2e; }
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

/* Hero */
.parts-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1d4ed8 100%);
    padding: 36px 0 28px; color: #fff;
}

/* Filter Sidebar */
.filter-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 22px; position: sticky; top: 80px;
}
.filter-title {
    font-size: .75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1.2px; color: #64748b; margin-bottom: 8px;
}
.filter-card .form-select,
.filter-card .form-control {
    font-size: .88rem; border-radius: 8px;
    border: 1.5px solid #e2e8f0;
}
.filter-card .form-select:focus,
.filter-card .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.12);
}

/* Category pills */
.cat-pill {
    display: inline-block; padding: 5px 12px;
    border-radius: 20px; font-size: .78rem; font-weight: 600;
    border: 1.5px solid #e2e8f0; background: #fff; color: #475569;
    cursor: pointer; text-decoration: none; transition: all .18s;
    white-space: nowrap;
}
.cat-pill:hover { border-color: #0d6efd; color: #0d6efd; background: #eff6ff; }
.cat-pill.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }

.btn-filter {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border: none; color: #fff; font-weight: 700;
    border-radius: 9px; padding: 10px; width: 100%;
    transition: opacity .2s;
}
.btn-filter:hover { opacity: .88; }
.btn-reset {
    background: #f1f5f9; border: none; color: #64748b;
    font-weight: 600; border-radius: 9px; padding: 8px;
    width: 100%; font-size: .85rem; transition: background .2s;
}
.btn-reset:hover { background: #e2e8f0; }

/* Product Card */
.pcard {
    background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    transition: transform .25s, box-shadow .25s;
    height: 100%; display: flex; flex-direction: column;
}
.pcard:hover { transform: translateY(-5px); box-shadow: 0 14px 36px rgba(0,0,0,.13); }
.pcard-img-wrap { position: relative; overflow: hidden; height: 185px; background: #e2e8f0; flex-shrink: 0; }
.pcard-img { width: 100%; height: 185px; object-fit: cover; object-position: center top; transition: transform .35s; display: block; }
.pcard:hover .pcard-img { transform: scale(1.08); }
.pcard-overlay {
    position: absolute; inset: 0;
    background: rgba(15,23,42,.72);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity .22s;
}
.pcard:hover .pcard-overlay { opacity: 1; }
.pcard-quick {
    background: #f59e0b; color: #0f172a; border: none;
    border-radius: 8px; padding: 8px 18px; font-size: .78rem;
    font-weight: 700; cursor: pointer;
}
.pcard-body { padding: 12px 14px 14px; display: flex; flex-direction: column; flex-grow: 1; }
.pcard-name { font-size: .9rem; font-weight: 700; color: #0f172a; margin-bottom: 3px; line-height: 1.3; }
.pcard-price { font-size: 1rem; font-weight: 800; color: #e67e22; }
.pcard-old   { font-size: .78rem; color: #94a3b8; text-decoration: line-through; }
.pcard-rating { background: #fef9c3; color: #a16207; font-size: .68rem; font-weight: 700; padding: 3px 7px; border-radius: 6px; }
.pcard-btn {
    width: 34px; height: 34px; background: #0d6efd; color: #fff;
    border: none; border-radius: 8px; font-size: .82rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .18s, transform .18s; flex-shrink: 0;
}
.pcard-btn:hover { background: #0a58ca; transform: scale(1.1); }
.pcard-wl-btn {
    width: 34px; height: 34px; background: #fff; color: #f87171;
    border: 1.5px solid #fecaca; border-radius: 8px; font-size: .82rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .18s; flex-shrink: 0;
}
.pcard-wl-btn:hover { background: #fff0f0; border-color: #f87171; }

/* Sort bar */
.sort-bar {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: 12px 18px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 20px;
}

/* Active filter badge */
.active-filter {
    display: inline-flex; align-items: center; gap: 5px;
    background: #eff6ff; border: 1px solid #bfdbfe;
    color: #1d4ed8; border-radius: 20px; padding: 3px 10px;
    font-size: .75rem; font-weight: 600;
}

/* No results */
.no-results { text-align: center; padding: 60px 20px; }

/* Pagination */
.page-link { color: var(--dark-navy); border-radius: 8px !important; margin: 0 2px; }
.page-item.active .page-link { background: #0d6efd; border-color: #0d6efd; color: #fff; }

/* Stock badge */
.stock-in  { color: #16a34a; font-size: .72rem; font-weight: 600; }
.stock-out { color: #dc3545; font-size: .72rem; font-weight: 600; }

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #0d6efd; border-radius: 3px; }
</style>
</head>
<body>

<!-- NAVBAR -->
<?php $active_page = 'parts'; require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="parts-hero">
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size:.8rem;">
                    <li class="breadcrumb-item"><a href="index.php" style="color:rgba(255,255,255,.6);text-decoration:none;">Home</a></li>
                    <li class="breadcrumb-item active text-white-50">Spare Parts</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1"><i class="fas fa-cogs text-warning me-2"></i>All Spare Parts</h2>
            <p class="mb-0" style="opacity:.75;">Genuine OEM & aftermarket parts — fast delivery across Pakistan</p>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-3" style="color:var(--gold);"><?= number_format($total_parts) ?></div>
            <small style="opacity:.7;">Parts Listed</small>
        </div>
    </div>
</div>
</div>

<!-- MAIN -->
<div class="container py-4">

<!-- Category Quick Pills -->
<div class="d-flex gap-2 flex-wrap mb-4">
    <a href="all-parts.php" class="cat-pill <?= $filter_cat === 0 ? 'active' : '' ?>">
        <i class="fas fa-th me-1"></i>All
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="all-parts.php?cat=<?= $cat['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
       class="cat-pill <?= $filter_cat === (int)$cat['id'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['name']) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- ===== FILTER SIDEBAR ===== -->
    <div class="col-lg-3">
    <div class="filter-card">
        <form method="GET" id="filterForm">
            <?php if ($filter_cat > 0): ?>
            <input type="hidden" name="cat" value="<?= $filter_cat ?>">
            <?php endif; ?>

            <!-- Search -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-search me-1"></i>Search</div>
                <input type="text" class="form-control" name="search"
                       placeholder="Part name, brand..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <hr class="my-3">

            <!-- Category -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-th-large me-1"></i>Category</div>
                <select class="form-select" name="cat">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filter_cat === (int)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Brand -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-tag me-1"></i>Brand</div>
                <select class="form-select" name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): if (!$b['brand']) continue; ?>
                    <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $filter_brand === $b['brand'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['brand']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price Range -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-money-bill me-1"></i>Price Range (PKR)</div>
                <div class="d-flex gap-2">
                    <input type="number" class="form-control" name="min" placeholder="Min"
                           value="<?= $filter_min ?: '' ?>" min="0">
                    <input type="number" class="form-control" name="max" placeholder="Max"
                           value="<?= $filter_max ?: '' ?>" min="0">
                </div>
            </div>

            <!-- Stock -->
            <div class="mb-4">
                <div class="filter-title"><i class="fas fa-box me-1"></i>Availability</div>
                <select class="form-select" name="stock">
                    <option value="">All</option>
                    <option value="in"  <?= $filter_stock === 'in'  ? 'selected' : '' ?>>In Stock Only</option>
                    <option value="out" <?= $filter_stock === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <button type="submit" class="btn-filter mb-2">
                <i class="fas fa-search me-2"></i>Apply Filters
            </button>
            <a href="all-parts.php" class="btn-reset d-block text-center text-decoration-none">
                <i class="fas fa-times me-1"></i>Reset All
            </a>
        </form>
    </div>
    </div>

    <!-- ===== PARTS GRID ===== -->
    <div class="col-lg-9">

        <!-- Sort Bar -->
        <div class="sort-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold text-muted small">
                    <i class="fas fa-cogs text-primary me-1"></i>
                    <strong><?= number_format($total_parts) ?></strong> parts found
                </span>
                <?php if ($filter_brand): ?><span class="active-filter"><i class="fas fa-tag"></i><?= htmlspecialchars($filter_brand) ?></span><?php endif; ?>
                <?php if ($filter_cat > 0):
                    $cn = '';
                    foreach ($categories as $c) { if ((int)$c['id'] === $filter_cat) { $cn = $c['name']; break; } }
                ?><span class="active-filter"><i class="fas fa-th-large"></i><?= htmlspecialchars($cn) ?></span><?php endif; ?>
                <?php if ($filter_stock === 'in'):  ?><span class="active-filter"><i class="fas fa-check-circle"></i>In Stock</span><?php endif; ?>
                <?php if ($search): ?><span class="active-filter"><i class="fas fa-search"></i>"<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Sort:</span>
                <select class="form-select form-select-sm" style="width:160px;border-radius:8px;"
                        onchange="window.location.href=updateParam('sort',this.value)">
                    <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
                    <option value="price_low"  <?= $sort==='price_low'  ? 'selected':'' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort==='price_high' ? 'selected':'' ?>>Price: High to Low</option>
                    <option value="rating"     <?= $sort==='rating'     ? 'selected':'' ?>>Top Rated</option>
                    <option value="popular"    <?= $sort==='popular'    ? 'selected':'' ?>>Most Popular</option>
                </select>
            </div>
        </div>

        <?php if (empty($parts)): ?>
        <!-- No Results -->
        <div class="no-results">
            <i class="fas fa-cogs fa-4x text-muted mb-3 d-block"></i>
            <h4 class="fw-bold text-muted mb-2">No Parts Found</h4>
            <p class="text-muted mb-4">Try adjusting your filters or search term.</p>
            <a href="all-parts.php" class="btn btn-primary fw-bold px-5">
                <i class="fas fa-times me-2"></i>Clear Filters
            </a>
        </div>

        <?php else: ?>
        <!-- Parts Grid -->
        <div class="row g-3">
        <?php foreach ($parts as $part):
            $price      = (float)$part['price'];
            $disc_price = !empty($part['discount_price']) ? (float)$part['discount_price'] : null;
            $show_price = $disc_price ?? $price;
            $in_stock   = (int)$part['stock'] > 0;
            $rating     = number_format((float)($part['rating'] ?? 4.5), 1);
            $sold       = (int)($part['reviews_count'] ?? 0);
            $sold_fmt   = $sold >= 1000 ? round($sold/1000,1).'k' : $sold;
            $img_src    = (!empty($part['image']) && $part['image'] !== 'default.jpg')
                          ? 'uploads/' . htmlspecialchars($part['image'])
                          : 'https://via.placeholder.com/400x185/e2e8f0/475569?text=' . urlencode($part['name']);
            $img_fb     = 'https://via.placeholder.com/400x185/e2e8f0/475569?text=No+Image';

            // Category CSS class
            $cat_key = strtolower($part['cat_name'] ?? '');
            $cat_colors = [
                'engine parts'  => ['#dbeafe','#1d4ed8'],
                'electrical'    => ['#fef9c3','#a16207'],
                'brakes'        => ['#fee2e2','#b91c1c'],
                'cooling system'=> ['#dcfce7','#15803d'],
                'suspension'    => ['#f3e8ff','#7e22ce'],
                'body parts'    => ['#ffedd5','#c2410c'],
                'transmission'  => ['#e0f2fe','#0369a1'],
                'exhaust'       => ['#f1f5f9','#475569'],
            ];
            $cat_color = $cat_colors[$cat_key] ?? ['#f1f5f9','#475569'];
        ?>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="pcard">
                <!-- Image -->
                <div class="pcard-img-wrap">
                    <img src="<?= $img_src ?>" class="pcard-img"
                         alt="<?= htmlspecialchars($part['name']) ?>"
                         onerror="this.src='<?= $img_fb ?>'">
                    <div class="pcard-overlay">
                        <button class="pcard-quick"
                                onclick="addToCart('<?= addslashes(htmlspecialchars($part['name'])) ?>',<?= $show_price ?>,<?= $part['id'] ?>)">
                            <i class="fas fa-cart-plus me-1"></i>Quick Add
                        </button>
                    </div>
                    <!-- Category badge -->
                    <?php if ($part['cat_name']): ?>
                    <span style="position:absolute;bottom:8px;left:8px;background:<?= $cat_color[0] ?>;color:<?= $cat_color[1] ?>;font-size:.63rem;font-weight:700;padding:3px 9px;border-radius:16px;">
                        <?= htmlspecialchars($part['cat_name']) ?>
                    </span>
                    <?php endif; ?>
                    <!-- Live badge -->
                    <?php if (!empty($part['id'])): ?>
                    <span style="position:absolute;top:8px;right:8px;background:#16a34a;color:#fff;font-size:.6rem;font-weight:700;padding:3px 8px;border-radius:16px;">
                        ✓ Live
                    </span>
                    <?php endif; ?>
                    <!-- Discount badge -->
                    <?php if ($disc_price && $disc_price < $price): ?>
                    <span style="position:absolute;top:8px;left:8px;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:16px;">
                        <?= round((1 - $disc_price/$price)*100) ?>% OFF
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Body -->
                <div class="pcard-body">
                    <!-- Brand -->
                    <?php if ($part['brand']): ?>
                    <span class="badge bg-light text-dark border mb-1" style="font-size:.68rem;width:fit-content;">
                        <?= htmlspecialchars($part['brand']) ?>
                    </span>
                    <?php endif; ?>

                    <h6 class="pcard-name"><?= htmlspecialchars($part['name']) ?></h6>

                    <?php if ($part['description']): ?>
                    <p style="font-size:.72rem;color:#94a3b8;margin:0 0 6px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($part['description']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Rating -->
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="pcard-rating">
                            <i class="fas fa-star text-warning me-1"></i><?= $rating ?>
                        </span>
                        <?php if ($sold > 0): ?>
                        <small class="text-muted"><?= $sold_fmt ?> sold</small>
                        <?php endif; ?>
                        <!-- Stock -->
                        <?php if ($in_stock): ?>
                        <span class="stock-in ms-auto"><i class="fas fa-check-circle me-1"></i>In Stock (<?= $part['stock'] ?>)</span>
                        <?php else: ?>
                        <span class="stock-out ms-auto"><i class="fas fa-times-circle me-1"></i>Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Price + Buttons -->
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2" style="border-top:1px solid #f1f5f9;">
                        <div>
                            <span class="pcard-price">PKR <?= number_format($show_price) ?></span>
                            <?php if ($disc_price && $disc_price < $price): ?>
                            <span class="pcard-old d-block">PKR <?= number_format($price) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <!-- Wishlist -->
                            <button class="pcard-wl-btn" title="Add to Wishlist"
                                    onclick="addToWishlist(<?= $part['id'] ?>, 'part')">
                                <i class="fas fa-heart"></i>
                            </button>
                            <!-- Cart -->
                            <?php if ($in_stock): ?>
                            <button class="pcard-btn" title="Add to Cart"
                                    onclick="addToCart('<?= addslashes(htmlspecialchars($part['name'])) ?>',<?= $show_price ?>,<?= $part['id'] ?>)">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            <?php else: ?>
                            <button class="pcard-btn" disabled style="opacity:.4;cursor:not-allowed;">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div><!-- /row -->

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
                <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= partsPageUrl($page-1) ?>"><i class="fas fa-chevron-left"></i></a></li>
                <?php endif; ?>
                <?php
                $start = max(1, $page-2); $end = min($total_pages, $page+2);
                if ($start > 1): ?><li class="page-item"><a class="page-link" href="<?= partsPageUrl(1) ?>">1</a></li><?php if ($start>2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; endif;
                for ($i=$start; $i<=$end; $i++): ?>
                <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= partsPageUrl($i) ?>"><?= $i ?></a></li>
                <?php endfor;
                if ($end < $total_pages): if ($end<$total_pages-1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="<?= partsPageUrl($total_pages) ?>"><?= $total_pages ?></a></li><?php endif; ?>
                <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="<?= partsPageUrl($page+1) ?>"><i class="fas fa-chevron-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </div><!-- /col-lg-9 -->
</div><!-- /row -->
</div><!-- /container -->

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 mt-5">
<div class="container text-center">
    <p class="mb-1"><a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-car text-warning me-2"></i>CarBazar</a></p>
    <p class="text-muted small mb-0">&copy; 2026 CarBazar. Pakistan's #1 Auto Marketplace.</p>
</div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function updateParam(key, value) {
    var url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.delete('page');
    return url.toString();
}

// Add to Cart
function addToCart(name, price, productId) {
    <?php if (isLoggedIn()): ?>
    if (!productId || productId <= 0) { showNotif('Demo product — cannot add to cart.', 'error'); return; }
    var fd = new FormData();
    fd.append('product_id', productId);
    fd.append('quantity', 1);
    fetch('backend/api/cart.php?action=add', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            showNotif(d.status === 'success' ? '✓ ' + name + ' added to cart!' : (d.message || 'Error'), d.status === 'success' ? 'success' : 'error');
            if (d.status === 'success') {
                var b = document.getElementById('cart-nav-badge');
                if (b) { b.textContent = (parseInt(b.textContent)||0)+1; b.style.display='inline-flex'; }
            }
        });
    <?php else: ?>
    window.location.href = 'login.php';
    <?php endif; ?>
}

// Add to Wishlist
function addToWishlist(id) {
    <?php if (isLoggedIn()): ?>
    var fd = new FormData(); fd.append('product_id', id);
    fetch('backend/api/wishlist.php?action=add', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => showNotif(d.status === 'success' ? '❤️ Added to wishlist!' : (d.message||'Error'), d.status==='success'?'success':'error'));
    <?php else: ?>
    window.location.href = 'login.php';
    <?php endif; ?>
}

function showNotif(msg, type) {
    var n = document.createElement('div');
    n.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;color:#fff;font-weight:600;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,.2);background:' + (type==='success'?'#16a34a':'#dc3545') + ';animation:slideIn .3s ease';
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}
</script>
</body>
</html>
