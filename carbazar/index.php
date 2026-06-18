<?php
require_once 'backend/config/db.php';

// ---- Fetch latest 6 cars from database ----
$db_cars = [];
$cars_result = $conn->query("
    SELECT c.*, u.name as seller_name, u.phone as seller_phone
    FROM cars c
    JOIN users u ON c.seller_id = u.id
    WHERE c.is_active = 1 AND c.is_sold = 0
    ORDER BY c.created_at DESC
    LIMIT 6
");
if ($cars_result) {
    while ($row = $cars_result->fetch_assoc()) {
        $db_cars[] = $row;
    }
}

// ---- Condition badge color helper ----
function conditionBadge($cond) {
    $map = [
        'excellent'    => ['bg-success',  'Excellent'],
        'good'         => ['bg-primary',  'Good'],
        'fair'         => ['bg-warning text-dark', 'Fair'],
        'needs_repair' => ['bg-danger',   'Needs Repair'],
    ];
    return $map[$cond] ?? ['bg-secondary', ucfirst($cond)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBazar - Buy & Sell Used Cars & Spare Parts in Pakistan</title>
    
    <?php 
    require_once 'includes/seo.php';
    generate_seo_tags([
        'title' => 'Buy & Sell Used Cars & Spare Parts in Pakistan',
        'description' => 'Pakistan\'s #1 Online Auto Marketplace. Buy used cars (Honda, Toyota, Suzuki) and genuine spare parts. Best deals in Karachi, Lahore, Islamabad. Free listings!',
        'keywords' => 'used cars pakistan, buy car online, sell car, spare parts, auto parts, honda city, toyota corolla, suzuki alto, car marketplace pakistan, olx cars, pakwheels alternative',
        'url' => 'http://localhost/carbazar/',
        'type' => 'website'
    ]);
    generate_organization_schema();
    ?>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --dark-navy: #1a1a2e; }
        body { font-family: 'Segoe UI', sans-serif; }
        .hero-section { min-height: 88vh; display: flex; align-items: center; }
        .search-section { position: sticky; top: 56px; z-index: 999; }
        .search-tab-btn { border: 2px solid #dee2e6; background: #fff; color: #333; padding: 10px 28px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all .25s; }
        .search-tab-btn.active { background: var(--gold); border-color: var(--gold); color: #1a1a2e; }
        .car-card { border-radius: 14px !important; overflow: hidden; transition: transform .3s, box-shadow .3s; }
        .car-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.15) !important; }
        .car-card .car-img { transition: transform .4s; }
        .car-card:hover .car-img { transform: scale(1.06); }
        .product-card { border-radius: 12px !important; overflow: hidden; transition: transform .3s, box-shadow .3s; border: none !important; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,.12) !important; }
        .product-card .card-img-top { height: 180px; object-fit: cover; }
        .category-card { background: #fff; border-radius: 14px; transition: transform .3s, box-shadow .3s; cursor: pointer; }
        .category-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,.1); }
        .feature-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.06); transition: transform .3s; }
        .feature-card:hover { transform: translateY(-4px); }
        .testimonial-card { background: #fff; border-radius: 14px; border-left: 4px solid var(--gold); }
        .notification { position: fixed; bottom: 24px; right: 24px; padding: 14px 22px; border-radius: 10px; color: #fff; font-weight: 600; z-index: 9999; animation: slideIn .3s ease; box-shadow: 0 6px 20px rgba(0,0,0,.2); }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .sell-card { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); border-radius: 16px; transition: background .3s; }
        .sell-card:hover { background: rgba(255,255,255,.14); }
        footer a:hover { color: var(--gold) !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
    </style>
</head>
<body>

<?php
// Navbar counts
$nc_count = 0; $nw_count = 0;
if (isLoggedIn()) {
    $nc = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id=?");
    $nc->bind_param("i", $_SESSION['user_id']); $nc->execute();
    $nc_count = (int)($nc->get_result()->fetch_assoc()['c'] ?? 0); $nc->close();
    $nw = $conn->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id=?");
    $nw->bind_param("i", $_SESSION['user_id']); $nw->execute();
    $nw_count = (int)($nw->get_result()->fetch_assoc()['c'] ?? 0); $nw->close();
}
?>
<!-- ===== PROFESSIONAL NAVBAR ===== -->
<style>
/* ---- Navbar Base ---- */
.cb-navbar {
    background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 60%, #16213e 100%);
    border-bottom: 2px solid rgba(240,192,64,.25);
    padding: 0;
    position: sticky;
    top: 0;
    z-index: 1050;
    box-shadow: 0 4px 24px rgba(0,0,0,.45);
    backdrop-filter: blur(10px);
}

/* ---- Brand ---- */
.cb-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    padding: 14px 0;
}
.cb-brand-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #f0c040, #e0a800);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #1a1a2e;
    box-shadow: 0 4px 12px rgba(240,192,64,.4);
    flex-shrink: 0;
    transition: transform .2s;
}
.cb-brand:hover .cb-brand-icon { transform: rotate(-8deg) scale(1.08); }
.cb-brand-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    line-height: 1;
}
.cb-brand-name {
    font-size: 1.25rem; font-weight: 800;
    color: #fff; letter-spacing: -.3px;
    display: block;
    line-height: 1.1;
}
.cb-brand-tagline {
    font-size: .58rem; color: rgba(240,192,64,.8);
    font-weight: 600; letter-spacing: 1.8px;
    text-transform: uppercase;
    display: block;
    line-height: 1;
    white-space: nowrap;
}

/* ---- Nav Links ---- */
.cb-nav-link {
    color: rgba(255,255,255,.75) !important;
    font-weight: 600;
    font-size: .88rem;
    padding: 8px 14px !important;
    border-radius: 8px;
    transition: all .2s;
    display: flex; align-items: center; gap: 6px;
    text-decoration: none;
    position: relative;
    letter-spacing: .2px;
}
.cb-nav-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,.08);
}
.cb-nav-link.active-link {
    color: #f0c040 !important;
    background: rgba(240,192,64,.12);
}
.cb-nav-link::after {
    content: '';
    position: absolute;
    bottom: 4px; left: 50%; right: 50%;
    height: 2px;
    background: #f0c040;
    border-radius: 2px;
    transition: left .2s, right .2s;
}
.cb-nav-link:hover::after,
.cb-nav-link.active-link::after {
    left: 14px; right: 14px;
}

/* Sell link — special highlight */
.cb-nav-sell {
    color: #f0c040 !important;
    border: 1.5px solid rgba(240,192,64,.4);
    background: rgba(240,192,64,.08);
}
.cb-nav-sell:hover {
    background: rgba(240,192,64,.18) !important;
    border-color: #f0c040;
}
.cb-nav-sell::after { display: none; }

/* ---- Divider ---- */
.cb-nav-divider {
    width: 1px; height: 28px;
    background: rgba(255,255,255,.12);
    margin: 0 6px;
}

/* ---- Icon Buttons ---- */
.cb-icon-btn {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
    text-decoration: none;
    position: relative;
    transition: all .2s;
    border: 1.5px solid transparent;
    flex-shrink: 0;
}
.cb-icon-btn:hover { transform: translateY(-2px); }

.cb-icon-wishlist {
    color: #f87171;
    background: rgba(248,113,113,.1);
    border-color: rgba(248,113,113,.25);
}
.cb-icon-wishlist:hover {
    background: rgba(248,113,113,.2);
    border-color: #f87171;
    color: #f87171;
}

.cb-icon-cart {
    color: #f0c040;
    background: rgba(240,192,64,.1);
    border-color: rgba(240,192,64,.25);
}
.cb-icon-cart:hover {
    background: rgba(240,192,64,.2);
    border-color: #f0c040;
    color: #f0c040;
}

.cb-icon-orders {
    color: rgba(255,255,255,.8);
    background: rgba(255,255,255,.06);
    border-color: rgba(255,255,255,.15);
}
.cb-icon-orders:hover {
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.3);
    color: #fff;
}

