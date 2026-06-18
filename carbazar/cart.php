<?php
require_once 'backend/config/db.php';

$cart_items = [];
$subtotal   = 0;
$item_count = 0;
$success    = '';
$error      = '';
$order_id   = 0;
$cart_count = 0;

if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];

    // POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'remove') {
            $cid = (int)($_POST['cart_id'] ?? 0);
            $st  = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
            $st->bind_param("ii", $cid, $uid);
            $st->execute(); $st->close();

        } elseif ($action === 'update_qty') {
            $cid = (int)($_POST['cart_id'] ?? 0);
            $qty = (int)($_POST['quantity'] ?? 1);
            if ($qty < 1) $qty = 1;
            $st = $conn->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id=p.id WHERE c.id=? AND c.user_id=?");
            $st->bind_param("ii", $cid, $uid); $st->execute();
            $row = $st->get_result()->fetch_assoc(); $st->close();
            if ($row && $qty <= (int)$row['stock']) {
                $st = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
                $st->bind_param("iii", $qty, $cid, $uid); $st->execute(); $st->close();
            } else {
                $error = 'Insufficient stock available.';
            }

        } elseif ($action === 'clear') {
            $st = $conn->prepare("DELETE FROM cart WHERE user_id=?");
            $st->bind_param("i", $uid); $st->execute(); $st->close();

        } elseif ($action === 'checkout') {
            $address = trim($_POST['shipping_address'] ?? '');
            $payment = in_array($_POST['payment_method'] ?? '', ['cod','online'])
                       ? $_POST['payment_method'] : 'cod';
            if (empty($address)) {
                $error = 'Shipping address is required.';
            } else {
                $st = $conn->prepare("SELECT c.product_id,c.quantity,p.price,p.discount_price,p.seller_id FROM cart c JOIN products p ON c.product_id=p.id WHERE c.user_id=?");
                $st->bind_param("i", $uid); $st->execute();
                $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
                if (empty($rows)) {
                    $error = 'Your cart is empty.';
                } else {
                    $total = 0; $items = [];
                    foreach ($rows as $r) {
                        $pr = $r['discount_price'] ? (float)$r['discount_price'] : (float)$r['price'];
                        $total += $pr * $r['quantity'];
                        $items[] = array_merge($r, ['unit_price' => $pr]);
                    }
                    $conn->begin_transaction();
                    try {
                        $st = $conn->prepare("INSERT INTO orders(user_id,total_amount,shipping_address,payment_method)VALUES(?,?,?,?)");
                        $st->bind_param("idss", $uid, $total, $address, $payment);
                        $st->execute(); $order_id = $conn->insert_id; $st->close();
                        $st = $conn->prepare("INSERT INTO order_items(order_id,product_id,seller_id,quantity,price)VALUES(?,?,?,?,?)");
                        $su = $conn->prepare("UPDATE products SET stock=stock-? WHERE id=?");
                        foreach ($items as $it) {
                            $st->bind_param("iiiid", $order_id, $it['product_id'], $it['seller_id'], $it['quantity'], $it['unit_price']);
                            $st->execute();
                            $su->bind_param("ii", $it['quantity'], $it['product_id']);
                            $su->execute();
                        }
                        $st->close(); $su->close();
                        $st = $conn->prepare("DELETE FROM cart WHERE user_id=?");
                        $st->bind_param("i", $uid); $st->execute(); $st->close();
                        $conn->commit();
                        $success = 'Order placed successfully! Order #' . $order_id . ' confirmed. Thank you!';
                    } catch (Exception $e) {
                        $conn->rollback();
                        error_log('Order error: ' . $e->getMessage());
                        $error = 'Order failed. Please try again.';
                    }
                }
            }
        }
    }

    // Fetch cart items
    if (empty($success)) {
        $st = $conn->prepare("
            SELECT c.id AS cart_id, c.quantity,
                   p.id AS product_id, p.name, p.price, p.discount_price,
                   p.image, p.stock, p.brand,
                   cat.name AS cat_name
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC
        ");
        $st->bind_param("i", $uid); $st->execute();
        $cart_items = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
        foreach ($cart_items as $ci) {
            $pr = $ci['discount_price'] ? (float)$ci['discount_price'] : (float)$ci['price'];
            $subtotal += $pr * $ci['quantity'];
        }
        $item_count = count($cart_items);
    }

    // Cart badge count
    $st2 = $conn->prepare("SELECT COUNT(*) AS c FROM cart WHERE user_id=?");
    $st2->bind_param("i", $uid); $st2->execute();
    $cart_count = (int)($st2->get_result()->fetch_assoc()['c'] ?? 0);
    $st2->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart - CarBazar</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root { --gold: #f0c040; --dark-navy: #1a1a2e; }
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }

/* Cart Item Card */
.cart-item-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 20px; margin-bottom: 14px;
    transition: box-shadow .2s;
}
.cart-item-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); }
.cart-img {
    width: 85px; height: 85px; object-fit: cover;
    border-radius: 10px; flex-shrink: 0;
    border: 2px solid #f1f5f9;
}

