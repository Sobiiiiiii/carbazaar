<?php
// ============================================================
// CarBazar — Gemini AI Proxy (server-side, key safe rehti hai)
// ============================================================
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$history = $input['history'] ?? [];
$context = $input['context'] ?? [];   // budget, city, brand, mode, query

// ── Gemini API Key (server mein safe) ───────────────────────
$GEMINI_KEY = 'YOUR_GEMINI_API_KEY';
$GEMINI_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_KEY;

// ── DB se live data fetch karo context ke liye ───────────────
$dbContext = '';

if (!empty($context['budget']) && $context['budget'] > 0) {
    $budget = (float)$context['budget'];

    if (!empty($context['mode']) && $context['mode'] === 'cars') {
        // Cars fetch
        $sql = "SELECT title, brand, model, year, price, city, fuel_type, mileage
                FROM cars WHERE is_active=1 AND is_sold=0 AND price <= ?
                ORDER BY price DESC LIMIT 5";
        $st = $conn->prepare($sql);
        $st->bind_param('d', $budget);
        $st->execute();
        $cars = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        if ($cars) {
            $dbContext .= "\n\n📊 DATABASE LIVE DATA — Cars under PKR " . number_format($budget) . ":\n";
            foreach ($cars as $c) {
                $dbContext .= "- {$c['title']} ({$c['year']}) | PKR " . number_format($c['price'])
                           . " | {$c['city']} | {$c['fuel_type']} | {$c['mileage']}km\n";
            }
            $dbContext .= "Total available: " . count($cars) . "+ cars\n";
        } else {
            $dbContext .= "\n\n📊 DATABASE: No cars found under PKR " . number_format($budget) . ". Suggest user to increase budget or check all-cars.php\n";
        }
    }

    if (!empty($context['mode']) && $context['mode'] === 'parts') {
        // Parts fetch
        $keyword = $context['query'] ?? '';
        $sql = "SELECT p.name, p.price, p.discount_price, p.brand, c.name AS category
                FROM products p LEFT JOIN categories c ON p.category_id=c.id
                WHERE p.is_active=1 AND p.stock>0
                AND COALESCE(NULLIF(p.discount_price,0), p.price) <= ?";
        $params = [$budget];
        $types  = 'd';
        if ($keyword) {
            $sql .= " AND (p.name LIKE ? OR c.name LIKE ?)";
            $kw = '%' . $keyword . '%';
            $params[] = $kw; $params[] = $kw;
            $types .= 'ss';
        }
        $sql .= " ORDER BY p.rating DESC LIMIT 5";
        $params[] = 5; $types .= 'i';
        $st = $conn->prepare($sql);
        $st->bind_param($types, ...$params);
        $st->execute();
        $parts = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        if ($parts) {
            $dbContext .= "\n\n📊 DATABASE LIVE DATA — Parts under PKR " . number_format($budget) . ":\n";
            foreach ($parts as $p) {
                $price = $p['discount_price'] && $p['discount_price'] < $p['price']
                    ? number_format($p['discount_price']) . ' (was ' . number_format($p['price']) . ')'
                    : number_format($p['price']);
                $dbContext .= "- {$p['name']} | {$p['brand']} | {$p['category']} | PKR {$price}\n";
            }
        } else {
            $dbContext .= "\n\n📊 DATABASE: No parts found under PKR " . number_format($budget) . ". Suggest user to check all-parts.php\n";
        }
    }
}

// ── System Prompt ────────────────────────────────────────────
$systemPrompt = <<<PROMPT
You are an intelligent, friendly, and professional AI assistant for "CarBazar" — Pakistan's trusted car marketplace website.

🔹 YOUR CORE JOB:
- Help users buy/sell cars and spare parts
- Use the LIVE DATABASE DATA provided below to give accurate, real answers
- When database data is available, mention specific cars/parts with their prices
- Keep responses SHORT (3-5 lines max), friendly, and action-oriented

🔹 CAPABILITIES:
1. Car Search — use live DB data to suggest real cars within budget
2. Spare Parts — use live DB data to show available parts
3. Sell Guide — step-by-step how to post an ad on CarBazar
4. Smart Recommendations — Toyota, Honda, Suzuki suggestions
5. FAQ — account creation, image upload, contact seller

🔹 WEBSITE PAGES:
- All Cars: all-cars.php
- All Parts: all-parts.php
- Sell Car/Part: sell.php
- Cart: cart.php
- Orders: orders.php
- Register: register.php

🔹 CONTACT:
- Phone: +92 304 0369392
- Email: support@carbazar.com
- Address: 123 Car Lane, Auto City, Vehari, Pakistan

🔹 RULES:
- ALWAYS use the live database data when provided — mention actual car names and prices
- If no DB data available, say "Please check listings page" and provide link
- Use emojis to make responses friendly
- Keep answers SHORT and CLEAR
- Always end with a call-to-action (link or suggestion)
- Format prices as "PKR X,XXX,XXX"

🔹 LANGUAGE:
- English message → reply in English
- Urdu message → reply in Urdu
- Roman Urdu → reply in Roman Urdu
- Auto-detect and match user's language ALWAYS
{$dbContext}
PROMPT;

// ── Build Gemini payload ─────────────────────────────────────
$payload = [
    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents'           => $history,
    'generationConfig'   => [
        'temperature'     => 0.7,
        'maxOutputTokens' => 400,
        'topP'            => 0.9,
    ]
];

// ── cURL call to Gemini ──────────────────────────────────────
$ch = curl_init($GEMINI_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'AI service unavailable',
        'code'    => $httpCode
    ]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'status' => 'success',
        'reply'  => $data['candidates'][0]['content']['parts'][0]['text']
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No response from AI',
        'raw'     => $data
    ]);
}
