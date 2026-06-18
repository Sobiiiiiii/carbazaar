<?php
require_once '../config/db.php';

// ============================================================
// Auth Check
// ============================================================
if (!isLoggedIn() || !isSeller()) {
    header('Location: ../../login.php');
    exit;
}

// Determine listing type: 'car' or 'parts'
$listing_type = isset($_GET['type']) && $_GET['type'] === 'car' ? 'car' : 'parts';

$success = '';
$error   = '';

// ============================================================
// POST Handler
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['listing_type'] ?? 'parts');

    if ($type === 'car') {
        // ---- CAR LISTING ----
        $title       = trim($_POST['title']       ?? '');
        $brand       = trim($_POST['brand']       ?? '');
        $model       = trim($_POST['model']       ?? '');
        $year        = intval($_POST['year']      ?? 0);
        $price       = floatval($_POST['price']   ?? 0);
        $mileage     = intval($_POST['mileage']   ?? 0);
        $fuel_type   = trim($_POST['fuel_type']   ?? 'petrol');
        $transmission= trim($_POST['transmission']?? 'manual');
        $condition   = trim($_POST['condition_type'] ?? 'good');
        $color       = trim($_POST['color']       ?? '');
        $city        = trim($_POST['city']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $seller_id   = $_SESSION['user_id'];

        // Validation
        if (empty($title) || empty($brand) || empty($model) || $year < 1970 || $price <= 0 || empty($city)) {
            $error = 'Please fill all required fields correctly.';
        } else {
            // Image Upload
            $image = 'default.jpg';
            if (!empty($_FILES['image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Only JPG, PNG, WEBP images are allowed.';
                } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
                    $error = 'Image size must be under 3MB.';
                } else {
                    $filename    = 'car_' . uniqid() . '.' . $ext;
                    $upload_path = UPLOADS_DIR . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $filename;
                    } else {
                        $error = 'Failed to upload image. Check uploads/ folder permissions.';
                    }
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("
                    INSERT INTO cars
                        (seller_id, title, brand, model, year, price, mileage,
                         fuel_type, transmission, condition_type, color, city, description, image)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "issiidisssssss",
                    $seller_id, $title, $brand, $model, $year, $price, $mileage,
                    $fuel_type, $transmission, $condition, $color, $city, $description, $image
                );

                if ($stmt->execute()) {
                    $success = 'Car listed successfully! Buyers can now see your listing.';
                } else {
                    error_log('Car insert error: ' . $stmt->error);
                    $error = 'Failed to save listing. Please try again.';
                }
                $stmt->close();
            }
        }

    } else {
        // ---- SPARE PARTS LISTING ----
        $name           = trim($_POST['name']           ?? '');
        $description    = trim($_POST['description']    ?? '');
        $price          = floatval($_POST['price']      ?? 0);
        $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
        $stock          = intval($_POST['stock']        ?? 0);
        $brand          = trim($_POST['brand']          ?? '');
        $category_id    = intval($_POST['category_id']  ?? 0);
        $seller_id      = $_SESSION['user_id'];

        if (empty($name) || $price <= 0 || $stock < 0 || $category_id <= 0) {
            $error = 'Please fill all required fields correctly.';
        } else {
            // Image Upload
            $image = 'default.jpg';
            if (!empty($_FILES['image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Only JPG, PNG, WEBP images are allowed.';
                } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                    $error = 'Image size must be under 2MB.';
                } else {
                    $filename    = 'product_' . uniqid() . '.' . $ext;
                    $upload_path = UPLOADS_DIR . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image = $filename;
                    } else {
                        $error = 'Failed to upload image. Check uploads/ folder permissions.';
                    }
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("
                    INSERT INTO products
                        (seller_id, category_id, name, description, price, discount_price, stock, brand, image)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iissdsdss",
                    $seller_id, $category_id, $name, $description,
                    $price, $discount_price, $stock, $brand, $image
                );

                if ($stmt->execute()) {
                    $success = 'Product added successfully!';
                } else {
                    error_log('Product insert error: ' . $stmt->error);
                    $error = 'Failed to add product. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}

// ============================================================
// Fetch categories for parts form
// ============================================================
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$current_year = (int) date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Listing - CarBazar Seller</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .sidebar { background: #2c3e50; color: white; min-height: 100vh; }
        .sidebar a:hover { color: #f0c040 !important; }
        .image-preview { width: 100%; max-height: 220px; object-fit: cover; border-radius: 10px; display: none; border: 2px solid #dee2e6; }
        .tab-btn { border: 2px solid #dee2e6; background: #fff; color: #333; padding: 10px 28px; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all .25s; }
        .tab-btn.active { background: #f0c040; border-color: #f0c040; color: #1a1a2e; }
        .required-star { color: #dc3545; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">

        <!-- ===== SIDEBAR ===== -->
        <div class="col-md-3 col-lg-2 sidebar p-4">
            <h5 class="mb-4 fw-bold"><i class="fas fa-store text-warning me-2"></i>Seller Panel</h5>
            <ul class="list-unstyled">
                <li class="mb-3"><a href="dashboard.php" class="text-white text-decoration-none"><i class="fas fa-chart-line me-2"></i>Dashboard</a></li>
                <li class="mb-3"><a href="products.php" class="text-white text-decoration-none"><i class="fas fa-box me-2"></i>My Products</a></li>
                <li class="mb-3"><a href="add-product.php" class="text-warning text-decoration-none fw-bold"><i class="fas fa-plus me-2"></i>Add Listing</a></li>
                <li class="mb-3"><a href="orders.php" class="text-white text-decoration-none"><i class="fas fa-shopping-bag me-2"></i>Orders</a></li>
                <li class="mb-3"><a href="settings.php" class="text-white text-decoration-none"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li class="mt-4"><a href="../auth/logout.php" class="text-white text-decoration-none"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>

        <!-- ===== MAIN CONTENT ===== -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-bold mb-0"><i class="fas fa-plus-circle text-warning me-2"></i>Add New Listing</h3>
                <a href="../../index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-home me-1"></i>Back to Site
                </a>
            </div>
            <hr>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Type Tabs -->
            <div class="d-flex gap-3 mb-4">
                <button class="tab-btn <?= $listing_type === 'car' ? 'active' : '' ?>" onclick="switchTab('car')">
                    <i class="fas fa-car me-2"></i>Sell a Car
                </button>
                <button class="tab-btn <?= $listing_type === 'parts' ? 'active' : '' ?>" onclick="switchTab('parts')">
                    <i class="fas fa-cogs me-2"></i>Sell Spare Parts
                </button>
            </div>

            <!-- ============================= -->
            <!-- CAR LISTING FORM             -->
            <!-- ============================= -->
            <div id="carForm" style="display: <?= $listing_type === 'car' ? 'block' : 'none' ?>;">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold">
                        <i class="fas fa-car me-2"></i>Car Details
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="carFormEl">
                            <input type="hidden" name="listing_type" value="car">
                            <div class="row g-3">

                                <!-- Title -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Ad Title <span class="required-star">*</span></label>
                                    <input type="text" class="form-control" name="title" required
                                           placeholder="e.g. Toyota Corolla 2020 — Excellent Condition"
                                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                                    <small class="text-muted">Write a clear, descriptive title for your ad.</small>
                                </div>

                                <!-- Brand & Model -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Brand <span class="required-star">*</span></label>
                                    <select class="form-select" name="brand" required>
                                        <option value="">Select Brand</option>
                                        <?php
                                        $brands = ['Toyota','Honda','Suzuki','Hyundai','Kia','Daihatsu','Nissan','Mitsubishi','BMW','Mercedes','Audi','Other'];
                                        foreach ($brands as $b):
                                            $sel = (($_POST['brand'] ?? '') === $b) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $b ?>" <?= $sel ?>><?= $b ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Model <span class="required-star">*</span></label>
                                    <input type="text" class="form-control" name="model" required
                                           placeholder="e.g. Corolla, Civic, Alto"
                                           value="<?= htmlspecialchars($_POST['model'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Year <span class="required-star">*</span></label>
                                    <select class="form-select" name="year" required>
                                        <option value="">Select Year</option>
                                        <?php for ($y = $current_year; $y >= 1990; $y--):
                                            $sel = (intval($_POST['year'] ?? 0) === $y) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <!-- Price & City -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Price (PKR) <span class="required-star">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">PKR</span>
                                        <input type="number" class="form-control" name="price" min="1" step="1" required
                                               placeholder="e.g. 2800000"
                                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">City <span class="required-star">*</span></label>
                                    <select class="form-select" name="city" required>
                                        <option value="">Select City</option>
                                        <?php
                                        $cities = ['Karachi','Lahore','Islamabad','Rawalpindi','Peshawar','Quetta','Multan','Faisalabad','Sialkot','Hyderabad','Other'];
                                        foreach ($cities as $c):
                                            $sel = (($_POST['city'] ?? '') === $c) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $c ?>" <?= $sel ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Mileage (km)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="mileage" min="0" step="1"
                                               placeholder="e.g. 45000"
                                               value="<?= htmlspecialchars($_POST['mileage'] ?? '') ?>">
                                        <span class="input-group-text">km</span>
                                    </div>
                                </div>

                                <!-- Fuel, Transmission, Condition -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fuel Type</label>
                                    <select class="form-select" name="fuel_type">
                                        <?php
                                        $fuels = ['petrol'=>'Petrol','diesel'=>'Diesel','hybrid'=>'Hybrid','electric'=>'Electric','cng'=>'CNG'];
                                        foreach ($fuels as $val => $label):
                                            $sel = (($_POST['fuel_type'] ?? 'petrol') === $val) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Transmission</label>
                                    <select class="form-select" name="transmission">
                                        <option value="manual"    <?= (($_POST['transmission'] ?? '') === 'manual')    ? 'selected' : '' ?>>Manual</option>
                                        <option value="automatic" <?= (($_POST['transmission'] ?? '') === 'automatic') ? 'selected' : '' ?>>Automatic</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Condition</label>
                                    <select class="form-select" name="condition_type">
                                        <?php
                                        $conditions = ['excellent'=>'Excellent','good'=>'Good','fair'=>'Fair','needs_repair'=>'Needs Repair'];
                                        foreach ($conditions as $val => $label):
                                            $sel = (($_POST['condition_type'] ?? 'good') === $val) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Color -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Color</label>
                                    <input type="text" class="form-control" name="color"
                                           placeholder="e.g. White, Silver, Black"
                                           value="<?= htmlspecialchars($_POST['color'] ?? '') ?>">
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control" name="description" rows="4"
                                              placeholder="Describe your car — condition, features, reason for selling..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <!-- Image Upload -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Car Photo</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" id="carImageInput">
                                    <small class="text-muted">Max 3MB. JPG, PNG, WEBP allowed. Clear photo gets more buyers!</small>
                                    <img id="carImagePreview" class="image-preview mt-2" alt="Preview">
                                </div>

                            </div><!-- /row -->

                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-warning fw-bold px-5">
                                    <i class="fas fa-car me-2"></i>Post Car Ad
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div><!-- /carForm -->

            <!-- ============================= -->
            <!-- SPARE PARTS FORM             -->
            <!-- ============================= -->
            <div id="partsForm" style="display: <?= $listing_type === 'parts' ? 'block' : 'none' ?>;">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="fas fa-cogs me-2"></i>Spare Part Details
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data" id="partsFormEl">
                            <input type="hidden" name="listing_type" value="parts">
                            <div class="row g-3">

                                <!-- Name & Brand -->
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Product Name <span class="required-star">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                           placeholder="e.g. Toyota Corolla Air Filter"
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Brand</label>
                                    <input type="text" class="form-control" name="brand"
                                           placeholder="e.g. Bosch, Denso"
                                           value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>">
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control" name="description" rows="3"
                                              placeholder="Describe the part — compatibility, condition, specs..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                </div>

                                <!-- Category, Price, Discount, Stock -->
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Category <span class="required-star">*</span></label>
                                    <select class="form-select" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat):
                                            $sel = (intval($_POST['category_id'] ?? 0) === $cat['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $cat['id'] ?>" <?= $sel ?>><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Price (PKR) <span class="required-star">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">PKR</span>
                                        <input type="number" class="form-control" name="price" min="1" step="0.01" required
                                               placeholder="0.00"
                                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Discount Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">PKR</span>
                                        <input type="number" class="form-control" name="discount_price" min="1" step="0.01"
                                               placeholder="Optional"
                                               value="<?= htmlspecialchars($_POST['discount_price'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Stock Qty <span class="required-star">*</span></label>
                                    <input type="number" class="form-control" name="stock" min="0" required
                                           placeholder="0"
                                           value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
                                </div>

                                <!-- Image Upload -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Product Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*" id="partsImageInput">
                                    <small class="text-muted">Max 2MB. JPG, PNG, WEBP allowed.</small>
                                    <img id="partsImagePreview" class="image-preview mt-2" alt="Preview">
                                </div>

                            </div><!-- /row -->

                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary fw-bold px-5">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div><!-- /partsForm -->

        </div><!-- /main content -->
    </div><!-- /row -->
</div><!-- /container -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // ---- Tab Switcher ----
    function switchTab(type) {
        document.getElementById('carForm').style.display   = type === 'car'   ? 'block' : 'none';
        document.getElementById('partsForm').style.display = type === 'parts' ? 'block' : 'none';
        document.querySelectorAll('.tab-btn').forEach((btn, i) => {
            btn.classList.toggle('active', (i === 0 && type === 'car') || (i === 1 && type === 'parts'));
        });
    }

    // ---- Image Preview Helper ----
    function setupPreview(inputId, previewId) {
        document.getElementById(inputId).addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    setupPreview('carImageInput',   'carImagePreview');
    setupPreview('partsImageInput', 'partsImagePreview');
</script>
</body>
</html>
