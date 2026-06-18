<?php
// ============================================================
// CarBazar Chatbot API — Budget-based DB search
// ============================================================
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input  = json_decode(file_get_contents('php://input'), true);
$type   = strtolower(trim($input['type']   ?? ''));   // 'cars' | 'parts'
$budget = (float)($input['budget'] ?? 0);
$brand  = trim($input['brand']  ?? '');
$city   = trim($input['city']   ?? '');
$query  = trim($input['query']  ?? '');               // parts search keyword
$limit  = 4;

// ── Cars Search ──────────────────────────────────────────────
if ($type === 'cars') {
    if ($budget <= 0) {
        echo json_encode(['status' => 'need_budget', 'message' => 'budget_required']);
        exit;
    }

    $sql    = "SELECT id, title, brand, model, year, price, city, image, fuel_type, mileage
               FROM cars
               WHERE is_active = 1 AND is_sold = 0 AND price <= ?";
    $params = [$budget];
    $types  = 'd';

    if ($brand) {
        $sql    .= " AND brand LIKE ?";
        $params[] = '%' . $brand . '%';
        $types  .= 's';
    }
    if ($city) {
        $sql    .= " AND city LIKE ?";
        $params[] = '%' . $city . '%';
        $types  .= 's';
    }

    $sql .= " ORDER BY price DESC LIMIT ?";
    $params[] = $limit;
    $types  .= 'i';

    $st = $conn->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    // Total count (without limit)
    $countSql = "SELECT COUNT(*) as total FROM cars WHERE is_active=1 AND is_sold=0 AND price <= ?";
    $cParams  = [$budget];
    $cTypes   = 'd';
    if ($brand) { $countSql .= " AND brand LIKE ?"; $cParams[] = '%'.$brand.'%'; $cTypes .= 's'; }
    if ($city)  { $countSql .= " AND city LIKE ?";  $cParams[] = '%'.$city.'%';  $cTypes .= 's'; }
    $cs = $conn->prepare($countSql);
    $cs->bind_param($cTypes, ...$cParams);
    $cs->execute();
    $total = $cs->get_result()->fetch_assoc()['total'];
    $cs->close();

    echo json_encode([
        'status' => 'success',
        'type'   => 'cars',
        'total'  => (int)$total,
        'items'  => $rows
    ]);
    exit;
}

// ── Parts Search ─────────────────────────────────────────────
if ($type === 'parts') {
    $sql    = "SELECT p.id, p.name, p.price, p.discount_price, p.brand, p.image,
                      p.stock, p.rating, c.name AS category
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.id
               WHERE p.is_active = 1 AND p.stock > 0";
    $params = [];
    $types  = '';

    if ($budget > 0) {
        $effectivePrice = "COALESCE(NULLIF(p.discount_price,0), p.price)";
        $sql    .= " AND $effectivePrice <= ?";
        $params[] = $budget;
        $types  .= 'd';
    }
    if ($query) {
        $sql    .= " AND (p.name LIKE ? OR p.brand LIKE ? OR c.name LIKE ?)";
        $kw      = '%' . $query . '%';
        $params  = array_merge($params, [$kw, $kw, $kw]);
        $types  .= 'sss';
    }

    $sql .= " ORDER BY p.rating DESC, p.price ASC LIMIT ?";
    $params[] = $limit;
    $types  .= 'i';

    $st = $conn->prepare($sql);
    if ($types) $st->bind_param($types, ...$params);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    // Total count
    $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.is_active=1 AND p.stock>0";
    $cParams  = [];
    $cTypes   = '';
    if ($budget > 0) {
        $countSql .= " AND COALESCE(NULLIF(p.discount_price,0), p.price) <= ?";
        $cParams[] = $budget; $cTypes .= 'd';
    }
    if ($query) {
        $countSql .= " AND (p.name LIKE ? OR p.brand LIKE ? OR c.name LIKE ?)";
        $kw = '%'.$query.'%';
        $cParams = array_merge($cParams, [$kw,$kw,$kw]); $cTypes .= 'sss';
    }
    $cs = $conn->prepare($countSql);
    if ($cTypes) $cs->bind_param($cTypes, ...$cParams);
    $cs->execute();
    $total = $cs->get_result()->fetch_assoc()['total'];
    $cs->close();

    echo json_encode([
        'status' => 'success',
        'type'   => 'parts',
        'total'  => (int)$total,
        'items'  => $rows
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request type']);
