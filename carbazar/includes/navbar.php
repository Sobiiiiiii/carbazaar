<?php
/**
 * includes/navbar.php — CarBazar Professional Navbar
 * Usage: $active_page = 'home'; require_once 'includes/navbar.php';
 * Pages: home | cars | parts | categories | sell | contact
 */
if (!isset($base_url))    $base_url    = '';
if (!isset($active_page)) $active_page = '';

$_nb_cart = 0; $_nb_wl = 0;
if (isLoggedIn()) {
    global $conn;
    $uid = (int)$_SESSION['user_id'];
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id=?");
    $s->bind_param("i",$uid); $s->execute();
    $_nb_cart = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id=?");
    $s->bind_param("i",$uid); $s->execute();
    $_nb_wl = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();
}
if (!function_exists('_nb_active')) {
    function _nb_active($p, $cur) { return $p === $cur ? 'active-link' : ''; }
}
?>
<style>
.cb-navbar{background:linear-gradient(135deg,#0f0f1a 0%,#1a1a2e 60%,#16213e 100%);border-bottom:2px solid rgba(240,192,64,.25);padding:0;position:sticky;top:0;z-index:1050;box-shadow:0 4px 24px rgba(0,0,0,.45);}
.cb-brand{display:flex;align-items:center;gap:10px;text-decoration:none;padding:12px 0;}
.cb-brand-icon{width:40px;height:40px;background:linear-gradient(135deg,#f0c040,#e0a800);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#1a1a2e;box-shadow:0 4px 12px rgba(240,192,64,.4);flex-shrink:0;transition:transform .2s;}
.cb-brand:hover .cb-brand-icon{transform:rotate(-8deg) scale(1.08);}
.cb-brand-text{display:flex;flex-direction:column;justify-content:center;gap:2px;line-height:1;}
.cb-brand-name{font-size:1.25rem;font-weight:800;color:#fff;letter-spacing:-.3px;display:block;line-height:1.1;}
.cb-brand-tagline{font-size:.58rem;color:rgba(240,192,64,.8);font-weight:600;letter-spacing:1.8px;text-transform:uppercase;display:block;line-height:1;white-space:nowrap;}
.cb-nav-link{color:rgba(255,255,255,.75)!important;font-weight:600;font-size:.88rem;padding:8px 14px!important;border-radius:8px;transition:all .2s;display:flex;align-items:center;gap:6px;text-decoration:none;position:relative;letter-spacing:.2px;}
.cb-nav-link:hover{color:#fff!important;background:rgba(255,255,255,.08);}
.cb-nav-link.active-link{color:#f0c040!important;background:rgba(240,192,64,.12);}
.cb-nav-link::after{content:'';position:absolute;bottom:4px;left:50%;right:50%;height:2px;background:#f0c040;border-radius:2px;transition:left .2s,right .2s;}
.cb-nav-link:hover::after,.cb-nav-link.active-link::after{left:14px;right:14px;}
.cb-nav-sell{color:#f0c040!important;border:1.5px solid rgba(240,192,64,.4);background:rgba(240,192,64,.08);}
.cb-nav-sell:hover{background:rgba(240,192,64,.18)!important;border-color:#f0c040;}
.cb-nav-sell::after{display:none;}
.cb-nav-divider{width:1px;height:28px;background:rgba(255,255,255,.12);margin:0 4px;}
.cb-icon-btn{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;text-decoration:none;position:relative;transition:all .2s;border:1.5px solid transparent;flex-shrink:0;}
.cb-icon-btn:hover{transform:translateY(-2px);}
.cb-icon-wishlist{color:#f87171;background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.25);}
.cb-icon-wishlist:hover{background:rgba(248,113,113,.2);border-color:#f87171;color:#f87171;}
.cb-icon-cart{color:#f0c040;background:rgba(240,192,64,.1);border-color:rgba(240,192,64,.25);}
.cb-icon-cart:hover{background:rgba(240,192,64,.2);border-color:#f0c040;color:#f0c040;}
.cb-icon-orders{color:rgba(255,255,255,.8);background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);}
.cb-icon-orders:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.3);color:#fff;}
.cb-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;border-radius:9px;font-size:.62rem;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid #1a1a2e;line-height:1;}
.cb-badge-red{background:#ef4444;color:#fff;}
.cb-badge-yellow{background:#f0c040;color:#1a1a2e;}
.cb-user-btn{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.07);border:1.5px solid rgba(255,255,255,.15);border-radius:10px;padding:6px 12px;color:#fff;transition:all .2s;text-decoration:none;font-size:.85rem;}
.cb-user-btn:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.3);color:#fff;}
.cb-user-avatar{width:28px;height:28px;background:linear-gradient(135deg,#f0c040,#e0a800);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#1a1a2e;flex-shrink:0;}
.cb-user-name{font-weight:600;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cb-btn-signin{padding:7px 16px;border-radius:9px;border:1.5px solid rgba(255,255,255,.3);background:transparent;color:rgba(255,255,255,.85);font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;}
.cb-btn-signin:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.5);color:#fff;}
.cb-btn-signup{padding:7px 18px;border-radius:9px;background:linear-gradient(135deg,#f0c040,#e0a800);border:none;color:#1a1a2e;font-size:.85rem;font-weight:700;text-decoration:none;transition:all .2s;box-shadow:0 4px 12px rgba(240,192,64,.35);}
.cb-btn-signup:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(240,192,64,.5);color:#1a1a2e;}
.cb-btn-dashboard{padding:7px 14px;border-radius:9px;background:rgba(240,192,64,.15);border:1.5px solid rgba(240,192,64,.4);color:#f0c040;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;display:flex;align-items:center;gap:6px;}
.cb-btn-dashboard:hover{background:rgba(240,192,64,.25);border-color:#f0c040;color:#f0c040;}
.cb-btn-logout{padding:7px 14px;border-radius:9px;background:rgba(239,68,68,.1);border:1.5px solid rgba(239,68,68,.25);color:#f87171;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s;display:flex;align-items:center;gap:6px;}
.cb-btn-logout:hover{background:rgba(239,68,68,.2);border-color:#ef4444;color:#fca5a5;}
.cb-toggler{border:1.5px solid rgba(255,255,255,.2);border-radius:8px;padding:6px 10px;background:rgba(255,255,255,.05);color:rgba(255,255,255,.8);transition:all .2s;cursor:pointer;}
.cb-toggler:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.4);}
/* Dropdown */
.cb-dropdown{position:relative;}
.cb-dropdown-menu{position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);background:#1a1a2e;border:1px solid rgba(240,192,64,.2);border-radius:12px;padding:8px;min-width:200px;box-shadow:0 12px 36px rgba(0,0,0,.4);opacity:0;visibility:hidden;transition:opacity .2s,transform .2s;transform:translateX(-50%) translateY(-6px);z-index:2000;}
.cb-dropdown:hover .cb-dropdown-menu{opacity:1;visibility:visible;transform:translateX(-50%) translateY(0);}
.cb-dropdown-menu a{display:flex;align-items:center;gap:9px;padding:9px 14px;border-radius:8px;color:rgba(255,255,255,.8)!important;text-decoration:none;font-size:.84rem;font-weight:600;transition:all .18s;}
.cb-dropdown-menu a:hover{background:rgba(240,192,64,.12);color:#f0c040!important;}
.cb-dropdown-menu a i{width:16px;text-align:center;color:#f0c040;font-size:.8rem;}
.cb-dropdown-divider{height:1px;background:rgba(255,255,255,.08);margin:4px 0;}
.cb-dropdown-arrow{font-size:.65rem;margin-left:2px;transition:transform .2s;}
.cb-dropdown:hover .cb-dropdown-arrow{transform:rotate(180deg);}
@media(max-width:991px){
.cb-nav-link{padding:10px 12px!important;}
.cb-nav-link::after{display:none;}
.cb-nav-divider{display:none;}
.cb-mobile-actions{display:flex;flex-wrap:wrap;gap:8px;padding:12px 0 16px;border-top:1px solid rgba(255,255,255,.1);margin-top:8px;}
.cb-mobile-sub{padding-left:16px;display:flex;flex-direction:column;gap:2px;margin-top:2px;}
.cb-mobile-sub a{color:rgba(255,255,255,.65)!important;font-size:.82rem;padding:6px 10px!important;}
}
@media(min-width:992px){.cb-mobile-actions{display:none!important;}}
</style>

<nav class="cb-navbar">
<div class="container-fluid px-4">
<div class="d-flex align-items-center justify-content-between w-100 py-1">

    <!-- Brand -->
    <a class="cb-brand" href="<?= $base_url ?>index.php">
        <div class="cb-brand-icon"><i class="fas fa-car"></i></div>
        <div class="cb-brand-text">
            <span class="cb-brand-name">CarBazar</span>
            <span class="cb-brand-tagline">Pakistan's #1 Auto Market</span>
        </div>
    </a>

    <!-- Desktop Nav -->
    <ul class="navbar-nav d-none d-lg-flex flex-row gap-1 mb-0">
        <li><a class="cb-nav-link <?= _nb_active('home',$active_page) ?>" href="<?= $base_url ?>index.php#home"><i class="fas fa-home"></i> Home</a></li>
        <li><a class="cb-nav-link <?= _nb_active('cars',$active_page) ?>" href="<?= $base_url ?>index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
        <li><a class="cb-nav-link <?= _nb_active('parts',$active_page) ?>" href="<?= $base_url ?>index.php#products"><i class="fas fa-cogs"></i> Spare Parts</a></li>
        <li><a class="cb-nav-link <?= _nb_active('categories',$active_page) ?>" href="<?= $base_url ?>index.php#categories"><i class="fas fa-th-large"></i> Categories</a></li>
        <li><a class="cb-nav-link cb-nav-sell <?= _nb_active('sell',$active_page) ?>" href="<?= $base_url ?>sell.php"><i class="fas fa-tag"></i> Sell</a></li>
        <li class="cb-dropdown">
            <a class="cb-nav-link <?= _nb_active('contact',$active_page) ?>" href="<?= $base_url ?>contact.php">
                <i class="fas fa-envelope"></i> Contact <i class="fas fa-chevron-down cb-dropdown-arrow"></i>
            </a>
            <div class="cb-dropdown-menu">
                <a href="<?= $base_url ?>contact.php#contact"><i class="fas fa-headset"></i> Contact Us</a>
                <a href="<?= $base_url ?>contact.php#returns"><i class="fas fa-undo-alt"></i> Returns Policy</a>
                <a href="<?= $base_url ?>contact.php#warranty"><i class="fas fa-shield-alt"></i> Warranty</a>
                <div class="cb-dropdown-divider"></div>
                <a href="<?= $base_url ?>contact.php#faq"><i class="fas fa-question-circle"></i> FAQs</a>
                <a href="<?= $base_url ?>contact.php#privacy"><i class="fas fa-lock"></i> Privacy Policy</a>
            </div>
        </li>
    </ul>
    <div class="d-flex align-items-center gap-2">
        <?php if (isLoggedIn()): ?>
        <div class="d-none d-lg-flex align-items-center gap-2">
            <a href="<?= $base_url ?>wishlist.php" class="cb-icon-btn cb-icon-wishlist" title="Wishlist">
                <i class="fas fa-heart"></i>
                <span id="wl-nav-badge" class="cb-badge cb-badge-yellow" <?= $_nb_wl > 0 ? '' : 'style="display:none"' ?>><?= $_nb_wl ?></span>
            </a>
            <a href="<?= $base_url ?>cart.php" class="cb-icon-btn cb-icon-cart" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span id="cart-nav-badge" class="cb-badge cb-badge-red" <?= $_nb_cart > 0 ? '' : 'style="display:none"' ?>><?= $_nb_cart ?></span>
            </a>
            <a href="<?= $base_url ?>orders.php" class="cb-icon-btn cb-icon-orders" title="My Orders">
                <i class="fas fa-box"></i>
            </a>
            <div class="cb-nav-divider"></div>
            <?php if (isSeller()): ?>
            <a href="<?= $base_url ?>backend/seller/dashboard.php" class="cb-btn-dashboard">
                <i class="fas fa-store"></i> Dashboard
            </a>
            <?php endif; ?>
            <div class="cb-user-btn">
                <div class="cb-user-avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
                <span class="cb-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            </div>
            <a href="<?= $base_url ?>backend/auth/logout.php" class="cb-btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        <?php else: ?>
        <div class="d-none d-lg-flex align-items-center gap-2">
            <a href="<?= $base_url ?>login.php" class="cb-btn-signin"><i class="fas fa-sign-in-alt me-1"></i>Sign In</a>
            <a href="<?= $base_url ?>register.php" class="cb-btn-signup"><i class="fas fa-user-plus me-1"></i>Sign Up</a>
        </div>
        <?php endif; ?>
        <button class="cb-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#cbMobileNav">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<!-- Mobile Menu -->
<div class="collapse d-lg-none" id="cbMobileNav">
<div style="border-top:1px solid rgba(255,255,255,.1);padding:12px 0;">
    <ul class="navbar-nav gap-1 mb-0">
        <li><a class="cb-nav-link <?= _nb_active('home',$active_page) ?>" href="<?= $base_url ?>index.php#home"><i class="fas fa-home"></i> Home</a></li>
        <li><a class="cb-nav-link <?= _nb_active('cars',$active_page) ?>" href="<?= $base_url ?>index.php#cars"><i class="fas fa-car"></i> Cars</a></li>
        <li><a class="cb-nav-link <?= _nb_active('parts',$active_page) ?>" href="<?= $base_url ?>index.php#products"><i class="fas fa-cogs"></i> Spare Parts</a></li>
        <li><a class="cb-nav-link <?= _nb_active('categories',$active_page) ?>" href="<?= $base_url ?>index.php#categories"><i class="fas fa-th-large"></i> Categories</a></li>
        <li><a class="cb-nav-link cb-nav-sell <?= _nb_active('sell',$active_page) ?>" href="<?= $base_url ?>sell.php"><i class="fas fa-tag"></i> Sell</a></li>
        <li>
            <a class="cb-nav-link <?= _nb_active('contact',$active_page) ?>" href="<?= $base_url ?>contact.php"><i class="fas fa-envelope"></i> Contact</a>
            <div class="cb-mobile-sub">
                <a class="cb-nav-link" href="<?= $base_url ?>contact.php#contact"><i class="fas fa-headset me-1"></i> Contact Us</a>
                <a class="cb-nav-link" href="<?= $base_url ?>contact.php#returns"><i class="fas fa-undo-alt me-1"></i> Returns Policy</a>
                <a class="cb-nav-link" href="<?= $base_url ?>contact.php#warranty"><i class="fas fa-shield-alt me-1"></i> Warranty</a>
                <a class="cb-nav-link" href="<?= $base_url ?>contact.php#faq"><i class="fas fa-question-circle me-1"></i> FAQs</a>
                <a class="cb-nav-link" href="<?= $base_url ?>contact.php#privacy"><i class="fas fa-lock me-1"></i> Privacy Policy</a>
            </div>
        </li>
    </ul>
    <div class="cb-mobile-actions">
        <?php if (isLoggedIn()): ?>
            <a href="<?= $base_url ?>wishlist.php" class="cb-icon-btn cb-icon-wishlist">
                <i class="fas fa-heart"></i>
                <?php if ($_nb_wl > 0): ?><span class="cb-badge cb-badge-yellow"><?= $_nb_wl ?></span><?php endif; ?>
            </a>
            <a href="<?= $base_url ?>cart.php" class="cb-icon-btn cb-icon-cart">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($_nb_cart > 0): ?><span class="cb-badge cb-badge-red"><?= $_nb_cart ?></span><?php endif; ?>
            </a>
            <a href="<?= $base_url ?>orders.php" class="cb-icon-btn cb-icon-orders"><i class="fas fa-box"></i></a>
            <?php if (isSeller()): ?>
            <a href="<?= $base_url ?>backend/seller/dashboard.php" class="cb-btn-dashboard"><i class="fas fa-store"></i> Dashboard</a>
            <?php endif; ?>
            <a href="<?= $base_url ?>backend/auth/logout.php" class="cb-btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="<?= $base_url ?>login.php" class="cb-btn-signin"><i class="fas fa-sign-in-alt me-1"></i>Sign In</a>
            <a href="<?= $base_url ?>register.php" class="cb-btn-signup"><i class="fas fa-user-plus me-1"></i>Sign Up</a>
        <?php endif; ?>
    </div>
</div>
</div>

</div>
</nav>
