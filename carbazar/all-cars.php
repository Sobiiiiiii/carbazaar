<?php
require_once 'backend/config/db.php';

// ============================================================
// Filters from GET
// ============================================================
$filter_brand    = trim($_GET['brand']    ?? '');
$filter_city     = trim($_GET['city']     ?? '');
$filter_min      = (int)($_GET['min']     ?? 0);
$filter_max      = (int)($_GET['max']     ?? 0);
$filter_fuel     = trim($_GET['fuel']     ?? '');
$filter_trans    = trim($_GET['trans']    ?? '');
$filter_cond     = trim($_GET['cond']     ?? '');
$filter_year_min = (int)($_GET['year_min'] ?? 0);
$filter_year_max = (int)($_GET['year_max'] ?? 0);
$sort            = trim($_GET['sort']     ?? 'newest');
$search          = trim($_GET['search']   ?? '');

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 9;
$offset = ($page - 1) * $limit;

// ============================================================
// Build Query
// ============================================================
$where  = ["c.is_active = 1", "c.is_sold = 0"];
$params = [];
$types  = '';

if ($filter_brand)    { $where[] = "c.brand = ?";        $params[] = $filter_brand;    $types .= 's'; }
if ($filter_city)     { $where[] = "c.city = ?";         $params[] = $filter_city;     $types .= 's'; }
if ($filter_fuel)     { $where[] = "c.fuel_type = ?";    $params[] = $filter_fuel;     $types .= 's'; }
if ($filter_trans)    { $where[] = "c.transmission = ?"; $params[] = $filter_trans;    $types .= 's'; }
if ($filter_cond)     { $where[] = "c.condition_type = ?"; $params[] = $filter_cond;   $types .= 's'; }
if ($filter_min > 0)  { $where[] = "c.price >= ?";       $params[] = $filter_min;      $types .= 'i'; }
if ($filter_max > 0)  { $where[] = "c.price <= ?";       $params[] = $filter_max;      $types .= 'i'; }
if ($filter_year_min > 0) { $where[] = "c.year >= ?";    $params[] = $filter_year_min; $types .= 'i'; }
if ($filter_year_max > 0) { $where[] = "c.year <= ?";    $params[] = $filter_year_max; $types .= 'i'; }
if ($search) {
    $like = '%' . $search . '%';
    $where[] = "(c.title LIKE ? OR c.brand LIKE ? OR c.model LIKE ? OR c.city LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}

$where_sql = implode(' AND ', $where);

$order_sql = match($sort) {
    'price_low'  => 'c.price ASC',
    'price_high' => 'c.price DESC',
    'oldest'     => 'c.created_at ASC',
    'year_new'   => 'c.year DESC',
    'year_old'   => 'c.year ASC',
    default      => 'c.created_at DESC',
};

// Total count
$count_sql = "SELECT COUNT(*) AS total FROM cars c WHERE $where_sql";
$st = $conn->prepare($count_sql);
if ($params) { $st->bind_param($types, ...$params); }
$st->execute();
$total_cars  = (int)($st->get_result()->fetch_assoc()['total'] ?? 0);
$total_pages = (int)ceil($total_cars / $limit);
$st->close();

// Fetch cars
$cars_sql = "
    SELECT c.*, u.name AS seller_name, u.phone AS seller_phone
    FROM cars c
    JOIN users u ON c.seller_id = u.id
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT ? OFFSET ?
";
$st = $conn->prepare($cars_sql);
$all_params = array_merge($params, [$limit, $offset]);
$all_types  = $types . 'ii';
$st->bind_param($all_types, ...$all_params);
$st->execute();
$cars = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Distinct brands & cities for filter dropdowns
$brands = $conn->query("SELECT DISTINCT brand FROM cars WHERE is_active=1 AND is_sold=0 ORDER BY brand")->fetch_all(MYSQLI_ASSOC);
$cities = $conn->query("SELECT DISTINCT city  FROM cars WHERE is_active=1 AND is_sold=0 AND city IS NOT NULL ORDER BY city")->fetch_all(MYSQLI_ASSOC);

// Condition badge helper
function carBadge($cond) {
    return match($cond) {
        'excellent'    => ['bg-success',  'Excellent'],
        'good'         => ['bg-primary',  'Good'],
        'fair'         => ['bg-warning text-dark', 'Fair'],
        'needs_repair' => ['bg-danger',   'Needs Repair'],
        default        => ['bg-secondary', ucfirst($cond)],
    };
}

$current_year = (int)date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Used Cars for Sale in Pakistan - CarBazar</title>

<?php 
require_once 'includes/seo.php';
generate_seo_tags([
    'title' => 'Used Cars for Sale in Pakistan - Honda, Toyota, Suzuki',
    'description' => 'Browse 1000+ used cars for sale in Pakistan. Find Honda City, Toyota Corolla, Suzuki Alto and more. Filter by price, city, brand. Best deals in Karachi, Lahore, Islamabad.',
    'keywords' => 'used cars pakistan, cars for sale, honda city price, toyota corolla, suzuki alto, buy car karachi, lahore cars, islamabad cars, second hand cars',
    'url' => 'http://localhost/carbazar/all-cars.php',
    'type' => 'website'
]);
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #f0c040; --dark-navy: #1a1a2e; }
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

/* Hero */
.cars-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 36px 0 28px; color: #fff;
}

