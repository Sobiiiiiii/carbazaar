<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    jsonResponse('error', 'Please login first');
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;

        // Check if product exists
        $stmt = $conn->prepare("SELECT id, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            jsonResponse('error', 'Product not found');
        }

        $product = $result->fetch_assoc();

        if ($product['stock'] < $quantity) {
            jsonResponse('error', 'Insufficient stock');
        }

        // Check if already in cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update quantity
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        } else {
            // Add to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        }

        if ($stmt->execute()) {
            jsonResponse('success', 'Product added to cart');
        } else {
            jsonResponse('error', 'Failed to add to cart');
        }
    }

    elseif ($action === 'remove') {
        $cart_id = $_POST['cart_id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Product removed from cart');
        } else {
            jsonResponse('error', 'Failed to remove product');
        }
    }

    elseif ($action === 'update') {
        $cart_id = $_POST['cart_id'] ?? 0;
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($quantity <= 0) {
            jsonResponse('error', 'Invalid quantity');
        }

        // Check stock before updating
        $stmt = $conn->prepare("
            SELECT p.stock FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            jsonResponse('error', 'Cart item not found');
        }

        $product = $result->fetch_assoc();
        if ($quantity > $product['stock']) {
            jsonResponse('error', 'Quantity exceeds available stock (' . $product['stock'] . ' available)');
        }

        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Cart updated');
        } else {
            jsonResponse('error', 'Failed to update cart');
        }
    }

    elseif ($action === 'clear') {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Cart cleared');
        } else {
            jsonResponse('error', 'Failed to clear cart');
        }
    }
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get') {
        // Get cart items
        $stmt = $conn->prepare("
            SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.discount_price, p.image, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $cart_items = [];
        $total = 0;

        while ($row = $result->fetch_assoc()) {
            $price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
            $subtotal = $price * $row['quantity'];
            $total += $subtotal;

            $cart_items[] = [
                'cart_id' => $row['id'],
                'product_id' => $row['product_id'],
                'name' => $row['name'],
                'price' => $price,
                'original_price' => $row['price'],
                'quantity' => $row['quantity'],
                'subtotal' => $subtotal,
                'image' => $row['image'],
                'stock' => $row['stock']
            ];
        }

        jsonResponse('success', 'Cart retrieved', [
            'items' => $cart_items,
            'total' => $total,
            'item_count' => count($cart_items)
        ]);
    }

    elseif ($action === 'count') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        jsonResponse('success', '', ['count' => $row['count']]);
    }
}
?>