/* Badge on icon buttons */
.cb-badge {
    position: absolute;
    top: -5px; right: -5px;
    min-width: 18px; height: 18px;
    border-radius: 9px;
    font-size: .62rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px;
    border: 2px solid #1a1a2e;
    line-height: 1;
}
.cb-badge-red    { background: #ef4444; color: #fff; }
.cb-badge-yellow { background: #f0c040; color: #1a1a2e; }

/* ---- User Dropdown ---- */
.cb-user-btn {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.07);
    border: 1.5px solid rgba(255,255,255,.15);
    border-radius: 10px;
    padding: 6px 12px;
    color: #fff;
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
    font-size: .85rem;
}
.cb-user-btn:hover {
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.3);
    color: #fff;
}
.cb-user-avatar {
    width: 28px; height: 28px;
    background: linear-gradient(135deg, #f0c040, #e0a800);
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 800; color: #1a1a2e;
    flex-shrink: 0;
}
.cb-user-name {
    font-weight: 600; max-width: 90px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* ---- Auth Buttons ---- */
.cb-btn-signin {
    padding: 7px 16px;
    border-radius: 9px;
    border: 1.5px solid rgba(255,255,255,.3);
    background: transparent;
    color: rgba(255,255,255,.85);
    font-size: .85rem; font-weight: 600;
    text-decoration: none;
    transition: all .2s;
}
.cb-btn-signin:hover {
    background: rgba(255,255,255,.1);
    border-color: rgba(255,255,255,.5);
    color: #fff;
}
.cb-btn-signup {
    padding: 7px 18px;
    border-radius: 9px;
    background: linear-gradient(135deg, #f0c040, #e0a800);
    border: none;
    color: #1a1a2e;
    font-size: .85rem; font-weight: 700;
    text-decoration: none;
    transition: all .2s;
    box-shadow: 0 4px 12px rgba(240,192,64,.35);
}
.cb-btn-signup:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(240,192,64,.5);
    color: #1a1a2e;
}

/* ---- Seller Dashboard Btn ---- */
.cb-btn-dashboard {
    padding: 7px 14px;
    border-radius: 9px;
    background: rgba(240,192,64,.15);
    border: 1.5px solid rgba(240,192,64,.4);
    color: #f0c040;
    font-size: .82rem; font-weight: 700;
    text-decoration: none;
    transition: all .2s;
    display: flex; align-items: center; gap: 6px;
}
.cb-btn-dashboard:hover {
    background: rgba(240,192,64,.25);
    border-color: #f0c040;
    color: #f0c040;
}

/* ---- Logout Btn ---- */
.cb-btn-logout {
    padding: 7px 14px;
    border-radius: 9px;
    background: rgba(239,68,68,.1);
    border: 1.5px solid rgba(239,68,68,.25);
    color: #f87171;
    font-size: .82rem; font-weight: 600;
    text-decoration: none;
    transition: all .2s;
    display: flex; align-items: center; gap: 6px;
}
.cb-btn-logout:hover {
    background: rgba(239,68,68,.2);
    border-color: #ef4444;
    color: #fca5a5;
}

/* ---- Toggler ---- */
.cb-toggler {
    border: 1.5px solid rgba(255,255,255,.2);
    border-radius: 8px;
    padding: 6px 10px;
    background: rgba(255,255,255,.05);
    color: rgba(255,255,255,.8);
    transition: all .2s;
}
.cb-toggler:hover {
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.4);
}

/* ---- Mobile ---- */
@media (max-width: 991px) {
    .cb-navbar .container-fluid { padding: 0 16px; }
    .cb-nav-link { padding: 10px 12px !important; border-radius: 8px; }
    .cb-nav-link::after { display: none; }
    .cb-nav-divider { display: none; }
    .cb-mobile-actions {
        display: flex; flex-wrap: wrap; gap: 8px;
        padding: 12px 0 16px;
        border-top: 1px solid rgba(255,255,255,.1);
        margin-top: 8px;
    }
}
@media (min-width: 992px) {
    .cb-mobile-actions { display: none !important; }
}
</style>

<nav class="cb-navbar">
<div class="container-fluid px-4">
<div class="d-flex align-items-center justify-content-between w-100 py-1">

    <!-- Brand -->
    <a class="cb-brand" href="index.php">
        <div class="cb-brand-icon">
            <i class="fas fa-car"></i>
        </div>
        <div class="cb-brand-text">
            <span class="cb-brand-name">CarBazar</span>
            <span class="cb-brand-tagline">Pakistan's #1 Auto Market</span>
        </div>
    </a>

    <!-- Desktop Nav Links (center) -->
    <ul class="navbar-nav d-none d-lg-flex flex-row gap-1 mb-0">
        <li class="nav-item">
            <a class="cb-nav-link" href="index.php#home">
                <i class="fas fa-home"></i> Home
            </a>
        </li>
        <li class="nav-item">
            <a class="cb-nav-link" href="index.php#cars">
                <i class="fas fa-car"></i> Cars
            </a>
        </li>
        <li class="nav-item">
            <a class="cb-nav-link" href="index.php#products">
                <i class="fas fa-cogs"></i> Spare Parts
            </a>
        </li>
        <li class="nav-item">
            <a class="cb-nav-link" href="index.php#categories">
                <i class="fas fa-th-large"></i> Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="cb-nav-link cb-nav-sell" href="sell.php">
                <i class="fas fa-tag"></i> Sell
            </a>
        </li>
        <li class="nav-item">
            <a class="cb-nav-link" href="index.php#contact">
                <i class="fas fa-envelope"></i> Contact
            </a>
        </li>
    </ul>

    <!-- Right Actions -->
    <div class="d-flex align-items-center gap-2">

        <?php if (isLoggedIn()): ?>

            <!-- Icon Buttons -->
            <div class="d-none d-lg-flex align-items-center gap-2">

                <!-- Wishlist -->
                <a href="wishlist.php" class="cb-icon-btn cb-icon-wishlist" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <?php if ($nw_count > 0): ?>
                    <span id="wl-nav-badge" class="cb-badge cb-badge-yellow"><?= $nw_count ?></span>
                    <?php else: ?>
                    <span id="wl-nav-badge" class="cb-badge cb-badge-yellow" style="display:none;">0</span>
                    <?php endif; ?>
                </a>

                <!-- Cart -->
                <a href="cart.php" class="cb-icon-btn cb-icon-cart" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($nc_count > 0): ?>
                    <span id="cart-nav-badge" class="cb-badge cb-badge-red"><?= $nc_count ?></span>
                    <?php else: ?>
                    <span id="cart-nav-badge" class="cb-badge cb-badge-red" style="display:none;">0</span>
                    <?php endif; ?>
                </a>

                <!-- Orders -->
                <a href="orders.php" class="cb-icon-btn cb-icon-orders" title="My Orders">
                    <i class="fas fa-box"></i>
                </a>

                <div class="cb-nav-divider"></div>

                <!-- Seller Dashboard -->
                <?php if (isSeller()): ?>
                <a href="backend/seller/dashboard.php" class="cb-btn-dashboard">
                    <i class="fas fa-store"></i> Dashboard
                </a>
                <?php endif; ?>

                <!-- User Info -->
                <div class="cb-user-btn">
                    <div class="cb-user-avatar">
                        <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                    </div>
                    <span class="cb-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>

                <!-- Logout -->
                <a href="backend/auth/logout.php" class="cb-btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

        <?php else: ?>

            <!-- Auth Buttons -->
            <div class="d-none d-lg-flex align-items-center gap-2">
                <a href="login.php" class="cb-btn-signin">
                    <i class="fas fa-sign-in-alt me-1"></i>Sign In
                </a>
                <a href="register.php" class="cb-btn-signup">
                    <i class="fas fa-user-plus me-1"></i>Sign Up
                </a>
            </div>

        <?php endif; ?>

        <!-- Mobile Toggler -->
        <button class="cb-toggler d-lg-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#cbMobileNav"
                aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<!-- Mobile Menu -->
<div class="collapse d-lg-none" id="cbMobileNav">
    <div style="border-top:1px solid rgba(255,255,255,.1);padding:12px 0;">
        <ul class="navbar-nav gap-1 mb-0">
            <li><a class="cb-nav-link" href="index.php#home"><i class="fas fa-home"></i> Home</a></li>
            <li><a class="cb-nav-link" href="index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
            <li><a class="cb-nav-link" href="index.php#products"><i class="fas fa-cogs"></i> Spare Parts</a></li>
            <li><a class="cb-nav-link" href="index.php#categories"><i class="fas fa-th-large"></i> Categories</a></li>
            <li><a class="cb-nav-link cb-nav-sell" href="sell.php"><i class="fas fa-tag"></i> Sell</a></li>
            <li><a class="cb-nav-link" href="index.php#contact"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>

        <!-- Mobile Actions -->
        <div class="cb-mobile-actions">
            <?php if (isLoggedIn()): ?>
                <a href="wishlist.php" class="cb-icon-btn cb-icon-wishlist" title="Wishlist">
                    <i class="fas fa-heart"></i>
                    <?php if ($nw_count > 0): ?>
                    <span class="cb-badge cb-badge-yellow"><?= $nw_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="cart.php" class="cb-icon-btn cb-icon-cart" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($nc_count > 0): ?>
                    <span class="cb-badge cb-badge-red"><?= $nc_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="orders.php" class="cb-icon-btn cb-icon-orders" title="Orders">
                    <i class="fas fa-box"></i>
                </a>
                <?php if (isSeller()): ?>
                <a href="backend/seller/dashboard.php" class="cb-btn-dashboard">
                    <i class="fas fa-store"></i> Dashboard
                </a>
                <?php endif; ?>
                <a href="backend/auth/logout.php" class="cb-btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="cb-btn-signin"><i class="fas fa-sign-in-alt me-1"></i>Sign In</a>
                <a href="register.php" class="cb-btn-signup"><i class="fas fa-user-plus me-1"></i>Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>
</nav>

<!-- HERO SECTION -->
<section id="home" class="hero-section text-white" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 text-center text-lg-start">
                <span class="badge bg-warning text-dark mb-3 px-3 py-2" style="font-size:.85rem;border-radius:20px;">
                    &#x1F1F5;&#x1F1F0; Pakistan's #1 Auto Marketplace
                </span>
                <h1 class="display-4 fw-bold mb-3 lh-sm" style="color:#ffffff !important; text-shadow: 0 2px 12px rgba(0,0,0,0.4);">
                    Buy &amp; Sell <span style="color:#f0c040; text-shadow: 0 2px 12px rgba(240,192,64,0.4);">Cars</span><br>
                    <span style="color:#ffffff;">& Spare Parts</span>
                </h1>
                <p class="lead mb-4" style="opacity:.8">Trusted marketplace for used cars and genuine spare parts across Pakistan. Best prices, verified sellers.</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start mb-4">
                    <a href="#cars" class="btn btn-warning btn-lg fw-bold px-4">
                        <i class="fas fa-car me-2"></i>Browse Cars
                    </a>
                    <a href="#products" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-cogs me-2"></i>Spare Parts
                    </a>
                </div>
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-3" style="color:#f0c040">500+</div>
                        <small style="opacity:.7">Cars Listed</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3" style="color:#f0c040">10K+</div>
                        <small style="opacity:.7">Spare Parts</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3" style="color:#f0c040">2K+</div>
                        <small style="opacity:.7">Happy Buyers</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=700&q=80"
                     alt="Cars For Sale Pakistan"
                     class="img-fluid"
                     style="border-radius:20px;max-height:400px;width:100%;object-fit:cover;box-shadow:0 24px 64px rgba(0,0,0,.5)">
            </div>
        </div>
    </div>
</section>

<!-- SEARCH SECTION -->
<section class="search-section py-4 bg-white shadow-sm" style="border-bottom:3px solid #f0c040;">
    <div class="container">
        <div class="d-flex justify-content-center gap-3 mb-3">
            <button class="search-tab-btn active" id="tabCars" onclick="switchSearchTab('cars')">
                &#x1F697; Cars
            </button>
            <button class="search-tab-btn" id="tabParts" onclick="switchSearchTab('parts')">
                &#x1F527; Spare Parts
            </button>
        </div>

        <!-- Cars Search -->
        <div id="carsSearch">
            <div class="row g-2">
                <div class="col-md-3">
                    <select class="form-select form-select-lg" id="searchBrand">
                        <option value="">All Brands</option>
                        <option value="Toyota">Toyota</option>
                        <option value="Honda">Honda</option>
                        <option value="Suzuki">Suzuki</option>
                        <option value="Hyundai">Hyundai</option>
                        <option value="Kia">Kia</option>
                        <option value="BMW">BMW</option>
                        <option value="Mercedes">Mercedes</option>
                        <option value="Audi">Audi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-lg" id="searchCity">
                        <option value="">Any City</option>
                        <option value="Karachi">Karachi</option>
                        <option value="Lahore">Lahore</option>
                        <option value="Islamabad">Islamabad</option>
                        <option value="Rawalpindi">Rawalpindi</option>
                        <option value="Peshawar">Peshawar</option>
                        <option value="Quetta">Quetta</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-lg" id="searchBudget">
                        <option value="">Any Budget</option>
                        <option value="1000000">Under 10 Lakh</option>
                        <option value="2000000">Under 20 Lakh</option>
                        <option value="3000000">Under 30 Lakh</option>
                        <option value="5000000">Under 50 Lakh</option>
                        <option value="10000000">Under 1 Crore</option>
                        <option value="999999999">1 Crore+</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-warning btn-lg w-100 fw-bold" onclick="filterCars()">
                        <i class="fas fa-search me-1"></i>Search Cars
                    </button>
                </div>
            </div>
        </div>

        <!-- Parts Search -->
        <div id="partsSearch" style="display:none;">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-lg" id="searchPartName" placeholder="Search part name (e.g. Air Filter, Battery)">
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-lg" id="searchPartCat">
                        <option value="">All Categories</option>
                        <option value="Engine Parts">Engine Parts</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Brakes">Brakes</option>
                        <option value="Cooling System">Cooling System</option>
                        <option value="Suspension">Suspension</option>
                        <option value="Body Parts">Body Parts</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-lg w-100 fw-bold" onclick="filterParts()">
                        <i class="fas fa-search me-1"></i>Search Parts
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CARS FOR SALE SECTION -->
<section id="cars" class="py-5" style="background:#f8f9fa;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">USED CARS</span>
                <h2 class="fw-bold mb-1">&#x1F697; Used Cars For Sale</h2>
                <p class="text-muted mb-0">Verified used cars from trusted sellers across Pakistan</p>
            </div>
            <a href="all-cars.php" class="btn btn-outline-warning fw-bold px-4">View All Cars</a>
        </div>

        <div class="row g-4" id="carsGrid">

<?php
// ============================================================
// Static fallback cars (jab DB mein koi car na ho)
// ============================================================
$static_cars = [
    [
        'title'          => 'Toyota Corolla 2020',
        'brand'          => 'Toyota',
        'model'          => 'Corolla',
        'year'           => '2020',
        'price'          => 2800000,
        'city'           => 'Karachi',
        'mileage'        => 45000,
        'fuel_type'      => 'petrol',
        'transmission'   => 'automatic',
        'condition_type' => 'excellent',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
    [
        'title'          => 'Honda Civic 2019',
        'brand'          => 'Honda',
        'model'          => 'Civic',
        'year'           => '2019',
        'price'          => 3200000,
        'city'           => 'Lahore',
        'mileage'        => 38000,
        'fuel_type'      => 'petrol',
        'transmission'   => 'automatic',
        'condition_type' => 'excellent',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1606016159991-dfe4f2746ad5?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
    [
        'title'          => 'Suzuki Alto 2022',
        'brand'          => 'Suzuki',
        'model'          => 'Alto',
        'year'           => '2022',
        'price'          => 1650000,
        'city'           => 'Islamabad',
        'mileage'        => 12000,
        'fuel_type'      => 'petrol',
        'transmission'   => 'manual',
        'condition_type' => 'excellent',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
    [
        'title'          => 'Toyota Prado 2018',
        'brand'          => 'Toyota',
        'model'          => 'Prado',
        'year'           => '2018',
        'price'          => 8500000,
        'city'           => 'Karachi',
        'mileage'        => 65000,
        'fuel_type'      => 'diesel',
        'transmission'   => 'automatic',
        'condition_type' => 'good',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
    [
        'title'          => 'Honda City 2021',
        'brand'          => 'Honda',
        'model'          => 'City',
        'year'           => '2021',
        'price'          => 2950000,
        'city'           => 'Lahore',
        'mileage'        => 28000,
        'fuel_type'      => 'petrol',
        'transmission'   => 'automatic',
        'condition_type' => 'excellent',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
    [
        'title'          => 'Suzuki Cultus 2020',
        'brand'          => 'Suzuki',
        'model'          => 'Cultus',
        'year'           => '2020',
        'price'          => 1850000,
        'city'           => 'Rawalpindi',
        'mileage'        => 52000,
        'fuel_type'      => 'petrol',
        'transmission'   => 'manual',
        'condition_type' => 'good',
        'image'          => '',
        'static_img'     => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=500&q=80',
        'seller_phone'   => '',
        'id'             => 0,
    ],
];

// DB cars first, fill remaining slots with static data (up to 6 total)
$display_cars = $db_cars;
$db_count     = count($db_cars);
if ($db_count < 6) {
    $needed = 6 - $db_count;
    $display_cars = array_merge($db_cars, array_slice($static_cars, 0, $needed));
}

foreach ($display_cars as $car):
    $is_db_car   = !empty($car['id']);
    $badge       = conditionBadge($car['condition_type'] ?? 'good');
    $fuel_label  = ucfirst($car['fuel_type'] ?? 'Petrol');
    $trans_label = ucfirst($car['transmission'] ?? 'Manual');
    $mileage_fmt = number_format($car['mileage'] ?? 0);
    $price_fmt   = 'PKR ' . number_format($car['price']);
    $title_esc   = htmlspecialchars($car['title']);
    $brand_esc   = htmlspecialchars($car['brand']);
    $city_esc    = htmlspecialchars($car['city'] ?? '');
    $year_esc    = htmlspecialchars($car['year'] ?? '');

    // Image: DB car → uploads folder, static → unsplash URL
    if ($is_db_car && !empty($car['image']) && $car['image'] !== 'default.jpg') {
        $img_src = 'uploads/' . htmlspecialchars($car['image']);
    } elseif (!empty($car['static_img'])) {
        $img_src = $car['static_img'];
    } else {
        $img_src = 'https://via.placeholder.com/500x210/1a1a2e/f0c040?text=' . urlencode($title_esc);
    }
    $img_fallback = 'https://via.placeholder.com/500x210/1a1a2e/f0c040?text=' . urlencode($title_esc);
?>
            <div class="col-lg-4 col-md-6 car-card-item"
                 data-brand="<?= htmlspecialchars($car['brand']) ?>"
                 data-city="<?= $city_esc ?>"
                 data-price="<?= (int)$car['price'] ?>">
                <div class="card car-card h-100 shadow-sm border-0">
                    <div style="position:relative;overflow:hidden;height:210px;">
                        <img src="<?= $img_src ?>"
                             alt="<?= $title_esc ?>" class="car-img"
                             style="width:100%;height:210px;object-fit:cover;"
                             onerror="this.src='<?= $img_fallback ?>'">
                        <span class="badge <?= $badge[0] ?>" style="position:absolute;top:12px;left:12px;font-size:.78rem;padding:6px 10px;">
                            <?= $badge[1] ?>
                        </span>
                        <span class="badge bg-dark" style="position:absolute;top:12px;right:12px;font-size:.78rem;padding:6px 10px;">
                            <?= $year_esc ?>
                        </span>
                        <?php if ($is_db_car): ?>
                        <span class="badge bg-warning text-dark" style="position:absolute;bottom:10px;left:12px;font-size:.7rem;padding:4px 8px;">
                            <i class="fas fa-database me-1"></i>Live
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3">
                        <h5 class="fw-bold mb-1" style="font-size:1rem;"><?= $title_esc ?></h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold fs-5" style="color:#e67e22;"><?= $price_fmt ?></span>
                            <span class="text-muted small"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= $city_esc ?></span>
                        </div>
                        <div class="row g-1 mb-3">
                            <div class="col-6"><small class="text-muted"><i class="fas fa-tachometer-alt text-primary me-1"></i><?= $mileage_fmt ?> km</small></div>
                            <div class="col-6"><small class="text-muted"><i class="fas fa-gas-pump text-success me-1"></i><?= $fuel_label ?></small></div>
                            <div class="col-6"><small class="text-muted"><i class="fas fa-cog text-warning me-1"></i><?= $trans_label ?></small></div>
                            <div class="col-6"><small class="text-muted"><i class="fas fa-car text-info me-1"></i><?= $brand_esc ?></small></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-warning btn-sm fw-bold flex-grow-1"
                                    onclick="contactSeller(<?= $car['id'] ?>, '<?= $title_esc ?>')">
                                <i class="fas fa-phone me-1"></i>Contact Seller
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" title="Add to Wishlist"
                                    onclick="addToWishlist(<?= $car['id'] ?>, 'car')">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
<?php endforeach; ?>

        </div><!-- end #carsGrid -->

        <!-- No results message -->
        <div id="carsNotFound" class="text-center py-5" style="display:none;">
            <i class="fas fa-car fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No cars found matching your search.</h5>
            <button class="btn btn-outline-warning mt-2" onclick="resetCarSearch()">Show All Cars</button>
        </div>

        <div class="text-center mt-4">
            <a href="all-cars.php" class="btn btn-warning btn-lg fw-bold px-5">
                <i class="fas fa-car me-2"></i>View All Cars
                <?php
                $total_cars = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_active=1 AND is_sold=0");
                $tc = $total_cars ? $total_cars->fetch_assoc()['c'] : 0;
                if ($tc > 0) echo '<span class="badge bg-dark ms-2">' . $tc . '</span>';
                ?>
            </a>
        </div>
    </div>
</section>

<!-- SPARE PARTS SECTION -->
<style>
.parts-section{background:#f1f5f9 !important}
.parts-header{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:14px;margin-bottom:24px}
.parts-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#0d6efd;margin-bottom:5px}
.parts-heading{font-size:1.6rem;font-weight:800;color:#0f172a !important;margin-bottom:3px}
.parts-subtext{font-size:.82rem;color:#64748b;margin:0}
.parts-filters{display:flex;gap:7px;flex-wrap:wrap}
.pf-btn{padding:6px 16px;border-radius:20px;border:1.5px solid #cbd5e1;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s}
.pf-btn:hover{border-color:#0d6efd;color:#0d6efd}
.pf-btn.active{background:#0d6efd;border-color:#0d6efd;color:#fff}
.pcard{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.07);transition:transform .25s,box-shadow .25s;height:100%;display:flex;flex-direction:column}
.pcard:hover{transform:translateY(-5px);box-shadow:0 14px 36px rgba(0,0,0,.13)}
.pcard-img-wrap{position:relative;overflow:hidden;height:185px !important;background:#e2e8f0;flex-shrink:0}
.pcard-img{width:100% !important;height:185px !important;object-fit:cover !important;object-position:center top !important;transition:transform .35s;display:block}
.pcard:hover .pcard-img{transform:scale(1.08)}
.pcard-overlay{position:absolute;inset:0;background:rgba(15,23,42,.72);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .22s}
.pcard:hover .pcard-overlay{opacity:1}
.pcard-quick{background:#f59e0b;color:#0f172a;border:none;border-radius:8px;padding:8px 18px;font-size:.78rem;font-weight:700;cursor:pointer}
.pcard-quick:hover{background:#fbbf24}
.pcard-cat{position:absolute;bottom:8px;left:8px;font-size:.63rem;font-weight:700;padding:3px 9px;border-radius:16px}
.pcard-cat.engine{background:rgba(219,234,254,.95);color:#1d4ed8}
.pcard-cat.electrical{background:rgba(254,249,195,.95);color:#a16207}
.pcard-cat.brakes{background:rgba(254,226,226,.95);color:#b91c1c}
.pcard-cat.cooling{background:rgba(220,252,231,.95);color:#15803d}
.pcard-hot{position:absolute;top:8px;right:8px;background:#f59e0b;color:#fff;font-size:.6rem;font-weight:700;padding:3px 8px;border-radius:16px}
.pcard-body{padding:11px 13px 13px;display:flex;flex-direction:column;gap:7px;flex-grow:1}
.pcard-top{display:flex;justify-content:space-between;align-items:flex-start;gap:6px}
.pcard-name{font-size:.86rem;font-weight:700;color:#0f172a !important;margin:0 0 2px;line-height:1.25}
.pcard-desc{font-size:.7rem;color:#94a3b8;margin:0;line-height:1.35}
.pcard-rating{background:#fef9c3;color:#a16207;font-size:.68rem;font-weight:700;padding:3px 7px;border-radius:6px;white-space:nowrap;flex-shrink:0}
.pcard-rating i{color:#f59e0b;margin-right:2px;font-size:.65rem}
.pcard-bottom{display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid #f1f5f9;margin-top:auto}
.pcard-price{font-size:.98rem;font-weight:800;color:#0f172a;display:block;line-height:1}
.pcard-sold{font-size:.66rem;color:#94a3b8;display:block;margin-top:2px}
.pcard-btn{width:33px;height:33px;background:#0d6efd;color:#fff;border:none;border-radius:8px;font-size:.82rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .18s,transform .18s;flex-shrink:0}
.pcard-btn:hover{background:#0a58ca;transform:scale(1.1)}
@media(max-width:767px){.parts-header{flex-direction:column;align-items:flex-start}.parts-heading{font-size:1.3rem}.pcard-img-wrap,.pcard-img{height:150px !important}}
</style>
<section id="products" class="parts-section py-5">
    <div class="container">

        <!-- Header -->
        <div class="parts-header">
            <div>
                <div class="parts-label">🔧 SPARE PARTS</div>
                <h2 class="parts-heading">Featured Spare Parts</h2>
                <p class="parts-subtext">Genuine OEM &amp; aftermarket parts — fast delivery across Pakistan</p>
            </div>
            <div class="parts-filters">
                <button class="pf-btn active" onclick="filterByCategory(this,'')">All</button>
                <button class="pf-btn" onclick="filterByCategory(this,'engine parts')">Engine</button>
                <button class="pf-btn" onclick="filterByCategory(this,'electrical')">Electrical</button>
                <button class="pf-btn" onclick="filterByCategory(this,'brakes')">Brakes</button>
                <button class="pf-btn" onclick="filterByCategory(this,'cooling system')">Cooling</button>
            </div>
        </div>

        <!-- Grid — 3 per row, all equal -->
        <div class="row g-3" id="partsGrid">

<?php
// ============================================================
// Fetch latest 6 active products from DB
// ============================================================
$db_products = [];
$prod_result = $conn->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 6
");
if ($prod_result) {
    while ($row = $prod_result->fetch_assoc()) {
        $db_products[] = $row;
    }
}

// ============================================================
// Static fallback parts (shown when DB has no products)
// ============================================================
$static_parts = [
    ['id'=>0,'name'=>'Premium Engine Oil','description'=>'5L · All Grades · Fully Synthetic','price'=>899,'discount_price'=>null,'rating'=>4.8,'reviews_count'=>1200,'cat_name'=>'Engine Parts','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&q=80'],
    ['id'=>0,'name'=>'Car Battery 12V','description'=>'50Ah · Maintenance Free · 2yr Warranty','price'=>4499,'discount_price'=>null,'rating'=>4.6,'reviews_count'=>890,'cat_name'=>'Electrical','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1609592806596-b8d4a4b9d5e8?w=500&q=80'],
    ['id'=>0,'name'=>'Air Filter','description'=>'Premium Quality · Universal Fit · High Flow','price'=>299,'discount_price'=>null,'rating'=>4.7,'reviews_count'=>2100,'cat_name'=>'Engine Parts','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=500&q=80'],
    ['id'=>0,'name'=>'Spark Plugs Set','description'=>'Pack of 4 · Iridium Tip · Long Life','price'=>599,'discount_price'=>null,'rating'=>4.5,'reviews_count'=>760,'cat_name'=>'Engine Parts','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1580274455191-1c62238fa333?w=500&q=80'],
    ['id'=>0,'name'=>'Brake Pads','description'=>'Front Set · Ceramic Grade · Low Dust','price'=>1299,'discount_price'=>null,'rating'=>4.9,'reviews_count'=>3400,'cat_name'=>'Brakes','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=500&q=80'],
    ['id'=>0,'name'=>'Radiator','description'=>'Aluminum · High Performance · All Cars','price'=>3999,'discount_price'=>null,'rating'=>4.7,'reviews_count'=>540,'cat_name'=>'Cooling System','brand'=>'','image'=>'','static_img'=>'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=500&q=80'],
];

// DB products first, fill remaining with static (total 6)
$display_parts = $db_products;
$db_prod_count = count($db_products);
if ($db_prod_count < 6) {
    $display_parts = array_merge($db_products, array_slice($static_parts, 0, 6 - $db_prod_count));
}

// Category → CSS class map
$cat_css = [
    'engine parts'  => 'engine',
    'electrical'    => 'electrical',
    'brakes'        => 'brakes',
    'cooling system'=> 'cooling',
    'suspension'    => 'engine',
    'body parts'    => 'brakes',
    'transmission'  => 'electrical',
    'exhaust'       => 'cooling',
];

foreach ($display_parts as $part):
    $is_db      = !empty($part['id']);
    $name_esc   = htmlspecialchars($part['name']);
    $cat_esc    = htmlspecialchars($part['cat_name'] ?? 'Parts');
    $cat_key    = strtolower($part['cat_name'] ?? '');
    $cat_class  = $cat_css[$cat_key] ?? 'engine';
    $desc_esc   = htmlspecialchars($part['description'] ?? '');
    $rating     = number_format((float)($part['rating'] ?? 4.5), 1);
    $sold_count = $part['reviews_count'] ?? 0;
    $sold_fmt   = $sold_count >= 1000 ? round($sold_count/1000, 1).'k' : $sold_count;

    // Price display
    $price      = (float)$part['price'];
    $disc_price = !empty($part['discount_price']) ? (float)$part['discount_price'] : null;
    $show_price = $disc_price ?? $price;
    $price_fmt  = 'PKR ' . number_format($show_price, 0);

    // Image
    if ($is_db && !empty($part['image']) && $part['image'] !== 'default.jpg') {
        $img_src = 'uploads/' . htmlspecialchars($part['image']);
    } elseif (!empty($part['static_img'])) {
        $img_src = $part['static_img'];
    } else {
        $img_src = 'https://via.placeholder.com/500x155/e2e8f0/475569?text=' . urlencode($name_esc);
    }
    $img_fallback = 'https://via.placeholder.com/500x155/e2e8f0/475569?text=' . urlencode($name_esc);
?>
            <div class="col-lg-4 col-md-6 part-card-item"
                 data-name="<?= strtolower($name_esc) ?>"
                 data-category="<?= strtolower($cat_esc) ?>">
                <div class="pcard">
                    <div class="pcard-img-wrap">
                        <img src="<?= $img_src ?>" class="pcard-img" alt="<?= $name_esc ?>"
                             onerror="this.src='<?= $img_fallback ?>'">
                        <div class="pcard-overlay">
                            <button class="pcard-quick" onclick="addToCart('<?= addslashes($name_esc) ?>',<?= $show_price ?>,<?= $part['id'] ?>)">
                                <i class="fas fa-cart-plus me-1"></i>Quick Add
                            </button>
                        </div>
                        <span class="pcard-cat <?= $cat_class ?>"><?= $cat_esc ?></span>
                        <?php if ($is_db): ?>
                        <span class="pcard-hot" style="background:#16a34a">✓ Live</span>
                        <?php elseif ($rating >= 4.8): ?>
                        <span class="pcard-hot">🔥 Hot</span>
                        <?php elseif ($rating >= 4.9): ?>
                        <span class="pcard-hot" style="background:#ef4444">⭐ Top</span>
                        <?php endif; ?>
                    </div>
                    <div class="pcard-body">
                        <div class="pcard-top">
                            <div>
                                <h5 class="pcard-name"><?= $name_esc ?></h5>
                                <p class="pcard-desc"><?= $desc_esc ?></p>
                            </div>
                            <div class="pcard-rating"><i class="fas fa-star"></i> <?= $rating ?></div>
                        </div>
                        <div class="pcard-bottom">
                            <div>
                                <?php if ($disc_price && $disc_price < $price): ?>
                                    <span class="pcard-price"><?= $price_fmt ?></span>
                                    <span class="pcard-sold" style="text-decoration:line-through;color:#94a3b8">PKR <?= number_format($price,0) ?></span>
                                <?php else: ?>
                                    <span class="pcard-price"><?= $price_fmt ?></span>
                                    <span class="pcard-sold"><?= $sold_fmt ?> sold</span>
                                <?php endif; ?>
                            </div>
                            <button class="pcard-btn" onclick="addToCart('<?= addslashes($name_esc) ?>',<?= $show_price ?>,<?= $part['id'] ?>)">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
<?php endforeach; ?>

        </div><!-- end partsGrid -->

        <div class="text-center mt-4">
            <a href="all-parts.php" class="btn btn-outline-primary fw-bold px-5 py-2">
                View All Spare Parts
                <?php
                $tp = $conn->query("SELECT COUNT(*) as c FROM products WHERE is_active=1");
                $tpc = $tp ? $tp->fetch_assoc()['c'] : 0;
                if ($tpc > 0) echo '<span class="badge bg-primary ms-2">'.$tpc.'</span>';
                ?>
                <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CATEGORIES SECTION -->
<section id="categories" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">BROWSE BY TYPE</span>
            <h2 class="fw-bold">Shop by Category</h2>
            <p class="text-muted">Find exactly what your car needs</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Engine+Parts'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#e8f0fe;">
                        <i class="fas fa-cog fa-2x text-primary"></i>
                    </div>
                    <h4 class="fw-bold">Engine Parts</h4>
                    <p class="text-muted mb-3">Oil, filters, spark plugs, pistons</p>
                    <a href="all-parts.php?catname=Engine+Parts" class="btn btn-sm btn-outline-primary px-4">Browse</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Suspension'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#e8f5e9;">
                        <i class="fas fa-car-crash fa-2x text-success"></i>
                    </div>
                    <h4 class="fw-bold">Suspension</h4>
                    <p class="text-muted mb-3">Shocks, springs, bushings, arms</p>
                    <a href="all-parts.php?catname=Suspension" class="btn btn-sm btn-outline-success px-4">Browse</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Brakes'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#fdecea;">
                        <i class="fas fa-circle-notch fa-2x text-danger"></i>
                    </div>
                    <h4 class="fw-bold">Brakes</h4>
                    <p class="text-muted mb-3">Pads, discs, calipers, drums</p>
                    <a href="all-parts.php?catname=Brakes" class="btn btn-sm btn-outline-danger px-4">Browse</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Electrical'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#fffde7;">
                        <i class="fas fa-bolt fa-2x text-warning"></i>
                    </div>
                    <h4 class="fw-bold">Electrical</h4>
                    <p class="text-muted mb-3">Battery, alternator, starter, sensors</p>
                    <a href="all-parts.php?catname=Electrical" class="btn btn-sm btn-outline-warning px-4">Browse</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Cooling+System'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#e0f7fa;">
                        <i class="fas fa-thermometer-half fa-2x text-info"></i>
                    </div>
                    <h4 class="fw-bold">Cooling System</h4>
                    <p class="text-muted mb-3">Radiator, thermostat, water pump</p>
                    <a href="all-parts.php?catname=Cooling+System" class="btn btn-sm btn-outline-info px-4">Browse</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="category-card text-center p-5 shadow-sm" onclick="location.href='all-parts.php?catname=Body+Parts'" style="cursor:pointer;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;background:#f3e5f5;">
                        <i class="fas fa-tools fa-2x text-secondary"></i>
                    </div>
                    <h4 class="fw-bold">Body Parts</h4>
                    <p class="text-muted mb-3">Mirrors, lights, bumpers, trim</p>
                    <a href="all-parts.php?catname=Body+Parts" class="btn btn-sm btn-outline-secondary px-4">Browse</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">OUR PROMISE</span>
            <h2 class="fw-bold">Why Choose CarBazar?</h2>
            <p class="text-muted">Pakistan's most trusted auto marketplace</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="feature-card text-center p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:65px;height:65px;background:#e8f5e9;">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <h5 class="fw-bold">100% Genuine Parts</h5>
                    <p class="text-muted mb-0">All parts are verified and 100% authentic from trusted suppliers</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card text-center p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:65px;height:65px;background:#e8f0fe;">
                        <i class="fas fa-truck fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Fast Delivery</h5>
                    <p class="text-muted mb-0">Quick shipping with live tracking on all orders across Pakistan</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card text-center p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:65px;height:65px;background:#fffde7;">
                        <i class="fas fa-shield-alt fa-2x text-warning"></i>
                    </div>
                    <h5 class="fw-bold">Secure Payment</h5>
                    <p class="text-muted mb-0">100% safe and encrypted transactions — your money is protected</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-card text-center p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:65px;height:65px;background:#fdecea;">
                        <i class="fas fa-undo fa-2x text-danger"></i>
                    </div>
                    <h5 class="fw-bold">Easy Returns</h5>
                    <p class="text-muted mb-0">Hassle-free returns within 30 days — no questions asked</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SELL SECTION -->
<section id="sell" class="py-5 text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">START SELLING</span>
            <h2 class="fw-bold mb-2">Want to Sell on CarBazar?</h2>
            <p class="mb-0" style="opacity:.85;">List your car or spare parts and reach thousands of buyers across Pakistan</p>
        </div>
        <div class="row g-4">
            <!-- Sell Car -->
            <div class="col-md-6">
                <div class="sell-card p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px;background:#f0c040;flex-shrink:0;">
                            <i class="fas fa-car fa-2x text-dark"></i>
                        </div>
                        <h4 class="mb-0 fw-bold">Sell Your Car</h4>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Free listing for 30 days</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Reach 50,000+ active buyers</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Verified buyer inquiries only</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Price suggestion tool included</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Sell faster with featured ads</li>
                    </ul>
                    <?php if (isLoggedIn() && isSeller()): ?>
                        <a href="backend/seller/add-product.php?type=car" class="btn btn-warning fw-bold w-100 py-2">
                            <i class="fas fa-car me-2"></i>List My Car — Free
                        </a>
                    <?php elseif (isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-warning fw-bold w-100 py-2">
                            <i class="fas fa-car me-2"></i>Upgrade to Seller Account
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=backend/seller/add-product.php?type=car" class="btn btn-warning fw-bold w-100 py-2">
                            <i class="fas fa-car me-2"></i>List My Car — Free
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Sell Parts -->
            <div class="col-md-6">
                <div class="sell-card p-4 h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:60px;height:60px;background:#0d6efd;flex-shrink:0;">
                            <i class="fas fa-cogs fa-2x text-white"></i>
                        </div>
                        <h4 class="mb-0 fw-bold">Sell Spare Parts</h4>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Low commission rates (5%)</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Easy product listing dashboard</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Seller analytics &amp; insights</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>Secure payment gateway</li>
                        <li class="mb-2"><i class="fas fa-check text-warning me-2"></i>24/7 seller support team</li>
                    </ul>
                    <?php if (isLoggedIn() && isSeller()): ?>
                        <a href="backend/seller/add-product.php?type=parts" class="btn btn-primary fw-bold w-100 py-2">
                            <i class="fas fa-store me-2"></i>Add Spare Parts Listing
                        </a>
                    <?php elseif (isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-primary fw-bold w-100 py-2">
                            <i class="fas fa-store me-2"></i>Upgrade to Seller Account
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=backend/seller/add-product.php?type=parts" class="btn btn-primary fw-bold w-100 py-2">
                            <i class="fas fa-store me-2"></i>Become a Seller
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">REVIEWS</span>
            <h2 class="fw-bold">What Our Customers Say</h2>
            <p class="text-muted">Real feedback from real customers</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card p-4 shadow-sm">
                    <div class="d-flex mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="mb-3">"Great experience! Found the exact part I needed at an affordable price. Delivery was fast and the product quality is excellent. Highly recommend CarBazar!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;font-weight:bold;">AK</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Ahmed Khan</h6>
                            <small class="text-muted">Verified Buyer — Karachi</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card p-4 shadow-sm">
                    <div class="d-flex mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="mb-3">"Selling on CarBazar is awesome! Great commission rates and the customer support is very helpful. I've sold over 50 parts in just 2 months. Highly recommended!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;font-weight:bold;">FA</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Fatima Ali</h6>
                            <small class="text-muted">Seller — Lahore</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="testimonial-card p-4 shadow-sm">
                    <div class="d-flex mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="mb-3">"Best platform for car parts in Pakistan! I trust CarBazar for genuine products. Their customer service is outstanding and prices are very competitive."</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;font-weight:bold;">UH</div>
                        <div>
                            <h6 class="mb-0 fw-bold">Usman Haider</h6>
                            <small class="text-muted">Verified Buyer — Islamabad</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- NEWSLETTER -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="mb-3">
                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                </div>
                <h2 class="fw-bold mb-3">Subscribe to Our Newsletter</h2>
                <p class="text-muted mb-4">Get the latest deals, new arrivals, and exclusive offers directly in your inbox. Join 10,000+ subscribers!</p>
                <form class="d-flex gap-2 justify-content-center flex-wrap" onsubmit="subscribeNewsletter(event)">
                    <input type="email" class="form-control form-control-lg" placeholder="Enter your email address" required style="max-width:400px;">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">Subscribe</button>
                </form>
                <small class="text-muted d-block mt-2">We respect your privacy. Unsubscribe anytime.</small>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT -->
<section id="contact" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark px-3 py-1 mb-2" style="border-radius:20px;font-size:.75rem;letter-spacing:1px;">SUPPORT</span>
            <h2 class="fw-bold">Get in Touch</h2>
            <p class="text-muted">We're here to help — reach out anytime</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;background:#e8f0fe;">
                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Address</h5>
                    <p class="text-muted mb-0">123 Car Lane<br>Auto City, Vehari<br>Pakistan</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;background:#e8f5e9;">
                        <i class="fas fa-phone fa-2x text-success"></i>
                    </div>
                    <h5 class="fw-bold">Phone</h5>
                    <p class="text-muted mb-0">+92 304 0369392<br>+92 304 0369394<br>Available 24/7</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="text-center p-4 bg-white rounded shadow-sm h-100">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;background:#fffde7;">
                        <i class="fas fa-envelope fa-2x text-warning"></i>
                    </div>
                    <h5 class="fw-bold">Email</h5>
                    <p class="text-muted mb-0">support@carbazar.com<br>sales@carbazar.com<br>seller@carbazar.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="cb-footer">

    <!-- Top Border Line -->
    <div class="cb-footer-topbar"></div>

    <!-- Main Grid -->
    <div class="cb-footer-body">
        <div class="container">
            <div class="row g-4">

                <!-- Col 1: Brand -->
                <div class="col-lg-3 col-md-6">
                    <div class="cb-brand">
                        <span class="cb-brand-icon"><i class="fas fa-car"></i></span>
                        <span class="cb-brand-name">CarBazar</span>
                    </div>
                    <p class="cb-desc">Pakistan's trusted marketplace for used cars &amp; genuine spare parts. Best prices, verified sellers.</p>
                    <div class="cb-stats">
                        <div class="cb-stat"><b>500+</b><span>Cars</span></div>
                        <div class="cb-stat"><b>10K+</b><span>Parts</span></div>
                        <div class="cb-stat"><b>2K+</b><span>Buyers</span></div>
                        <div class="cb-stat"><b>4.9★</b><span>Rating</span></div>
                    </div>
                    <div class="cb-socials">
                        <a href="#" class="cb-soc cb-fb"  title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="cb-soc cb-tw"  title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="cb-soc cb-ig"  title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="cb-soc cb-yt"  title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="cb-soc cb-wa"  title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="cb-heading">Quick Links</h6>
                    <ul class="cb-links">
                        <li>
                            <a href="#home">
                                <i class="fas fa-home me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Home
                            </a>
                        </li>
                        <li>
                            <a href="all-cars.php">
                                <i class="fas fa-car me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Cars For Sale
                            </a>
                        </li>
                        <li>
                            <a href="all-parts.php">
                                <i class="fas fa-cogs me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Spare Parts
                            </a>
                        </li>
                        <li>
                            <a href="#categories">
                                <i class="fas fa-th-large me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Categories
                            </a>
                        </li>
                        <li>
                            <a href="sell.php">
                                <i class="fas fa-tag me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Sell on CarBazar
                            </a>
                        </li>
                        <li>
                            <a href="#about">
                                <i class="fas fa-info-circle me-1" style="color:#f59e0b;font-size:0.75rem;"></i>About Us
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 3: Support -->
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="cb-heading">Support</h6>
                    <ul class="cb-links">
                        <li>
                            <a href="contact.php">
                                <i class="fas fa-headset me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Contact Us
                            </a>
                        </li>
                        <li>
                            <a href="orders.php">
                                <i class="fas fa-truck me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Shipping Info
                            </a>
                        </li>
                        <li>
                            <a href="contact.php#returns">
                                <i class="fas fa-undo-alt me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Returns Policy
                            </a>
                        </li>
                        <li>
                            <a href="contact.php#warranty">
                                <i class="fas fa-shield-alt me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Warranty
                            </a>
                        </li>
                        <li>
                            <a href="contact.php#faq">
                                <i class="fas fa-question-circle me-1" style="color:#f59e0b;font-size:0.75rem;"></i>FAQs
                            </a>
                        </li>
                        <li>
                            <a href="contact.php#privacy">
                                <i class="fas fa-lock me-1" style="color:#f59e0b;font-size:0.75rem;"></i>Privacy Policy
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Col 4: Contact -->
                <div class="col-lg-5 col-md-6">
                    <h6 class="cb-heading">Get In Touch</h6>
                    <div class="cb-contacts">
                        <div class="cb-contact">
                            <i class="fas fa-map-marker-alt"></i>
                            <div><b>Address</b><span>123 Car Lane, Auto City, Vehari, Pakistan</span></div>
                        </div>
                        <div class="cb-contact">
                            <i class="fas fa-phone-alt"></i>
                            <div><b>Phone</b><span>+92 304 0619219 &nbsp;|&nbsp; +92 304 6109219</span></div>
                        </div>
                        <div class="cb-contact">
                            <i class="fas fa-envelope"></i>
                            <div><b>Email</b><span>support@carbazar.com &nbsp;|&nbsp; seller@carbazar.com</span></div>
                        </div>
                        <div class="cb-contact">
                            <i class="fas fa-clock"></i>
                            <div><b>Hours</b><span>Mon–Sat: 9AM–9PM &nbsp;|&nbsp; Sunday: 10AM–6PM</span></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="cb-footer-bottom">
        <div class="container">
            <div class="cb-bottom-inner">
                <p>&copy; 2026 <strong>CarBazar</strong>. All rights reserved.
                    <a href="#">Privacy</a> &middot; <a href="#">Terms</a> &middot; <a href="#">Sitemap</a>
                </p>
                <a href="#home" class="cb-top-btn" title="Back to top"><i class="fas fa-arrow-up"></i></a>
            </div>
        </div>
    </div>

</footer>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- INLINE JAVASCRIPT -->
<script>
// ============================================================
// Tab switching
// ============================================================
function switchSearchTab(tab) {
    document.getElementById('carsSearch').style.display = tab === 'cars' ? 'block' : 'none';
    document.getElementById('partsSearch').style.display = tab === 'parts' ? 'block' : 'none';
    document.getElementById('tabCars').classList.toggle('active', tab === 'cars');
    document.getElementById('tabParts').classList.toggle('active', tab === 'parts');
}

// ============================================================
// Filter cars
// ============================================================
function filterCars() {
    var brand  = document.getElementById('searchBrand').value.toLowerCase();
    var city   = document.getElementById('searchCity').value.toLowerCase();
    var budget = parseInt(document.getElementById('searchBudget').value) || 999999999;

    var cards = document.querySelectorAll('.car-card-item');
    var found = 0;
    cards.forEach(function(card) {
        var cb   = card.dataset.brand.toLowerCase();
        var cc   = card.dataset.city.toLowerCase();
        var cp   = parseInt(card.dataset.price);
        var show = (!brand || cb.includes(brand)) &&
                   (!city  || cc.includes(city))  &&
                   (cp <= budget);
        card.style.display = show ? 'block' : 'none';
        if (show) found++;
    });

    var msg = document.getElementById('carsNotFound');
    if (msg) msg.style.display = found === 0 ? 'block' : 'none';

    // Scroll to cars section
    document.getElementById('cars').scrollIntoView({ behavior: 'smooth' });
}

function resetCarSearch() {
    document.getElementById('searchBrand').value = '';
    document.getElementById('searchCity').value  = '';
    document.getElementById('searchBudget').value = '';
    filterCars();
}

// ============================================================
// Filter parts
// ============================================================
function filterParts() {
    var query = document.getElementById('searchPartName').value.toLowerCase();
    var cat   = document.getElementById('searchPartCat').value.toLowerCase();

    var cards = document.querySelectorAll('.part-card-item');
    var found = 0;
    cards.forEach(function(card) {
        var name     = card.dataset.name.toLowerCase();
        var category = card.dataset.category.toLowerCase();
        var show = (!query || name.includes(query)) &&
                   (!cat   || category.includes(cat));
        card.style.display = show ? 'block' : 'none';
        if (show) found++;
    });

    // Scroll to parts section
    document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
}

// ============================================================
// Contact seller
// ============================================================
function contactSeller(carId, carTitle) {
    // DB car hai (id > 0) → detail page pe jao
    if (carId && carId > 0) {
        window.location.href = 'car-detail.php?id=' + carId;
        return;
    }
    // Static car → login check
    <?php if (isLoggedIn()): ?>
    alert('Contact Seller:\nPhone: +92 304 0369392\nEmail: support@carbazar.com\n\nCar: ' + carTitle);
    <?php else: ?>
    if (confirm('Please login to contact the seller.\n\nGo to login page?')) {
        window.location.href = 'login.php';
    }
    <?php endif; ?>
}

// ============================================================
// Add to wishlist — DB product ke liye real API call
// ============================================================
function addToWishlist(id, type) {
    <?php if (isLoggedIn()): ?>
    if (!id || id <= 0) {
        showNotification('This is a demo item and cannot be saved to wishlist.', 'error');
        return;
    }
    var formData = new FormData();
    if (type === 'car') {
        formData.append('car_id', id);
    } else {
        formData.append('product_id', id);
    }
    fetch('backend/api/wishlist.php?action=add', { method: 'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.status === 'success') {
                showNotification('❤️ Saved to wishlist! <a href="wishlist.php" style="color:#fff;text-decoration:underline;">View</a>', 'success');
                var badge = document.getElementById('wl-nav-badge');
                if (badge) {
                    var cur = parseInt(badge.textContent) || 0;
                    badge.textContent = cur + 1;
                    badge.style.display = 'inline-block';
                }
            } else if (data.status === 'info') {
                showNotification('Already in your wishlist! <a href="wishlist.php" style="color:#fff;text-decoration:underline;">View</a>', 'success');
            } else {
                showNotification(data.message || 'Could not add to wishlist.', 'error');
            }
        })
        .catch(function(){ showNotification('Network error. Please try again.', 'error'); });
    <?php else: ?>
    showNotification('Please login to use wishlist', 'error');
    setTimeout(function() { window.location.href = 'login.php'; }, 1500);
    <?php endif; ?>
}

// ============================================================
// Add to cart — DB product (id > 0) ya static
// ============================================================
function addToCart(name, price, productId) {
    <?php if (isLoggedIn()): ?>
    if (!productId || productId <= 0) {
        showNotification(name + ' — This is a demo product and cannot be added to cart.', 'error');
        return;
    }
    var formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    fetch('backend/api/cart.php?action=add', { method: 'POST', body: formData })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.status === 'success') {
                showNotification('✓ ' + name + ' added to cart!', 'success');
                // Badge update
                var badge = document.getElementById('cart-nav-badge');
                if (badge) {
                    var cur = parseInt(badge.textContent) || 0;
                    badge.textContent = cur + 1;
                    badge.style.display = 'inline-block';
                }
            } else {
                showNotification(data.message || 'Could not add to cart.', 'error');
            }
        })
        .catch(function(){ showNotification('Network error. Please try again.', 'error'); });
    <?php else: ?>
    showNotification('Please login to use cart', 'error');
    setTimeout(function() { window.location.href = 'login.php'; }, 1500);
    <?php endif; ?>
}

// ============================================================
// Notification
// ============================================================
function showNotification(message, type) {
    var n = document.createElement('div');
    n.className = 'notification ' + type;
    n.innerHTML = message;
    document.body.appendChild(n);
    setTimeout(function() { n.remove(); }, 3000);
}

// ============================================================
// Newsletter
// ============================================================
function subscribeNewsletter(e) {
    e.preventDefault();
    var email = e.target.querySelector('input').value;
    showNotification('Subscribed: ' + email + ' ✅', 'success');
    e.target.reset();
}

// ============================================================
// Smooth scroll
// ============================================================
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>

<!-- ============================================================ -->
<!-- CARBAZAR AI CHATBOT WIDGET                                    -->
<!-- ============================================================ -->
<style>
/* Floating Button */
#cb-chat-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 58px;
    height: 58px;
    background: linear-gradient(135deg, #f59e0b, #f0c040);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(245,158,11,.45);
    z-index: 10000;
    transition: transform .2s, box-shadow .2s;
    border: none;
}
#cb-chat-fab:hover { transform: scale(1.1); box-shadow: 0 10px 30px rgba(245,158,11,.55); }
#cb-chat-fab i { font-size: 1.4rem; color: #0f172a; }
#cb-chat-fab .cb-fab-badge {
    position: absolute;
    top: -4px; right: -4px;
    background: #ef4444;
    color: #fff;
    font-size: .6rem;
    font-weight: 700;
    width: 18px; height: 18px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
}

/* Chat Window */
#cb-chat-window {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: 360px;
    max-height: 520px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    z-index: 9999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: cbSlideUp .25s ease;
}
@keyframes cbSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
#cb-chat-window.open { display: flex; }

/* Header */
.cb-chat-header {
    background: linear-gradient(135deg, #0f0f1a, #1a1a2e);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.cb-chat-header .cb-bot-avatar {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #f59e0b, #f0c040);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; color: #0f172a; font-weight: 800;
    flex-shrink: 0;
}
.cb-chat-header .cb-bot-info { flex: 1; }
.cb-chat-header .cb-bot-info h6 { margin: 0; color: #fff; font-size: .88rem; font-weight: 700; }
.cb-chat-header .cb-bot-info small { color: #f59e0b; font-size: .7rem; }
.cb-chat-close {
    background: none; border: none; color: #9ca3af;
    font-size: 1.1rem; cursor: pointer; padding: 0;
    transition: color .15s;
}
.cb-chat-close:hover { color: #fff; }

/* Messages */
#cb-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f8fafc;
}
#cb-chat-messages::-webkit-scrollbar { width: 4px; }
#cb-chat-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.cb-msg {
    max-width: 82%;
    padding: 9px 13px;
    border-radius: 14px;
    font-size: .82rem;
    line-height: 1.5;
    word-break: break-word;
}
.cb-msg.bot {
    background: #fff;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    align-self: flex-start;
}
.cb-msg.user {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #0f172a;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
    font-weight: 600;
}
.cb-msg.typing {
    background: #fff;
    color: #94a3b8;
    align-self: flex-start;
    font-style: italic;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
}
.cb-msg.typing span {
    display: inline-block;
    animation: cbDot 1.2s infinite;
}
.cb-msg.typing span:nth-child(2) { animation-delay: .2s; }
.cb-msg.typing span:nth-child(3) { animation-delay: .4s; }
@keyframes cbDot {
    0%,80%,100% { opacity: .2; transform: translateY(0); }
    40%          { opacity: 1;  transform: translateY(-4px); }
}

/* Quick Replies */
.cb-quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 0 16px 10px;
    background: #f8fafc;
}
.cb-quick-btn {
    background: #fff;
    border: 1.5px solid #f59e0b;
    color: #b45309;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: .73rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.cb-quick-btn:hover { background: #f59e0b; color: #0f172a; }

/* Input */
.cb-chat-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #e2e8f0;
    background: #fff;
}
#cb-chat-input {
    flex: 1;
    border: 1.5px solid #e2e8f0;
    border-radius: 22px;
    padding: 8px 14px;
    font-size: .82rem;
    outline: none;
    transition: border-color .15s;
    resize: none;
    font-family: inherit;
}
#cb-chat-input:focus { border-color: #f59e0b; }
#cb-chat-send {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    border: none;
    border-radius: 50%;
    color: #0f172a;
    font-size: .9rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform .15s;
}
#cb-chat-send:hover { transform: scale(1.1); }

@media (max-width: 480px) {
    #cb-chat-window { width: calc(100vw - 24px); right: 12px; bottom: 90px; }
    #cb-chat-fab    { right: 16px; bottom: 20px; }
}
</style>

<!-- FAB Button -->
<button id="cb-chat-fab" onclick="cbToggleChat()" aria-label="Open CarBazar Assistant">
    <i class="fas fa-robot" id="cb-fab-icon"></i>
    <span class="cb-fab-badge" id="cb-fab-badge">1</span>
</button>

<!-- Chat Window -->
<div id="cb-chat-window">
    <!-- Header -->
    <div class="cb-chat-header">
        <div class="cb-bot-avatar">CB</div>
        <div class="cb-bot-info">
            <h6>CarBazar Assistant</h6>
            <small>🟢 Online — Always here to help</small>
        </div>
        <button class="cb-chat-close" onclick="cbToggleChat()" aria-label="Close chat">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Messages -->
    <div id="cb-chat-messages"></div>

    <!-- Quick Replies -->
    <div class="cb-quick-replies" id="cb-quick-replies">
        <button class="cb-quick-btn" onclick="cbQuickSend('Buy a car')">🚗 Buy Car</button>
        <button class="cb-quick-btn" onclick="cbQuickSend('Sell my car')">💰 Sell Car</button>
        <button class="cb-quick-btn" onclick="cbQuickSend('Find spare parts')">🔧 Spare Parts</button>
        <button class="cb-quick-btn" onclick="cbQuickSend('How to create account?')">👤 Account Help</button>
    </div>

    <!-- Input -->
    <div class="cb-chat-input-wrap">
        <textarea id="cb-chat-input" rows="1" placeholder="Type your message..." 
                  onkeydown="cbHandleKey(event)"></textarea>
        <button id="cb-chat-send" onclick="cbSendMessage()" aria-label="Send">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
(function() {
    // ── Config ──────────────────────────────────────────────
    // API key ab backend/api/ai_chat.php mein safely store hai

    // System prompt ab backend/api/ai_chat.php mein safely store hai

    // ── State ───────────────────────────────────────────────
    var chatHistory = [];
    var isOpen      = false;
    var isTyping    = false;

    // Conversation context — budget/city/type track karne ke liye
    var cbContext = {
        mode   : null,   // 'cars' | 'parts' | null
        budget : 0,
        city   : '',
        brand  : '',
        query  : ''
    };

    // ── DOM refs ────────────────────────────────────────────
    var win      = document.getElementById('cb-chat-window');
    var msgs     = document.getElementById('cb-chat-messages');
    var input    = document.getElementById('cb-chat-input');
    var badge    = document.getElementById('cb-fab-badge');
    var fabIcon  = document.getElementById('cb-fab-icon');
    var quickDiv = document.getElementById('cb-quick-replies');

    // ── Init ────────────────────────────────────────────────
    cbAddBotMsg('👋 Assalam o Alaikum! I\'m your <b>CarBazar Assistant</b>.<br>How can I help you today? 🚗');

    // ── Toggle ──────────────────────────────────────────────
    window.cbToggleChat = function() {
        isOpen = !isOpen;
        win.classList.toggle('open', isOpen);
        fabIcon.className = isOpen ? 'fas fa-times' : 'fas fa-robot';
        if (isOpen) {
            badge.style.display = 'none';
            input.focus();
            msgs.scrollTop = msgs.scrollHeight;
        }
    };

    // ── Quick send ──────────────────────────────────────────
    window.cbQuickSend = function(text) {
        input.value = text;
        cbSendMessage();
        quickDiv.style.display = 'none';
    };

    // ── Key handler ─────────────────────────────────────────
    window.cbHandleKey = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            cbSendMessage();
        }
    };

    // ── Budget extractor ─────────────────────────────────────
    function cbExtractBudget(text) {
        // "10 lakh", "1500000", "15 lac", "1.5 million"
        var m;
        m = text.match(/(\d+(?:\.\d+)?)\s*(lakh|lac)/i);
        if (m) return parseFloat(m[1]) * 100000;
        m = text.match(/(\d+(?:\.\d+)?)\s*million/i);
        if (m) return parseFloat(m[1]) * 1000000;
        m = text.match(/(\d+(?:\.\d+)?)\s*crore/i);
        if (m) return parseFloat(m[1]) * 10000000;
        m = text.match(/(\d+(?:\.\d+)?)\s*k\b/i);
        if (m) return parseFloat(m[1]) * 1000;
        m = text.match(/\b(\d{5,})\b/);  // raw number like 1500000
        if (m) return parseFloat(m[1]);
        return 0;
    }

    // ── City extractor ───────────────────────────────────────
    function cbExtractCity(text) {
        var cities = ['karachi','lahore','islamabad','rawalpindi','peshawar',
                      'quetta','multan','faisalabad','vehari','sialkot','hyderabad'];
        var t = text.toLowerCase();
        for (var i = 0; i < cities.length; i++) {
            if (t.indexOf(cities[i]) !== -1) return cities[i];
        }
        return '';
    }

    // ── Brand extractor ──────────────────────────────────────
    function cbExtractBrand(text) {
        var brands = ['toyota','honda','suzuki','kia','hyundai','daihatsu',
                      'nissan','mitsubishi','mercedes','bmw','audi','changan','mg'];
        var t = text.toLowerCase();
        for (var i = 0; i < brands.length; i++) {
            if (t.indexOf(brands[i]) !== -1) return brands[i];
        }
        return '';
    }

    // ── DB Search ────────────────────────────────────────────
    function cbSearchDB(type, budget, city, brand, query, callback) {
        fetch('backend/api/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, budget: budget, city: city, brand: brand, query: query })
        })
        .then(function(r) { return r.json(); })
        .then(callback)
        .catch(function() { callback(null); });
    }

    // ── Render DB results ────────────────────────────────────
    function cbRenderCars(data) {
        if (!data || data.status !== 'success' || !data.items.length) {
            return '😔 Is budget mein koi car available nahi mili.<br>Aap <a href="all-cars.php" style="color:#f59e0b;font-weight:700;">All Cars</a> page pe check kar sakte hain ya budget thoda barha kar dekhein.';
        }
        var html = '🚗 <b>' + data.total + ' cars</b> mile aapke budget mein';
        if (cbContext.city) html += ' (' + cbContext.city + ')';
        html += '! Yahan top ' + data.items.length + ' hain:<br><br>';

        data.items.forEach(function(car) {
            var img = car.image && car.image !== 'default.jpg'
                ? 'uploads/' + car.image
                : 'https://via.placeholder.com/60x45?text=Car';
            var price = parseInt(car.price).toLocaleString('en-PK');
            html += '<div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border-radius:10px;padding:8px;margin-bottom:7px;">'
                  + '<img src="' + img + '" style="width:60px;height:45px;object-fit:cover;border-radius:7px;flex-shrink:0;" onerror="this.src=\'https://via.placeholder.com/60x45?text=Car\'">'
                  + '<div style="flex:1;min-width:0;">'
                  + '<div style="font-weight:700;font-size:.8rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + car.title + '</div>'
                  + '<div style="font-size:.72rem;color:#64748b;">' + car.year + ' &bull; ' + car.fuel_type + (car.city ? ' &bull; ' + car.city : '') + '</div>'
                  + '<div style="font-weight:800;color:#f59e0b;font-size:.82rem;">PKR ' + price + '</div>'
                  + '</div>'
                  + '<a href="car-detail.php?id=' + car.id + '" style="background:#f59e0b;color:#0f172a;border-radius:7px;padding:4px 9px;font-size:.7rem;font-weight:700;text-decoration:none;white-space:nowrap;">View</a>'
                  + '</div>';
        });

        if (data.total > data.items.length) {
            html += '<a href="all-cars.php" style="display:block;text-align:center;background:#0f172a;color:#f59e0b;border-radius:8px;padding:7px;font-size:.78rem;font-weight:700;text-decoration:none;margin-top:4px;">View All ' + data.total + ' Cars →</a>';
        }
        return html;
    }

    function cbRenderParts(data) {
        if (!data || data.status !== 'success' || !data.items.length) {
            return '😔 Is budget mein koi part available nahi mila.<br>Aap <a href="all-parts.php" style="color:#f59e0b;font-weight:700;">All Parts</a> page pe check kar sakte hain.';
        }
        var html = '🔧 <b>' + data.total + ' parts</b> mile';
        if (cbContext.budget > 0) html += ' PKR ' + parseInt(cbContext.budget).toLocaleString('en-PK') + ' budget mein';
        html += '!<br><br>';

        data.items.forEach(function(part) {
            var img = part.image && part.image !== 'default.jpg'
                ? 'uploads/' + part.image
                : 'https://via.placeholder.com/60x45?text=Part';
            var price = part.discount_price && part.discount_price < part.price
                ? '<span style="text-decoration:line-through;color:#94a3b8;font-size:.7rem;">PKR ' + parseInt(part.price).toLocaleString('en-PK') + '</span> <span style="color:#f59e0b;font-weight:800;">PKR ' + parseInt(part.discount_price).toLocaleString('en-PK') + '</span>'
                : '<span style="color:#f59e0b;font-weight:800;">PKR ' + parseInt(part.price).toLocaleString('en-PK') + '</span>';
            html += '<div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border-radius:10px;padding:8px;margin-bottom:7px;">'
                  + '<img src="' + img + '" style="width:60px;height:45px;object-fit:cover;border-radius:7px;flex-shrink:0;" onerror="this.src=\'https://via.placeholder.com/60x45?text=Part\'">'
                  + '<div style="flex:1;min-width:0;">'
                  + '<div style="font-weight:700;font-size:.8rem;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + part.name + '</div>'
                  + '<div style="font-size:.72rem;color:#64748b;">' + (part.brand || '') + (part.category ? ' &bull; ' + part.category : '') + '</div>'
                  + '<div style="font-size:.78rem;">' + price + '</div>'
                  + '</div>'
                  + '<a href="all-parts.php" style="background:#f59e0b;color:#0f172a;border-radius:7px;padding:4px 9px;font-size:.7rem;font-weight:700;text-decoration:none;white-space:nowrap;">View</a>'
                  + '</div>';
        });

        if (data.total > data.items.length) {
            html += '<a href="all-parts.php" style="display:block;text-align:center;background:#0f172a;color:#f59e0b;border-radius:8px;padding:7px;font-size:.78rem;font-weight:700;text-decoration:none;margin-top:4px;">View All ' + data.total + ' Parts →</a>';
        }
        return html;
    }

    // ── Send message ─────────────────────────────────────────
    window.cbSendMessage = function() {
        var text = input.value.trim();
        if (!text || isTyping) return;
        input.value = '';
        quickDiv.style.display = 'none';

        cbAddUserMsg(text);
        chatHistory.push({ role: 'user', parts: [{ text: text }] });

        // Extract info from message
        var budget = cbExtractBudget(text);
        var city   = cbExtractCity(text);
        var brand  = cbExtractBrand(text);

        if (budget > 0) cbContext.budget = budget;
        if (city)       cbContext.city   = city;
        if (brand)      cbContext.brand  = brand;

        // Mode detect
        var isCarsIntent  = /car|gaari|gari|vehicle|buy|khareed|sedan|suv|hatchback/i.test(text);
        var isPartsIntent = /part|spare|engine|brake|tyre|tire|filter|battery|oil|clutch|radiator|suspension|shock|mirror|bulb|wiper|belt|piston|gear|electrical/i.test(text);

        if (isCarsIntent && !isPartsIntent) cbContext.mode = 'cars';
        if (isPartsIntent)                  cbContext.mode = 'parts';

        // Parts keyword for search
        if (isPartsIntent) {
            var partKeywords = text.match(/engine|brake|tyre|tire|filter|battery|oil|clutch|radiator|suspension|shock|mirror|bulb|wiper|belt|piston|gear/i);
            if (partKeywords) cbContext.query = partKeywords[0];
        }

        cbShowTyping();

        // ── DB Search flow ───────────────────────────────────
        if (cbContext.mode === 'cars' && cbContext.budget > 0) {
            cbSearchDB('cars', cbContext.budget, cbContext.city, cbContext.brand, '', function(data) {
                cbRemoveTyping();
                isTyping = false;
                var reply = cbRenderCars(data);
                chatHistory.push({ role: 'model', parts: [{ text: reply }] });
                cbAddBotMsg(reply);
            });
            isTyping = true;
            return;
        }

        if (cbContext.mode === 'parts') {
            if (cbContext.budget > 0 || cbContext.query) {
                cbSearchDB('parts', cbContext.budget, '', '', cbContext.query, function(data) {
                    cbRemoveTyping();
                    isTyping = false;
                    var reply = cbRenderParts(data);
                    chatHistory.push({ role: 'model', parts: [{ text: reply }] });
                    cbAddBotMsg(reply);
                });
                isTyping = true;
                return;
            } else {
                // Parts mode but no budget yet — ask
                cbRemoveTyping();
                isTyping = false;
                cbAddBotMsg('🔧 Zaroor! Kaunsa part chahiye aur aapka budget kya hai?<br><br>Misal ke taur pe: <i>"brake pads 5000 mein"</i> ya <i>"engine oil 2000 budget"</i>');
                return;
            }
        }

        if (cbContext.mode === 'cars' && cbContext.budget === 0) {
            // Cars mode but no budget — ask
            cbRemoveTyping();
            isTyping = false;
            cbAddBotMsg('🚗 Zaroor! Aapka <b>budget</b> kitna hai?<br><br>Misal ke taur pe: <i>"10 lakh"</i>, <i>"25 lakh"</i>, <i>"50 lakh"</i><br><br>City bhi batao toh aur better results milenge! 📍');
            return;
        }

        // ── Fallback to local rules / Gemini ─────────────────
        cbCallGemini(text);
    };

    // ── Smart Local Replies (API key ke bina bhi kaam kare) ──
    var cbRules = [
        // Spare Parts
        { p: /spare part|parts|part chahiye|part dhundna|engine|brake|tyre|tire|filter|battery|oil|clutch|radiator|suspension|shock|mirror|light|bulb|wiper|belt|piston|gear/i,
          r: '🔧 Spare parts ke liye <b>All Parts</b> page check karein!<br><br>Kaunsi car ke liye part chahiye? Brand aur model batao, main help karta hoon. 😊<br><br><a href="all-parts.php" style="color:#f59e0b;font-weight:700;">👉 View All Parts</a>' },

        // Buy car
        { p: /buy|khareedna|car chahiye|car lena|purchase|gaari chahiye|mujhe car|car buy/i,
          r: '🚗 Great choice! Kuch details batao:<br><br>1️⃣ <b>Budget</b> kitna hai? (e.g. 10 lakh, 20 lakh)<br>2️⃣ <b>City</b> kaunsi hai?<br>3️⃣ <b>Car type</b>? (Family, Hatchback, SUV, Sedan)<br><br>Main best options suggest karta hoon! 😊' },

        // Budget
        { p: /(\d+)\s*(lakh|lac|million|crore|k\b)/i,
          r: function(m) {
              var amt = m.match(/(\d+)\s*(lakh|lac)/i);
              if (amt) {
                  var n = parseInt(amt[1]);
                  var cars = n <= 10  ? 'Suzuki Mehran, Suzuki Alto, Daihatsu Mira' :
                             n <= 20  ? 'Honda City 2015-18, Toyota Vitz, Suzuki Cultus' :
                             n <= 35  ? 'Honda Civic 2016-18, Toyota Corolla 2015-17' :
                             n <= 60  ? 'Toyota Corolla 2020+, Honda Civic 2019+' :
                                        'Toyota Fortuner, Honda CR-V, Toyota Land Cruiser';
                  return '💰 <b>' + n + ' lakh</b> budget mein yeh cars best hain:<br><br>✅ ' + cars.split(', ').join('<br>✅ ') + '<br><br>City batao, available listings check karta hoon! 🚗<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 View All Cars</a>';
              }
              return '💰 Budget batao (e.g. 10 lakh, 20 lakh), main best cars suggest karta hoon! 🚗';
          }
        },

        // Sell car
        { p: /sell|bechna|car sell|apni car|gaari bechni|listing|ad post|ad lagana/i,
          r: '💰 Car sell karna easy hai! Yeh steps follow karo:<br><br>1️⃣ <b>Sell</b> button click karo (top menu mein)<br>2️⃣ Car details bharo (brand, model, year, price)<br>3️⃣ Clear photos upload karo (4-6 photos best hain)<br>4️⃣ City aur contact info add karo<br>5️⃣ Submit karo — listing live ho jaegi! ✅<br><br><a href="sell.php" style="color:#f59e0b;font-weight:700;">👉 Post Your Ad Now</a>' },

        // Toyota
        { p: /toyota|corolla|fortuner|hilux|land cruiser|prado|yaris|vitz/i,
          r: '🚗 <b>Toyota</b> Pakistan mein sabse reliable brand hai!<br><br>Popular models:<br>✅ Corolla — family ke liye best<br>✅ Yaris — fuel efficient<br>✅ Fortuner — SUV, powerful<br>✅ Hilux — heavy duty<br><br>Budget batao, available options check karta hoon! 💪<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 View Toyota Cars</a>' },

        // Honda
        { p: /honda|civic|city|hrv|brv|accord|vezel/i,
          r: '🚗 <b>Honda</b> great choice hai!<br><br>Popular models:<br>✅ Honda City — affordable, fuel efficient<br>✅ Honda Civic — sporty, premium feel<br>✅ Honda BR-V — 7-seater family car<br>✅ Honda HR-V — compact SUV<br><br>Kaunsa model pasand hai? Budget batao! 😊<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 View Honda Cars</a>' },

        // Suzuki
        { p: /suzuki|mehran|alto|cultus|swift|wagon|jimny|vitara/i,
          r: '🚗 <b>Suzuki</b> budget-friendly aur reliable hai!<br><br>Popular models:<br>✅ Alto — sabse affordable, 660cc<br>✅ Cultus — city driving ke liye best<br>✅ Swift — sporty look<br>✅ Wagon R — spacious hatchback<br><br>Budget aur city batao! 🚗<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 View Suzuki Cars</a>' },

        // Account / Register
        { p: /account|register|signup|sign up|login|password|create account|account kaise/i,
          r: '👤 Account banana bilkul easy hai!<br><br>1️⃣ <b>Register</b> button click karo<br>2️⃣ Name, Email, Password bharo<br>3️⃣ Submit karo — account ready! ✅<br><br>Account se aap cars wishlist kar sakte ho, orders track kar sakte ho aur ads post kar sakte ho.<br><br><a href="register.php" style="color:#f59e0b;font-weight:700;">👉 Create Account</a>' },

        // Contact seller
        { p: /contact|seller se baat|seller contact|phone number|number kaise|seller ka number/i,
          r: '📞 Seller se contact karna easy hai!<br><br>1️⃣ Car ya part ki listing open karo<br>2️⃣ <b>"Contact Seller"</b> button click karo<br>3️⃣ Login hona zaroori hai<br><br>Hamare support ke liye:<br>📱 <b>+92 304 0369392</b><br>📧 <b>support@carbazar.com</b>' },

        // Images / upload
        { p: /image|photo|upload|tasveer|picture/i,
          r: '📸 Images upload karna easy hai!<br><br>✅ Ad post karte waqt "Upload Images" section hoga<br>✅ 4-6 clear photos upload karo<br>✅ Supported formats: JPG, PNG, JFIF<br>✅ Bahar se, andar se, aur engine ki photo zaroor lagao<br><br>Clear photos se car jaldi bikti hai! 💡' },

        // City
        { p: /karachi|lahore|islamabad|rawalpindi|peshawar|quetta|multan|faisalabad|vehari|sialkot|hyderabad/i,
          r: function(m) {
              var city = m.match(/karachi|lahore|islamabad|rawalpindi|peshawar|quetta|multan|faisalabad|vehari|sialkot|hyderabad/i)[0];
              city = city.charAt(0).toUpperCase() + city.slice(1).toLowerCase();
              return '📍 <b>' + city + '</b> mein available cars check karo!<br><br>Search bar mein city select karo ya neeche link se dekho:<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 Browse Cars in ' + city + '</a>';
          }
        },

        // Price / cost
        { p: /price|qeemat|kitne ka|cost|rate|value/i,
          r: '💰 Price check karne ke liye listings page visit karo.<br><br>Har listing mein price clearly likha hota hai. Budget batao, main best options suggest karta hoon! 🚗<br><a href="all-cars.php" style="color:#f59e0b;font-weight:700;">👉 View All Cars</a>' },

        // Greetings
        { p: /^(hi|hello|hey|salam|assalam|aoa|helo|hii|helo|good morning|good evening|good afternoon|kaise ho|how are you)/i,
          r: '👋 Wa Alaikum Assalam! Main theek hoon, shukriya! 😊<br><br>Aap kaise hain? Main aapki kya madad kar sakta hoon?<br><br>🚗 Car khareedni hai?<br>💰 Car bechni hai?<br>🔧 Spare parts chahiye?' },

        // Thanks
        { p: /thank|shukriya|thanks|jazakallah|shukria/i,
          r: '😊 Khushi hui madad karke! Koi aur sawaal ho toh zaroor poochein.<br><br>CarBazar pe aapka swagat hai! 🚗✨' },

        // Bye
        { p: /bye|goodbye|khuda hafiz|allah hafiz|alvida/i,
          r: '👋 Allah Hafiz! CarBazar pe dobara aana. Koi bhi zaroorat ho toh main hamesha yahan hoon! 🚗😊' },

        // FAQ - how it works
        { p: /kaise kaam|how does|how to use|website kaise|carbazar kya|what is carbazar/i,
          r: '🚗 <b>CarBazar</b> Pakistan ka trusted car marketplace hai!<br><br>✅ Cars khareedein aur bechein<br>✅ Spare parts dhundein<br>✅ Verified sellers se deal karein<br>✅ Wishlist aur cart features<br><br>Kya karna chahte hain? Main guide karta hoon! 😊' },
    ];

    function cbGetLocalReply(text) {
        for (var i = 0; i < cbRules.length; i++) {
            if (cbRules[i].p.test(text)) {
                var r = cbRules[i].r;
                return typeof r === 'function' ? r(text) : r;
            }
        }
        return null;
    }

    // ── Call Gemini via PHP Proxy (AI-powered) ───────────────
    function cbCallGemini(userText) {
        isTyping = true;

        // Local reply as instant fallback
        var localReply = cbGetLocalReply(userText);

        fetch('backend/api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                history: chatHistory,
                context: cbContext
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            cbRemoveTyping();
            isTyping = false;

            var reply;
            if (data.status === 'success' && data.reply) {
                reply = data.reply;
                // Markdown → HTML
                reply = reply.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
                reply = reply.replace(/\*(.*?)\*/g, '<i>$1</i>');
                reply = reply.replace(/\n/g, '<br>');
            } else {
                // AI unavailable — local fallback
                reply = localReply || '🤔 Samajh nahi aaya. Kripya aur detail mein batao ya in options mein se choose karo:<br><br>🚗 <a href="all-cars.php" style="color:#f59e0b;">Cars dekho</a> &nbsp;|&nbsp; 🔧 <a href="all-parts.php" style="color:#f59e0b;">Parts dekho</a> &nbsp;|&nbsp; 💰 <a href="sell.php" style="color:#f59e0b;">Car becho</a>';
            }

            chatHistory.push({ role: 'model', parts: [{ text: reply }] });
            cbAddBotMsg(reply);
        })
        .catch(function() {
            cbRemoveTyping();
            isTyping = false;
            var reply = localReply || '⚠️ Connection issue. Please try again.<br><br>🚗 <a href="all-cars.php" style="color:#f59e0b;">Cars dekho</a> &nbsp;|&nbsp; 🔧 <a href="all-parts.php" style="color:#f59e0b;">Parts dekho</a>';
            chatHistory.push({ role: 'model', parts: [{ text: reply }] });
            cbAddBotMsg(reply);
        });
    }

    // ── Add messages ─────────────────────────────────────────
    function cbAddBotMsg(html) {
        var div = document.createElement('div');
        div.className = 'cb-msg bot';
        div.innerHTML = html;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function cbAddUserMsg(text) {
        var div = document.createElement('div');
        div.className = 'cb-msg user';
        div.textContent = text;
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function cbShowTyping() {
        var div = document.createElement('div');
        div.className = 'cb-msg typing';
        div.id = 'cb-typing-indicator';
        div.innerHTML = 'Typing <span>.</span><span>.</span><span>.</span>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function cbRemoveTyping() {
        var t = document.getElementById('cb-typing-indicator');
        if (t) t.remove();
    }
})();
</script>

</body>
</html>