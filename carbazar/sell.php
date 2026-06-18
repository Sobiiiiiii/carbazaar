<?php
require_once 'backend/config/db.php';

$success_msg = "";
$error_msg   = "";
$active_tab  = "car";

// Fetch categories for parts form
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// POST: Sell a Car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'car') {
    $active_tab = "car";
    if (!isLoggedIn()) {
        $error_msg = "Please login first.";
    } else {
        $seller_id      = (int) $_SESSION['user_id'];
        $title          = trim($_POST['title']          ?? "");
        $brand          = trim($_POST['brand']          ?? "");
        $model          = trim($_POST['model']          ?? "");
        $year           = (int) ($_POST['year']         ?? 0);
        $price          = (float) ($_POST['price']      ?? 0);
        $mileage        = (int) ($_POST['mileage']      ?? 0);
        $fuel_type      = trim($_POST['fuel_type']      ?? "");
        $transmission   = trim($_POST['transmission']   ?? "");
        $condition_type = trim($_POST['condition_type'] ?? "");
        $color          = trim($_POST['color']          ?? "");
        $city           = trim($_POST['city']           ?? "");
        $description    = trim($_POST['description']    ?? "");
        if (!$title || !$brand || !$model || !$year || !$price || !$city) {
            $error_msg = "Please fill required fields: Title, Brand, Model, Year, Price, City.";
        } else {
            $image = "";
            if (!empty($_FILES['image']['name'])) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $ftype   = mime_content_type($_FILES['image']['tmp_name']);
                $fsize   = $_FILES['image']['size'];
                if (!in_array($ftype, $allowed)) {
                    $error_msg = "Only JPG, PNG, WEBP or GIF images are allowed.";
                } elseif ($fsize > 3 * 1024 * 1024) {
                    $error_msg = "Image size must not exceed 3MB.";
                } else {
                    $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'car_' . time() . '_' . mt_rand(1000, 9999) . '.' . strtolower($ext);
                    $dest     = UPLOADS_DIR . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $image = $filename;
                    } else {
                        $error_msg = "Image upload failed. Please check the uploads folder.";
                    }
                }
            }
            if (!$error_msg) {
                $stmt = $conn->prepare("INSERT INTO cars (seller_id, title, brand, model, year, price, mileage, fuel_type, transmission, condition_type, color, city, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issiidisssssss', $seller_id, $title, $brand, $model, $year, $price, $mileage, $fuel_type, $transmission, $condition_type, $color, $city, $description, $image);
                if ($stmt->execute()) { $success_msg = "Car listing posted successfully! Congratulations!"; }
                else { $error_msg = "Database error: " . htmlspecialchars($stmt->error); }
                $stmt->close();
            }
        }
    }
}