/* Filter Sidebar */
.filter-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 22px; position: sticky; top: 80px;
}
.filter-title {
    font-size: .78rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1.2px; color: #64748b; margin-bottom: 10px;
}
.filter-card .form-select,
.filter-card .form-control {
    font-size: .88rem; border-radius: 8px;
    border: 1.5px solid #e2e8f0;
}
.filter-card .form-select:focus,
.filter-card .form-control:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(240,192,64,.15);
}
.btn-filter {
    background: linear-gradient(135deg, #f0c040, #e0a800);
    border: none; color: #1a1a2e; font-weight: 700;
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

/* Car Card */
.car-card {
    background: #fff; border-radius: 14px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    transition: transform .25s, box-shadow .25s;
    height: 100%; display: flex; flex-direction: column;
}
.car-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0,0,0,.14);
}
.car-img-wrap { position: relative; overflow: hidden; height: 210px; flex-shrink: 0; }
.car-img {
    width: 100%; height: 210px; object-fit: cover;
    transition: transform .4s;
}
.car-card:hover .car-img { transform: scale(1.06); }
.car-body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex-grow: 1; }
.car-title { font-size: .95rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
.car-price { font-size: 1.1rem; font-weight: 800; color: #e67e22; }

/* Spec pills */
.spec-pill {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f5f9; border-radius: 6px;
    padding: 4px 8px; font-size: .72rem; color: #475569; font-weight: 600;
}

/* Sort bar */
.sort-bar {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    padding: 12px 18px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 20px;
}

/* No results */
.no-results { text-align: center; padding: 60px 20px; }

/* Pagination */
.page-link { color: var(--dark-navy); border-radius: 8px !important; margin: 0 2px; }
.page-item.active .page-link { background: var(--gold); border-color: var(--gold); color: #1a1a2e; }

/* Active filter badge */
.active-filter {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(240,192,64,.15); border: 1px solid rgba(240,192,64,.4);
    color: #92400e; border-radius: 20px; padding: 3px 10px;
    font-size: .75rem; font-weight: 600;
}

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
</style>
</head>
<body>

<!-- NAVBAR -->
<?php $active_page = 'cars'; require_once 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="cars-hero">
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0" style="font-size:.8rem;">
                    <li class="breadcrumb-item"><a href="index.php" style="color:rgba(240,192,64,.8);text-decoration:none;">Home</a></li>
                    <li class="breadcrumb-item active text-white-50">All Cars</li>
                </ol>
            </nav>
            <h2 class="fw-bold mb-1"><i class="fas fa-car text-warning me-2"></i>Used Cars For Sale</h2>
            <p class="mb-0" style="opacity:.75;">Browse all verified used cars across Pakistan</p>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-3" style="color:var(--gold);"><?= number_format($total_cars) ?></div>
            <small style="opacity:.7;">Cars Listed</small>
        </div>
    </div>
</div>
</div>

<!-- MAIN -->
<div class="container py-4">
<div class="row g-4">

    <!-- ===== FILTER SIDEBAR ===== -->
    <div class="col-lg-3">
    <div class="filter-card">
        <form method="GET" id="filterForm">

            <!-- Search -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-search me-1"></i>Search</div>
                <input type="text" class="form-control" name="search"
                       placeholder="Brand, model, city..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <hr class="my-3">

            <!-- Brand -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-car me-1"></i>Brand</div>
                <select class="form-select" name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                    <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $filter_brand === $b['brand'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['brand']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- City -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-map-marker-alt me-1"></i>City</div>
                <select class="form-select" name="city">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                    <option value="<?= htmlspecialchars($c['city']) ?>" <?= $filter_city === $c['city'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['city']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Price Range -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-tag me-1"></i>Price Range (PKR)</div>
                <div class="d-flex gap-2">
                    <input type="number" class="form-control" name="min" placeholder="Min"
                           value="<?= $filter_min ?: '' ?>" min="0">
                    <input type="number" class="form-control" name="max" placeholder="Max"
                           value="<?= $filter_max ?: '' ?>" min="0">
                </div>
            </div>

            <!-- Year Range -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-calendar me-1"></i>Year</div>
                <div class="d-flex gap-2">
                    <select class="form-select" name="year_min">
                        <option value="">From</option>
                        <?php for ($y = $current_year; $y >= 1990; $y--): ?>
                        <option value="<?= $y ?>" <?= $filter_year_min === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select class="form-select" name="year_max">
                        <option value="">To</option>
                        <?php for ($y = $current_year; $y >= 1990; $y--): ?>
                        <option value="<?= $y ?>" <?= $filter_year_max === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Fuel Type -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-gas-pump me-1"></i>Fuel Type</div>
                <select class="form-select" name="fuel">
                    <option value="">All</option>
                    <?php foreach (['petrol'=>'Petrol','diesel'=>'Diesel','hybrid'=>'Hybrid','electric'=>'Electric','cng'=>'CNG'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $filter_fuel === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Transmission -->
            <div class="mb-3">
                <div class="filter-title"><i class="fas fa-cog me-1"></i>Transmission</div>
                <select class="form-select" name="trans">
                    <option value="">All</option>
                    <option value="manual"    <?= $filter_trans === 'manual'    ? 'selected' : '' ?>>Manual</option>
                    <option value="automatic" <?= $filter_trans === 'automatic' ? 'selected' : '' ?>>Automatic</option>
                </select>
            </div>

            <!-- Condition -->
            <div class="mb-4">
                <div class="filter-title"><i class="fas fa-star me-1"></i>Condition</div>
                <select class="form-select" name="cond">
                    <option value="">All</option>
                    <?php foreach (['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair','needs_repair'=>'Needs Repair'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $filter_cond === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-filter mb-2">
                <i class="fas fa-search me-2"></i>Apply Filters
            </button>
            <a href="all-cars.php" class="btn-reset d-block text-center text-decoration-none">
                <i class="fas fa-times me-1"></i>Reset All
            </a>
        </form>
    </div>
    </div>

    <!-- ===== CARS GRID ===== -->
    <div class="col-lg-9">

        <!-- Sort Bar -->
        <div class="sort-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold text-muted small">
                    <i class="fas fa-car text-warning me-1"></i>
                    <strong><?= number_format($total_cars) ?></strong> cars found
                </span>
                <!-- Active filters -->
                <?php if ($filter_brand): ?><span class="active-filter"><i class="fas fa-car"></i><?= htmlspecialchars($filter_brand) ?></span><?php endif; ?>
                <?php if ($filter_city):  ?><span class="active-filter"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($filter_city) ?></span><?php endif; ?>
                <?php if ($filter_fuel):  ?><span class="active-filter"><i class="fas fa-gas-pump"></i><?= ucfirst($filter_fuel) ?></span><?php endif; ?>
                <?php if ($filter_trans): ?><span class="active-filter"><i class="fas fa-cog"></i><?= ucfirst($filter_trans) ?></span><?php endif; ?>
                <?php if ($search):       ?><span class="active-filter"><i class="fas fa-search"></i>"<?= htmlspecialchars($search) ?>"</span><?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Sort:</span>
                <select class="form-select form-select-sm" style="width:150px;border-radius:8px;"
                        onchange="window.location.href=updateParam('sort',this.value)">
                    <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
                    <option value="price_low"  <?= $sort==='price_low'  ? 'selected':'' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort==='price_high' ? 'selected':'' ?>>Price: High to Low</option>
                    <option value="year_new"   <?= $sort==='year_new'   ? 'selected':'' ?>>Year: Newest</option>
                    <option value="year_old"   <?= $sort==='year_old'   ? 'selected':'' ?>>Year: Oldest</option>
                </select>
            </div>
        </div>

        <?php if (empty($cars)): ?>
        <!-- No Results -->
        <div class="no-results">
            <i class="fas fa-car fa-4x text-muted mb-3 d-block"></i>
            <h4 class="fw-bold text-muted mb-2">No Cars Found</h4>
            <p class="text-muted mb-4">Try adjusting your filters or search term.</p>
            <a href="all-cars.php" class="btn btn-warning fw-bold px-5">
                <i class="fas fa-times me-2"></i>Clear Filters
            </a>
        </div>

        <?php else: ?>
        <!-- Cars Grid -->
        <div class="row g-4" id="carsGrid">
        <?php foreach ($cars as $car):
            $badge    = carBadge($car['condition_type'] ?? 'good');
            $img_src  = (!empty($car['image']) && $car['image'] !== 'default.jpg')
                        ? 'uploads/' . htmlspecialchars($car['image'])
                        : 'https://via.placeholder.com/500x210/1a1a2e/f0c040?text=' . urlencode($car['title']);
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="car-card">
                <!-- Image -->
                <div class="car-img-wrap">
                    <img src="<?= $img_src ?>"
                         alt="<?= htmlspecialchars($car['title']) ?>"
                         class="car-img"
                         onerror="this.src='https://via.placeholder.com/500x210/1a1a2e/f0c040?text=No+Image'">
                    <span class="badge <?= $badge[0] ?>"
                          style="position:absolute;top:12px;left:12px;font-size:.78rem;padding:6px 10px;">
                        <?= $badge[1] ?>
                    </span>
                    <span class="badge bg-dark"
                          style="position:absolute;top:12px;right:12px;font-size:.78rem;padding:6px 10px;">
                        <?= htmlspecialchars($car['year']) ?>
                    </span>
                    <!-- Live badge -->
                    <span class="badge bg-success"
                          style="position:absolute;bottom:10px;left:12px;font-size:.68rem;padding:4px 8px;">
                        <i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Live
                    </span>
                </div>

                <!-- Body -->
                <div class="car-body">
                    <h6 class="car-title"><?= htmlspecialchars($car['title']) ?></h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="car-price">PKR <?= number_format($car['price']) ?></span>
                        <span class="text-muted small">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            <?= htmlspecialchars($car['city'] ?? 'Pakistan') ?>
                        </span>
                    </div>

                    <!-- Specs -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="spec-pill">
                            <i class="fas fa-tachometer-alt text-primary"></i>
                            <?= number_format($car['mileage'] ?? 0) ?> km
                        </span>
                        <span class="spec-pill">
                            <i class="fas fa-gas-pump text-success"></i>
                            <?= ucfirst($car['fuel_type'] ?? 'Petrol') ?>
                        </span>
                        <span class="spec-pill">
                            <i class="fas fa-cog text-warning"></i>
                            <?= ucfirst($car['transmission'] ?? 'Manual') ?>
                        </span>
                        <span class="spec-pill">
                            <i class="fas fa-car text-info"></i>
                            <?= htmlspecialchars($car['brand']) ?>
                        </span>
                    </div>

                    <!-- Seller -->
                    <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded-2" style="background:#f8f9fa;">
                        <div style="width:28px;height:28px;background:linear-gradient(135deg,#f0c040,#e0a800);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#1a1a2e;flex-shrink:0;">
                            <?= strtoupper(substr($car['seller_name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-size:.78rem;font-weight:600;color:#0f172a;"><?= htmlspecialchars($car['seller_name'] ?? 'Seller') ?></div>
                            <div style="font-size:.68rem;color:#64748b;">Verified Seller</div>
                        </div>
                        <div class="ms-auto">
                            <small class="text-muted" style="font-size:.68rem;">
                                <?= date('d M Y', strtotime($car['created_at'])) ?>
                            </small>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2 mt-auto">
                        <a href="car-detail.php?id=<?= $car['id'] ?>"
                           class="btn btn-warning btn-sm fw-bold flex-grow-1">
                            <i class="fas fa-phone me-1"></i>Contact Seller
                        </a>
                        <button class="btn btn-outline-secondary btn-sm"
                                title="Add to Wishlist"
                                onclick="addToWishlist(<?= $car['id'] ?>, 'car')">
                            <i class="fas fa-heart"></i>
                        </button>
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
                <li class="page-item">
                    <a class="page-link" href="<?= updatePageUrl($page - 1) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end   = min($total_pages, $page + 2);
                if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= updatePageUrl(1) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= updatePageUrl($i) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= updatePageUrl($total_pages) ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= updatePageUrl($page + 1) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php endif; // end empty check ?>
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
// Update a single query param while keeping others
function updateParam(key, value) {
    var url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.delete('page'); // reset to page 1
    return url.toString();
}

// Wishlist
function addToWishlist(id, type) {
    <?php if (isLoggedIn()): ?>
    if (!id || id <= 0) return;
    var fd = new FormData();
    fd.append('product_id', id);
    fetch('backend/api/wishlist.php?action=add', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            showNotif(d.status === 'success' ? '❤️ Added to wishlist!' : (d.message || 'Error'), d.status === 'success' ? 'success' : 'error');
        });
    <?php else: ?>
    window.location.href = 'login.php';
    <?php endif; ?>
}

function showNotif(msg, type) {
    var n = document.createElement('div');
    n.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;color:#fff;font-weight:600;z-index:9999;box-shadow:0 6px 20px rgba(0,0,0,.2);background:' + (type==='success'?'#16a34a':'#dc3545');
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}
</script>
</body>
</html>
<?php
// Helper: build pagination URL preserving all filters
function updatePageUrl($p) {
    $params = $_GET;
    $params['page'] = $p;
    return 'all-cars.php?' . http_build_query($params);
}
?>
