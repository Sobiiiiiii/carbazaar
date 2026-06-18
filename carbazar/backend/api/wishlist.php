<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    jsonResponse('error', 'Please login first');
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? '';

// ── ALTER TABLE once if car_id column missing ──────────────────
$col_check = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'car_id'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE wishlist
        MODIFY product_id INT DEFAULT NULL,
        ADD COLUMN car_id INT DEFAULT NULL AFTER product_id,
        ADD UNIQUE KEY unique_wishlist_car (user_id, car_id),
        ADD FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── ADD ──────────────────────────────────────────────────────
    if ($action === 'add') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $car_id     = isset($_POST['car_id'])     ? (int)$_POST['car_id']     : null;

        if ($car_id) {
            // Car wishlist
            $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, car_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $car_id);
        } elseif ($product_id) {
            // Product wishlist
            $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $product_id);
        } else {
            jsonResponse('error', 'No item specified');
        }

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                jsonResponse('success', 'Added to wishlist');
            } else {
                jsonResponse('info', 'Already in wishlist');
            }
        } else {
            jsonResponse('error', 'Failed to add to wishlist');
        }
    }

    // ── REMOVE ───────────────────────────────────────────────────
    elseif ($action === 'remove') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $car_id     = isset($_POST['car_id'])     ? (int)$_POST['car_id']     : null;

        if ($car_id) {
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND car_id = ?");
            $stmt->bind_param("ii", $user_id, $car_id);
        } elseif ($product_id) {
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
        } else {
            jsonResponse('error', 'No item specified');
        }

        if ($stmt->execute()) {
            jsonResponse('success', 'Removed from wishlist');
        } else {
            jsonResponse('error', 'Failed to remove');
        }
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // ── LIST ─────────────────────────────────────────────────────
    if ($action === 'list') {
        // Products
        $stmt = $conn->prepare("
            SELECT p.* FROM wishlist w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        jsonResponse('success', 'Wishlist retrieved', $items);
    }

    // ── CHECK ────────────────────────────────────────────────────
    elseif ($action === 'check') {
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $car_id     = isset($_GET['car_id'])     ? (int)$_GET['car_id']     : null;

        if ($car_id) {
            $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND car_id = ?");
            $stmt->bind_param("ii", $user_id, $car_id);
        } else {
            $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        jsonResponse('success', '', ['exists' => $exists]);
    }
}
?>