/* Quantity Controls */
.qty-wrap { display: flex; align-items: center; gap: 6px; }
.qty-btn {
    width: 36px; height: 36px;
    border: 1.5px solid #dee2e6; background: #fff;
    border-radius: 8px; font-weight: 700; font-size: 1.1rem;
    cursor: pointer; transition: all .15s;
    display: flex; align-items: center; justify-content: center;
}
.qty-btn:hover { background: var(--gold); border-color: var(--gold); }
.qty-input {
    width: 52px; height: 36px; text-align: center;
    border: 1.5px solid #dee2e6; border-radius: 8px;
    font-weight: 700; font-size: .95rem; padding: 0;
}
.qty-input:focus { outline: none; border-color: var(--gold); }

/* Summary Card */
.summary-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    padding: 28px; position: sticky; top: 80px;
}
.total-price { font-size: 1.9rem; font-weight: 800; color: var(--gold); }

/* Buttons */
.remove-btn {
    background: none; border: none; color: #dc3545;
    font-size: .88rem; cursor: pointer; padding: 6px 10px;
    border-radius: 8px; transition: background .15s; font-weight: 600;
}
.remove-btn:hover { background: #fff0f0; }
.place-order-btn {
    background: linear-gradient(135deg, #16a34a, #15803d);
    border: none; color: #fff; padding: 14px;
    font-size: 1rem; font-weight: 700; border-radius: 12px;
    width: 100%; cursor: pointer; transition: opacity .2s;
}
.place-order-btn:hover { opacity: .9; }

/* States */
.empty-cart { text-align: center; padding: 80px 20px; }
.login-card {
    background: linear-gradient(135deg, #1a1a2e, #0f3460);
    border-radius: 20px; color: #fff; padding: 50px 30px; text-align: center;
}

/* Payment labels */
.payment-label {
    cursor: pointer; border-radius: 10px;
    border: 1.5px solid #dee2e6; padding: 12px 14px;
    background: #f8f9fa; transition: border-color .2s;
    display: flex; align-items: center; gap: 10px;
}
.payment-label:hover { border-color: var(--gold); }

/* JazzCash Info Box */
.jazzcash-box {
    background: linear-gradient(135deg, #c8102e 0%, #a00d25 100%);
    border-radius: 12px; padding: 16px 18px; color: #fff;
    display: none; margin-top: 10px;
    border: 2px solid rgba(255,255,255,.2);
    box-shadow: 0 4px 16px rgba(200,16,46,.3);
}
.jazzcash-box.show { display: block; }
.jazzcash-number {
    font-size: 1.4rem; font-weight: 800; letter-spacing: 2px;
    background: rgba(255,255,255,.15); border-radius: 8px;
    padding: 8px 14px; display: inline-block; margin: 6px 0;
    font-family: 'Courier New', monospace;
}
.jazzcash-copy-btn {
    background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.4);
    color: #fff; border-radius: 6px; padding: 4px 10px;
    font-size: .75rem; font-weight: 600; cursor: pointer;
    transition: background .15s; margin-left: 8px;
}
.jazzcash-copy-btn:hover { background: rgba(255,255,255,.35); }

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }
</style>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<?php $active_page = ''; require_once 'includes/navbar.php'; ?>

<!-- BREADCRUMB -->
<div class="bg-white border-bottom py-2">
<div class="container">
    <nav><ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item">
            <a href="index.php" style="color:var(--gold);text-decoration:none;">Home</a>
        </li>
        <li class="breadcrumb-item active text-muted">Cart</li>
    </ol></nav>
</div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="container py-4">

<?php if ($success): ?>
<!-- ORDER SUCCESS -->
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-3">
    <i class="fas fa-check-circle fa-2x text-success"></i>
    <div>
        <strong>Order Placed Successfully!</strong><br>
        <?= htmlspecialchars($success) ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<div class="text-center py-5">
    <i class="fas fa-box-open fa-5x text-success mb-4 d-block"></i>
    <h3 class="fw-bold mb-2">Thank You! Your order has been confirmed.</h3>

    <?php if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'online'): ?>
    <!-- JazzCash Payment Instructions -->
    <div class="mx-auto mb-4" style="max-width:480px;">
        <div style="background:linear-gradient(135deg,#c8102e,#a00d25);border-radius:16px;padding:24px;color:#fff;box-shadow:0 8px 28px rgba(200,16,46,.35);">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                <i class="fas fa-mobile-alt fa-2x"></i>
                <h5 class="fw-bold mb-0">Complete JazzCash Payment</h5>
            </div>
            <p style="font-size:.88rem;opacity:.9;margin-bottom:12px;">
                Send your order amount to the JazzCash number below:
            </p>
            <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:14px;margin-bottom:12px;">
                <div style="font-size:.75rem;opacity:.8;margin-bottom:4px;">JazzCash Number</div>
                <div style="font-size:1.6rem;font-weight:800;letter-spacing:3px;font-family:'Courier New',monospace;">
                    0323-0269392
                </div>
            </div>
            <div style="font-size:.78rem;opacity:.85;text-align:left;background:rgba(0,0,0,.15);border-radius:8px;padding:10px 14px;">
                <div class="mb-1"><i class="fas fa-check-circle me-2"></i>Save the payment screenshot</div>
                <div class="mb-1"><i class="fas fa-check-circle me-2"></i>Mention order number: <strong>#<?= $order_id ?></strong></div>
                <div><i class="fas fa-check-circle me-2"></i>Delivery will be processed after confirmation</div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <p class="text-muted mb-4">We will process your order and deliver it soon.</p>
    <?php endif; ?>

    <a href="index.php#products" class="btn btn-warning btn-lg fw-bold px-5">
        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
    </a>
</div>

<?php elseif (!isLoggedIn()): ?>
<!-- NOT LOGGED IN -->
<div class="row justify-content-center mt-4">
<div class="col-lg-5 col-md-7">
    <div class="login-card">
        <i class="fas fa-lock fa-4x mb-3 d-block" style="color:var(--gold)"></i>
        <h3 class="fw-bold mb-2">Login Required</h3>
        <p style="opacity:.8" class="mb-4">Please login or create an account to view your cart.</p>
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

<?php elseif (empty($cart_items)): ?>
<!-- EMPTY CART -->
<div class="empty-cart">
    <i class="fas fa-shopping-cart fa-5x text-muted mb-4 d-block"></i>
    <h3 class="fw-bold text-muted mb-2">Your Cart is Empty</h3>
    <p class="text-muted mb-4">No items in your cart. Browse spare parts!</p>
    <a href="index.php#products" class="btn btn-warning btn-lg fw-bold px-5">
        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
    </a>
</div>

<?php else: ?>
<!-- CART WITH ITEMS -->

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ===== LEFT: Cart Items ===== -->
    <div class="col-lg-8">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">
                <i class="fas fa-shopping-cart text-warning me-2"></i>
                Your Cart
                <span class="badge bg-warning text-dark ms-1"><?= $item_count ?></span>
            </h4>
            <form method="POST" onsubmit="return confirm('Clear entire cart?')">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-trash me-1"></i>Clear Cart
                </button>
            </form>
        </div>

        <!-- Items Loop -->
        <?php foreach ($cart_items as $ci):
            $unit_price = $ci['discount_price'] ? (float)$ci['discount_price'] : (float)$ci['price'];
            $item_sub   = $unit_price * $ci['quantity'];
            $img_src    = (!empty($ci['image']) && $ci['image'] !== 'default.jpg')
                          ? 'uploads/' . htmlspecialchars($ci['image'])
                          : 'https://via.placeholder.com/85x85/e2e8f0/475569?text=Part';
        ?>
        <div class="cart-item-card">
            <div class="d-flex gap-3 align-items-start">

                <!-- Product Image -->
                <img src="<?= $img_src ?>"
                     class="cart-img"
                     alt="<?= htmlspecialchars($ci['name']) ?>"
                     onerror="this.src='https://via.placeholder.com/85x85/e2e8f0/475569?text=No+Img'">

                <!-- Product Details -->
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                        <div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($ci['name']) ?></h6>
                            <!-- Badges -->
                            <div class="d-flex gap-2 flex-wrap mb-2">
                                <?php if ($ci['brand']): ?>
                                <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                                    <?= htmlspecialchars($ci['brand']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($ci['cat_name']): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:.7rem;">
                                    <?= htmlspecialchars($ci['cat_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <!-- Price -->
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color:#e67e22;font-size:1.05rem;">
                                    PKR <?= number_format($unit_price) ?>
                                </span>
                                <?php if ($ci['discount_price'] && $ci['discount_price'] < $ci['price']): ?>
                                <span class="text-muted text-decoration-line-through small">
                                    PKR <?= number_format($ci['price']) ?>
                                </span>
                                <span class="badge bg-success" style="font-size:.65rem;">Sale</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        <div class="text-end">
                            <div class="fw-bold fs-5" style="color:#1a1a2e;">
                                PKR <?= number_format($item_sub) ?>
                            </div>
                            <small class="text-muted">Subtotal</small>
                        </div>
                    </div>

                    <!-- Quantity + Remove -->
                    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">

                        <!-- Qty Form -->
                        <form method="POST" class="qty-wrap" id="qtyForm<?= $ci['cart_id'] ?>">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="cart_id" value="<?= $ci['cart_id'] ?>">
                            <button type="button" class="qty-btn"
                                    onclick="changeQty(<?= $ci['cart_id'] ?>, -1, <?= $ci['stock'] ?>)">
                                &#8722;
                            </button>
                            <input type="number" name="quantity"
                                   id="qty<?= $ci['cart_id'] ?>"
                                   class="qty-input"
                                   value="<?= $ci['quantity'] ?>"
                                   min="1" max="<?= $ci['stock'] ?>"
                                   onchange="document.getElementById('qtyForm<?= $ci['cart_id'] ?>').submit()">
                            <button type="button" class="qty-btn"
                                    onclick="changeQty(<?= $ci['cart_id'] ?>, 1, <?= $ci['stock'] ?>)">
                                &#43;
                            </button>
                        </form>

                        <small class="text-muted d-inline-flex align-items-center gap-1">
                            <i class="fas fa-box" style="color:#f59e0b;"></i>Stock: <?= $ci['stock'] ?>
                        </small>

                        <!-- Remove -->
                        <form method="POST" class="ms-auto">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="cart_id" value="<?= $ci['cart_id'] ?>">
                            <button type="submit" class="remove-btn"
                                    onclick="return confirm('Remove this item?')">
                                <i class="fas fa-trash-alt me-1"></i>Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <a href="index.php#products" class="btn btn-outline-secondary mt-2">
            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
        </a>
    </div><!-- /col-lg-8 -->

    <!-- ===== RIGHT: Order Summary ===== -->
    <div class="col-lg-4">
    <div class="summary-card">
        <h5 class="fw-bold mb-4">
            <i class="fas fa-receipt text-warning me-2"></i>Order Summary
        </h5>

        <!-- Price Breakdown -->
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Items (<?= $item_count ?>)</span>
            <span class="fw-semibold">PKR <?= number_format($subtotal) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Shipping</span>
            <span class="text-success fw-semibold">
                <i class="fas fa-truck me-1"></i>Free
            </span>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="fw-bold fs-5">Total</span>
            <span class="total-price">PKR <?= number_format($subtotal) ?></span>
        </div>

        <!-- Checkout Form -->
        <form method="POST" id="checkoutForm">
            <input type="hidden" name="action" value="checkout">

            <!-- Shipping Address -->
            <div class="mb-3">
                <label class="form-label fw-semibold small">
                    Shipping Address <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" name="shipping_address" rows="3"
                          placeholder="House address, street, city, zip code..."
                          required style="border-radius:10px;font-size:.9rem;"
                ><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
            </div>

            <!-- Payment Method -->
            <div class="mb-4">
                <label class="form-label fw-semibold small">Payment Method</label>
                <div class="d-flex flex-column gap-2">
                    <label class="payment-label" id="lbl-cod">
                        <input type="radio" name="payment_method" value="cod"
                               class="form-check-input mt-0" checked
                               onchange="togglePaymentInfo(this.value)">
                        <div>
                            <div class="fw-semibold small">
                                <i class="fas fa-money-bill-wave text-success me-1"></i>
                                Cash on Delivery
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">
                                Pay when delivered
                            </div>
                        </div>
                    </label>
                    <label class="payment-label" id="lbl-online">
                        <input type="radio" name="payment_method" value="online"
                               class="form-check-input mt-0"
                               onchange="togglePaymentInfo(this.value)">
                        <div>
                            <div class="fw-semibold small">
                                <i class="fas fa-mobile-alt text-danger me-1"></i>
                                JazzCash / Online Payment
                            </div>
                            <div class="text-muted" style="font-size:.72rem;">
                                Send to JazzCash number
                            </div>
                        </div>
                    </label>
                </div>

                <!-- JazzCash Info Box -->
                <div class="jazzcash-box" id="jazzcashBox">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/9/9a/JazzCash_logo.svg/200px-JazzCash_logo.svg.png"
                             alt="JazzCash" height="22"
                             onerror="this.style.display='none'">
                        <span class="fw-bold" style="font-size:.85rem;">JazzCash Payment</span>
                    </div>
                    <div style="font-size:.78rem; opacity:.85; margin-bottom:4px;">
                        Send your total amount to the number below:
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <span class="jazzcash-number" id="jcNumber">0323-0269392</span>
                        <button type="button" class="jazzcash-copy-btn" onclick="copyJazzCash()">
                            <i class="fas fa-copy me-1"></i>Copy
                        </button>
                    </div>
                    <div style="font-size:.72rem; opacity:.8; margin-top:8px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Keep the payment screenshot. Delivery will be initiated after confirmation.
                    </div>
                </div>
            </div>

            <button type="submit" class="place-order-btn"
                    onclick="return confirm('Place this order?')">
                <i class="fas fa-check-circle me-2"></i>Place Order
            </button>
        </form>

        <div class="mt-3 text-center">
            <small class="text-muted">
                <i class="fas fa-shield-alt text-success me-1"></i>
                100% Secure Checkout
            </small>
        </div>
    </div>
    </div><!-- /col-lg-4 -->

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
<script>
// Quantity +/- buttons
function changeQty(cartId, delta, maxStock) {
    var input  = document.getElementById('qty' + cartId);
    var newVal = parseInt(input.value) + delta;
    if (newVal < 1) newVal = 1;
    if (newVal > maxStock) {
        alert('Only ' + maxStock + ' units available in stock.');
        return;
    }
    input.value = newVal;
    document.getElementById('qtyForm' + cartId).submit();
}

// Toggle JazzCash info box
function togglePaymentInfo(value) {
    var box = document.getElementById('jazzcashBox');
    var lblOnline = document.getElementById('lbl-online');
    if (value === 'online') {
        box.classList.add('show');
        lblOnline.style.borderColor = '#c8102e';
        lblOnline.style.background  = '#fff5f5';
    } else {
        box.classList.remove('show');
        lblOnline.style.borderColor = '';
        lblOnline.style.background  = '';
    }
}

// Copy JazzCash number
function copyJazzCash() {
    var num = '03230269392';
    navigator.clipboard.writeText(num).then(function() {
        var btn = document.querySelector('.jazzcash-copy-btn');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        btn.style.background = 'rgba(255,255,255,.45)';
        setTimeout(function() {
            btn.innerHTML = orig;
            btn.style.background = '';
        }, 2000);
    }).catch(function() {
        alert('Number: 0323-0269392');
    });
}
</script>
</body>
</html>
