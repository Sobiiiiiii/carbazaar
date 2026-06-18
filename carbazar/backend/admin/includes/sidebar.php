<?php
// Admin Sidebar - set $admin_page to highlight current page
if (!isset($admin_page)) $admin_page = '';
function adm_active($p, $cur) { return $p === $cur ? 'active' : ''; }
?>
<style>
:root { --gold: #f0c040; --navy: #1a1a2e; --navy2: #16213e; --sidebar-w: 250px; }
.adm-sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: linear-gradient(180deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
    border-right: 2px solid rgba(240,192,64,.2);
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 20px rgba(0,0,0,.4);
}
.adm-brand {
    padding: 20px 20px 16px;
    border-bottom: 1px solid rgba(240,192,64,.15);
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.adm-brand-icon {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, #f0c040, #e0a800);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #1a1a2e;
    box-shadow: 0 4px 12px rgba(240,192,64,.4);
    flex-shrink: 0;
}
.adm-brand-text { line-height: 1; }
.adm-brand-name { font-size: 1.1rem; font-weight: 800; color: #fff; display: block; }
.adm-brand-sub  { font-size: .6rem; color: rgba(240,192,64,.8); font-weight: 600;
                  letter-spacing: 2px; text-transform: uppercase; display: block; margin-top: 2px; }
.adm-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
.adm-nav-section { font-size: .65rem; font-weight: 700; color: rgba(255,255,255,.35);
                   letter-spacing: 2px; text-transform: uppercase;
                   padding: 12px 8px 6px; margin-top: 4px; }
.adm-nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    color: rgba(255,255,255,.7); text-decoration: none;
    font-size: .88rem; font-weight: 600;
    transition: all .2s; margin-bottom: 2px;
}
.adm-nav-link i { width: 18px; text-align: center; font-size: .9rem; }
.adm-nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
.adm-nav-link.active { background: rgba(240,192,64,.15); color: #f0c040;
                        border-left: 3px solid #f0c040; padding-left: 9px; }
.adm-nav-link .badge-count {
    margin-left: auto; background: #f0c040; color: #1a1a2e;
    font-size: .65rem; font-weight: 700; padding: 2px 7px;
    border-radius: 10px; min-width: 20px; text-align: center;
}
.adm-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,.08);
    font-size: .78rem; color: rgba(255,255,255,.4);
}
.adm-footer a { color: rgba(240,192,64,.7); text-decoration: none; }
.adm-footer a:hover { color: #f0c040; }
/* Main content offset */
.adm-main { margin-left: var(--sidebar-w); min-height: 100vh; background: #f4f6fb; }
/* Top bar */
.adm-topbar {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 14px 28px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    position: sticky; top: 0; z-index: 50;
}
.adm-topbar-title { font-size: 1.2rem; font-weight: 700; color: #1a1a2e; }
.adm-topbar-right { display: flex; align-items: center; gap: 14px; }
.adm-user-badge {
    display: flex; align-items: center; gap: 8px;
    background: rgba(240,192,64,.1); border: 1px solid rgba(240,192,64,.3);
    border-radius: 20px; padding: 6px 14px;
    font-size: .82rem; font-weight: 600; color: #1a1a2e;
}
.adm-user-badge i { color: #f0c040; }
.adm-logout-btn {
    background: #dc3545; color: #fff; border: none;
    padding: 7px 16px; border-radius: 8px;
    font-size: .82rem; font-weight: 600; cursor: pointer;
    text-decoration: none; transition: background .2s;
}
.adm-logout-btn:hover { background: #b02a37; color: #fff; }
/* Cards */
.stat-card {
    background: #fff; border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    border-left: 4px solid transparent;
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.stat-card .stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; margin-bottom: 12px;
}
.stat-card .stat-num { font-size: 1.8rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
.stat-card .stat-label { font-size: .82rem; color: #6b7280; font-weight: 500; margin-top: 4px; }
/* Table */
.adm-table { background: #fff; border-radius: 14px; overflow: hidden;
             box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.adm-table table { margin: 0; }
.adm-table thead th { background: #f8f9fa; font-weight: 700; font-size: .82rem;
                       color: #374151; border-bottom: 2px solid #e5e7eb; padding: 12px 16px; }
.adm-table tbody td { padding: 12px 16px; vertical-align: middle; font-size: .88rem; }
.adm-table tbody tr:hover { background: #fafbff; }
/* Status badges */
.badge-pending    { background: #fef3c7; color: #92400e; }
.badge-processing { background: #dbeafe; color: #1e40af; }
.badge-shipped    { background: #e0e7ff; color: #3730a3; }
.badge-delivered  { background: #d1fae5; color: #065f46; }
.badge-cancelled  { background: #fee2e2; color: #991b1b; }
.badge-active     { background: #d1fae5; color: #065f46; }
.badge-blocked    { background: #fee2e2; color: #991b1b; }
.badge-admin      { background: rgba(240,192,64,.2); color: #92400e; }
.badge-seller     { background: #dbeafe; color: #1e40af; }
.badge-buyer      { background: #f3f4f6; color: #374151; }
/* Page content */
.adm-content { padding: 28px; }
/* Responsive */
@media (max-width: 768px) {
    .adm-sidebar { transform: translateX(-100%); transition: transform .3s; }
    .adm-sidebar.open { transform: translateX(0); }
    .adm-main { margin-left: 0; }
}
</style>

<div class="adm-sidebar">
    <a href="<?= BASE_URL ?>backend/admin/dashboard.php" class="adm-brand">
        <div class="adm-brand-icon"><i class="fas fa-car"></i></div>
        <div class="adm-brand-text">
            <span class="adm-brand-name">CarBazar</span>
            <span class="adm-brand-sub">Admin Panel</span>
        </div>
    </a>

    <nav class="adm-nav">
        <div class="adm-nav-section">Main</div>
        <a href="<?= BASE_URL ?>backend/admin/dashboard.php"
           class="adm-nav-link <?= adm_active('dashboard', $admin_page) ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="adm-nav-section">Users</div>
        <a href="<?= BASE_URL ?>backend/admin/users.php"
           class="adm-nav-link <?= adm_active('users', $admin_page) ?>">
            <i class="fas fa-users"></i> All Users
        </a>

        <div class="adm-nav-section">Listings</div>
        <a href="<?= BASE_URL ?>backend/admin/cars.php"
           class="adm-nav-link <?= adm_active('cars', $admin_page) ?>">
            <i class="fas fa-car"></i> Cars
        </a>
        <a href="<?= BASE_URL ?>backend/admin/products.php"
           class="adm-nav-link <?= adm_active('products', $admin_page) ?>">
            <i class="fas fa-cogs"></i> Spare Parts
        </a>

        <div class="adm-nav-section">Commerce</div>
        <a href="<?= BASE_URL ?>backend/admin/orders.php"
           class="adm-nav-link <?= adm_active('orders', $admin_page) ?>">
            <i class="fas fa-shopping-bag"></i> Orders
        </a>
        <a href="<?= BASE_URL ?>backend/admin/transactions.php"
           class="adm-nav-link <?= adm_active('transactions', $admin_page) ?>">
            <i class="fas fa-money-bill-wave"></i> Transactions
        </a>
        <a href="<?= BASE_URL ?>backend/admin/categories.php"
           class="adm-nav-link <?= adm_active('categories', $admin_page) ?>">
            <i class="fas fa-tags"></i> Categories
        </a>

        <div class="adm-nav-section">Site</div>
        <a href="<?= BASE_URL ?>index.php" target="_blank"
           class="adm-nav-link">
            <i class="fas fa-external-link-alt"></i> View Website
        </a>
    </nav>

    <div class="adm-footer">
        Logged in as <strong style="color:#f0c040"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong><br>
        <a href="<?= BASE_URL ?>backend/auth/logout.php">Logout</a>
    </div>
</div>