// POST: Sell Spare Parts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'part') {
    $active_tab = "part";
    if (!isLoggedIn()) {
        $error_msg = "Please login first.";
    } else {
        $seller_id      = (int) $_SESSION['user_id'];
        $category_id    = (int) ($_POST['category_id']    ?? 0);
        $name           = trim($_POST['name']             ?? "");
        $description    = trim($_POST['description']      ?? "");
        $price          = (float) ($_POST['price']        ?? 0);
        $discount_price = (float) ($_POST['discount_price'] ?? 0);
        $stock          = (int) ($_POST['stock']          ?? 0);
        $brand          = trim($_POST['brand']            ?? "");
        if (!$name || !$category_id || !$price || $stock < 0) {
            $error_msg = "Please fill required fields: Product Name, Category, Price, Stock.";
        } else {
            $image = "";
            if (!empty($_FILES['part_image']['name'])) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $ftype   = mime_content_type($_FILES['part_image']['tmp_name']);
                $fsize   = $_FILES['part_image']['size'];
                if (!in_array($ftype, $allowed)) {
                    $error_msg = "Only JPG, PNG, WEBP or GIF images are allowed.";
                } elseif ($fsize > 2 * 1024 * 1024) {
                    $error_msg = "Image size must not exceed 2MB.";
                } else {
                    $ext      = pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'part_' . time() . '_' . mt_rand(1000, 9999) . '.' . strtolower($ext);
                    $dest     = UPLOADS_DIR . $filename;
                    if (move_uploaded_file($_FILES['part_image']['tmp_name'], $dest)) {
                        $image = $filename;
                    } else {
                        $error_msg = "Image upload failed. Please check the uploads folder.";
                    }
                }
            }
            if (!$error_msg) {
                $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, name, description, price, discount_price, stock, brand, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('iissddiss', $seller_id, $category_id, $name, $description, $price, $discount_price, $stock, $brand, $image);
                if ($stmt->execute()) { $success_msg = "Spare part listing posted successfully! Congratulations!"; }
                else { $error_msg = "Database error: " . htmlspecialchars($stmt->error); }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell on CarBazar - Pakistan's #1 Auto Marketplace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        :root { --gold: #f0c040; --dark-navy: #1a1a2e; }
        body { font-family: 'Segoe UI', sans-serif; }
        .sell-hero { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); min-height: 220px; display: flex; align-items: center; }
        .tab-btn { border: 2px solid #dee2e6; background: #fff; color: #333; padding: 10px 32px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all .25s; font-size: 1rem; }
        .tab-btn.active-car { background: #ffc107; border-color: #ffc107; color: #1a1a2e; }
        .tab-btn.active-part { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .form-section { display: none; }
        .form-section.active { display: block; }
        .sell-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .img-preview-box { width: 100%; height: 180px; border: 2px dashed #dee2e6; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f8f9fa; cursor: pointer; transition: border-color .2s; }
        .img-preview-box:hover { border-color: #ffc107; }
        .img-preview-box img { width: 100%; height: 100%; object-fit: cover; }
        .login-prompt-card { background: linear-gradient(135deg, #1a1a2e, #0f3460); border-radius: 20px; color: #fff; }
        footer a:hover { color: var(--gold) !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
        .required-star { color: #dc3545; }
        .section-badge { background: rgba(240,192,64,.15); color: #f0c040; border: 1px solid rgba(240,192,64,.3); border-radius: 20px; padding: 4px 14px; font-size: .78rem; font-weight: 600; letter-spacing: 1px; display: inline-block; margin-bottom: 8px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<?php $active_page = 'sell'; require_once 'includes/navbar.php'; ?>

<!-- HERO SECTION -->
<section class="sell-hero text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="section-badge"><i class="fas fa-tag me-1"></i>SELL ON CARBAZAR</span>
                <h1 class="display-5 fw-bold mb-2">Sell on <span style="color:#f0c040">CarBazar</span></h1>
                <p class="lead mb-0" style="opacity:.8">List your car or spare parts on Pakistan's largest auto marketplace. Reach millions of buyers!</p>
            </div>
            <div class="col-lg-4 text-end d-none d-lg-block">
                <i class="fas fa-car-side fa-5x" style="color:rgba(240,192,64,.3)"></i>
            </div>
        </div>
    </div>
</section>

<!-- MAIN CONTENT -->
<section class="py-5" style="background:#f8f9fa; min-height: 60vh;">
    <div class="container">

        <!-- ALERTS -->
        <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle fa-lg"></i>
            <div><?= htmlspecialchars($success_msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div><?= htmlspecialchars($error_msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
        <!-- NOT LOGGED IN: Show login prompt -->
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="login-prompt-card p-5 text-center shadow-lg">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x" style="color:#f0c040"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Login Required</h3>
                    <p class="mb-4" style="opacity:.8">Please login or create an account to sell your car or spare parts on CarBazar.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="login.php" class="btn btn-warning btn-lg fw-bold px-5">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                    <p class="mt-4 mb-0 small" style="opacity:.6">Login or register — it's completely free!</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- TAB BUTTONS -->
        <div class="d-flex justify-content-center gap-3 mb-4 flex-wrap">
            <button class="tab-btn <?= $active_tab === 'car' ? 'active-car' : '' ?>" id="btnCar" onclick="switchTab('car')">
                <i class="fas fa-car me-2"></i>Sell a Car
            </button>
            <button class="tab-btn <?= $active_tab === 'part' ? 'active-part' : '' ?>" id="btnPart" onclick="switchTab('part')">
                <i class="fas fa-cogs me-2"></i>Sell Spare Parts
            </button>
        </div>

        <!-- ===== CAR FORM ===== -->
        <div class="form-section <?= $active_tab === 'car' ? 'active' : '' ?>" id="sectionCar">
            <div class="sell-card p-4 p-lg-5">
                <div class="mb-4">
                    <span class="section-badge"><i class="fas fa-car me-1"></i>CAR LISTING</span>
                    <h4 class="fw-bold mb-1">Sell Your Car</h4>
                    <p class="text-muted mb-0">Fill the form below to list your car. <span class="required-star">*</span> fields are required.</p>
                </div>
                <form method="POST" enctype="multipart/form-data" id="carForm">
                    <input type="hidden" name="form_type" value="car">
                    <div class="row g-3">

                        <!-- Ad Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Ad Title <span class="required-star">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="title" placeholder="e.g. Toyota Corolla 2020 - Excellent Condition" required maxlength="200" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>

                        <!-- Brand -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Brand <span class="required-star">*</span></label>
                            <select class="form-select form-select-lg" name="brand" required>
                                <option value="">-- Select Brand --</option>
                                <?php $brands = ['Toyota','Honda','Suzuki','Hyundai','Kia','Daihatsu','Nissan','Mitsubishi','BMW','Mercedes','Audi','Other']; foreach ($brands as $b): ?>
                                <option value="<?= $b ?>" <?= (($_POST['brand'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Model -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Model <span class="required-star">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="model" placeholder="e.g. Corolla, Civic, Alto" required maxlength="100" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">
                        </div>

                        <!-- Year -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Year <span class="required-star">*</span></label>
                            <select class="form-select form-select-lg" name="year" required>
                                <option value="">-- Year --</option>
                                <?php for ($y = 2026; $y >= 1990; $y--): ?>
                                <option value="<?= $y ?>" <?= (($_POST['year'] ?? '') == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Price -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price (PKR) <span class="required-star">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">PKR</span>
                                <input type="number" class="form-control" name="price" placeholder="e.g. 2500000" required min="1" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- City -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">City <span class="required-star">*</span></label>
                            <select class="form-select form-select-lg" name="city" required>
                                <option value="">-- Select City --</option>
                                <?php $cities = ['Karachi','Lahore','Islamabad','Rawalpindi','Peshawar','Quetta','Multan','Faisalabad','Other']; foreach ($cities as $c): ?>
                                <option value="<?= $c ?>" <?= (($_POST['city'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mileage -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mileage (km)</label>
                            <div class="input-group input-group-lg">
                                <input type="number" class="form-control" name="mileage" placeholder="e.g. 45000" min="0" value="<?= htmlspecialchars($_POST['mileage'] ?? '') ?>">
                                <span class="input-group-text">km</span>
                            </div>
                        </div>

                        <!-- Fuel Type -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Fuel Type</label>
                            <select class="form-select form-select-lg" name="fuel_type">
                                <option value="">-- Select --</option>
                                <option value="petrol" <?= (($_POST['fuel_type'] ?? '') === 'petrol') ? 'selected' : '' ?>>Petrol</option>
                                <option value="diesel" <?= (($_POST['fuel_type'] ?? '') === 'diesel') ? 'selected' : '' ?>>Diesel</option>
                                <option value="hybrid" <?= (($_POST['fuel_type'] ?? '') === 'hybrid') ? 'selected' : '' ?>>Hybrid</option>
                                <option value="electric" <?= (($_POST['fuel_type'] ?? '') === 'electric') ? 'selected' : '' ?>>Electric</option>
                                <option value="cng" <?= (($_POST['fuel_type'] ?? '') === 'cng') ? 'selected' : '' ?>>CNG</option>
                            </select>
                        </div>

                        <!-- Transmission -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Transmission</label>
                            <select class="form-select form-select-lg" name="transmission">
                                <option value="">-- Select --</option>
                                <option value="manual" <?= (($_POST['transmission'] ?? '') === 'manual') ? 'selected' : '' ?>>Manual</option>
                                <option value="automatic" <?= (($_POST['transmission'] ?? '') === 'automatic') ? 'selected' : '' ?>>Automatic</option>
                            </select>
                        </div>

                        <!-- Condition -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Condition</label>
                            <select class="form-select form-select-lg" name="condition_type">
                                <option value="">-- Select --</option>
                                <option value="excellent" <?= (($_POST['condition_type'] ?? '') === 'excellent') ? 'selected' : '' ?>>Excellent</option>
                                <option value="good" <?= (($_POST['condition_type'] ?? '') === 'good') ? 'selected' : '' ?>>Good</option>
                                <option value="fair" <?= (($_POST['condition_type'] ?? '') === 'fair') ? 'selected' : '' ?>>Fair</option>
                                <option value="needs_repair" <?= (($_POST['condition_type'] ?? '') === 'needs_repair') ? 'selected' : '' ?>>Needs Repair</option>
                            </select>
                        </div>

                        <!-- Color -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Color</label>
                            <input type="text" class="form-control form-control-lg" name="color" placeholder="e.g. White, Silver, Black" maxlength="50" value="<?= htmlspecialchars($_POST['color'] ?? '') ?>">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="4" placeholder="Describe your car: features, accessories, any special details..." maxlength="2000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <div class="form-text">Max 2000 characters</div>
                        </div>

                        <!-- Image Upload -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Car Image (Max 3MB)</label>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <input type="file" class="form-control form-control-lg" name="image" id="carImageInput" accept="image/*" onchange="previewImage(this, 'carImgPreview')">
                                    <div class="form-text">JPG, PNG, WEBP ya GIF. Max size: 3MB</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="img-preview-box" id="carImgPreview" onclick="document.getElementById('carImageInput').click()">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-image fa-2x mb-2 d-block"></i>
                                            <small>Image preview yahan dikhegi</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-warning btn-lg fw-bold px-5">
                                <i class="fas fa-upload me-2"></i>Post Car Listing
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg ms-2">Cancel</a>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <!-- PARTS FORM -->
        <div class="form-section <?= $active_tab === 'part' ? 'active' : '' ?>" id="sectionPart">
            <div class="sell-card p-4 p-lg-5">
                <div class="mb-4">
                    <span class="section-badge"><i class="fas fa-cogs me-1"></i>SPARE PARTS LISTING</span>
                    <h4 class="fw-bold mb-1">Sell Spare Parts</h4>
                    <p class="text-muted mb-0">List your spare parts. <span class="required-star">*</span> fields are required.</p>
                </div>
                <form method="POST" enctype="multipart/form-data" id="partForm">
                    <input type="hidden" name="form_type" value="part">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product Name <span class="required-star">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="name" placeholder="e.g. Air Filter, Brake Pads" required maxlength="200" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand</label>
                            <input type="text" class="form-control form-control-lg" name="brand" placeholder="e.g. Denso, Bosch, NGK" maxlength="100" value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category <span class="required-star">*</span></label>
                            <select class="form-select form-select-lg" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; else: ?>
                                <option value="1">Engine Parts</option>
                                <option value="2">Electrical</option>
                                <option value="3">Brakes</option>
                                <option value="4">Cooling System</option>
                                <option value="5">Suspension</option>
                                <option value="6">Body Parts</option>
                                <option value="7">Filters</option>
                                <option value="8">Tyres and Wheels</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Price (PKR) <span class="required-star">*</span></label>
                            <div class="input-group input-group-lg"><span class="input-group-text">PKR</span><input type="number" class="form-control" name="price" placeholder="e.g. 2500" required min="1" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Discount Price (PKR)</label>
                            <div class="input-group input-group-lg"><span class="input-group-text">PKR</span><input type="number" class="form-control" name="discount_price" placeholder="Optional" min="0" step="0.01" value="<?= htmlspecialchars($_POST['discount_price'] ?? '') ?>"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Stock <span class="required-star">*</span></label>
                            <input type="number" class="form-control form-control-lg" name="stock" placeholder="e.g. 10" required min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="4" placeholder="Part ki details: compatibility, condition, specs..." maxlength="2000"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Part Image (Max 2MB)</label>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-6"><input type="file" class="form-control form-control-lg" name="part_image" id="partImageInput" accept="image/*" onchange="previewImage(this, 'partImgPreview')"><div class="form-text">JPG, PNG, WEBP ya GIF. Max: 2MB</div></div>
                                <div class="col-md-6"><div class="img-preview-box" id="partImgPreview" onclick="document.getElementById('partImageInput').click()"><div class="text-center text-muted"><i class="fas fa-image fa-2x mb-2 d-block"></i><small>Image preview yahan dikhegi</small></div></div></div>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold px-5"><i class="fas fa-upload me-2"></i>Post Part Listing</button>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; // end isLoggedIn ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="cb-footer">
    <div class="cb-footer-topbar"></div>
    <div class="cb-footer-body">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="cb-brand"><span class="cb-brand-icon"><i class="fas fa-car"></i></span><span class="cb-brand-name">CarBazar</span></div>
                    <p class="cb-desc">Pakistan's trusted marketplace for used cars and genuine spare parts. Best prices, verified sellers.</p>
                    <div class="cb-socials">
                        <a href="#" class="cb-soc cb-fb" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="cb-soc cb-tw" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="cb-soc cb-ig" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="cb-soc cb-yt" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="cb-soc cb-wa" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="cb-heading">Quick Links</h6>
                    <ul class="cb-links">
                        <li><a href="index.php#home">Home</a></li>
                        <li><a href="index.php#cars">Cars For Sale</a></li>
                        <li><a href="index.php#products">Spare Parts</a></li>
                        <li><a href="index.php#categories">Categories</a></li>
                        <li><a href="sell.php">Sell on CarBazar</a></li>
                        <li><a href="#">About Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="cb-heading">Support</h6>
                    <ul class="cb-links">
                        <li><a href="index.php#contact">Contact Us</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Returns Policy</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-5 col-md-6">
                    <h6 class="cb-heading">Get In Touch</h6>
                    <div class="cb-contacts">
                        <div class="cb-contact"><i class="fas fa-map-marker-alt"></i><div><b>Address</b><span>123 Car Lane, Auto City, Vehari, Pakistan</span></div></div>
                        <div class="cb-contact"><i class="fas fa-phone-alt"></i><div><b>Phone</b><span>+92 304 0369392 | +92 304 0369394</span></div></div>
                        <div class="cb-contact"><i class="fas fa-envelope"></i><div><b>Email</b><span>support@carbazar.com | seller@carbazar.com</span></div></div>
                        <div class="cb-contact"><i class="fas fa-clock"></i><div><b>Hours</b><span>Mon-Sat: 9AM-9PM | Sunday: 10AM-6PM</span></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="cb-footer-bottom">
        <div class="container">
            <div class="cb-bottom-inner">
                <p>&copy; 2026 <strong>CarBazar</strong>. All rights reserved. <a href="#">Privacy</a> &middot; <a href="#">Terms</a> &middot; <a href="#">Sitemap</a></p>
                <a href="#" class="cb-top-btn" title="Back to top"><i class="fas fa-arrow-up"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
// Tab switching
function switchTab(tab) {
    var carSection  = document.getElementById('sectionCar');
    var partSection = document.getElementById('sectionPart');
    var btnCar      = document.getElementById('btnCar');
    var btnPart     = document.getElementById('btnPart');
    if (tab === 'car') {
        carSection.classList.add('active');
        partSection.classList.remove('active');
        btnCar.classList.add('active-car');
        btnCar.classList.remove('active-part');
        btnPart.classList.remove('active-car', 'active-part');
    } else {
        partSection.classList.add('active');
        carSection.classList.remove('active');
        btnPart.classList.add('active-part');
        btnPart.classList.remove('active-car');
        btnCar.classList.remove('active-car', 'active-part');
    }
}

// Image preview using FileReader
function previewImage(input, previewId) {
    var preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '<div class="text-center text-muted"><i class="fas fa-image fa-2x mb-2 d-block"></i><small>Image preview yahan dikhegi</small></div>';
    }
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
});
</script>

</body>
</html>
