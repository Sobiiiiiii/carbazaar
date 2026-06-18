<?php
// ============================================================
// DEPLOYMENT INSTRUCTIONS
// ============================================================
// 1. Rename this file to: db.php
// 2. Replace the values below with your InfinityFree details
// 3. Delete db.TEMPLATE.php after setup
// ============================================================

// ============================================================
// SESSION - Start first
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Database Config - UPDATE THESE VALUES!
// ============================================================
// Get these from InfinityFree Control Panel → MySQL Databases

if (!defined('DB_HOST')) define('DB_HOST', 'sqlXXX.infinityfree.net'); // ← Change sqlXXX
if (!defined('DB_USER')) define('DB_USER', 'epiz_XXXXXXXX');           // ← Your database user
if (!defined('DB_PASS')) define('DB_PASS', 'YOUR_PASSWORD_HERE');      // ← Your database password
if (!defined('DB_NAME')) define('DB_NAME', 'epiz_XXXXXXXX_carbazar');  // ← Your database name

// ============================================================
// Connection
// ============================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    $err = $conn->connect_error;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $err]);
    } else {
        echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:40px'>
        <h2 style='color:red'>&#10060; Database Connection Failed</h2>
        <p><b>Error:</b> " . htmlspecialchars($err) . "</p>
        <hr>
        <h3>Setup Instructions:</h3>
        <ol>
            <li>Go to InfinityFree Control Panel</li>
            <li>Click 'MySQL Databases'</li>
            <li>Copy your database details</li>
            <li>Update backend/config/db.php with correct values</li>
        </ol>
        <p><b>Current Settings:</b></p>
        <ul>
            <li>Host: " . DB_HOST . "</li>
            <li>User: " . DB_USER . "</li>
            <li>Database: " . DB_NAME . "</li>
        </ul>
        </body></html>";
    }
    exit;
}

$conn->set_charset("utf8mb4");

// ============================================================
// Paths - UPDATE YOUR DOMAIN HERE!
// ============================================================
if (!defined('ROOT_PATH'))   define('ROOT_PATH',   realpath(__DIR__ . '/../../'));
if (!defined('BASE_URL'))    define('BASE_URL',    'http://carbazar.infinityfreeapp.com/'); // ← Change this!
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', ROOT_PATH . '/uploads/');

// ============================================================
// Error Logging
// ============================================================
$log_dir = __DIR__ . '/../logs/';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', $log_dir . 'error.log');

// ============================================================
// Helper Functions
// ============================================================
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isSeller')) {
    function isSeller() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'seller';
    }
}

if (!function_exists('isBuyer')) {
    function isBuyer() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer';
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($status, $message, $data = null) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $r = ['status' => $status, 'message' => $message];
        if ($data !== null) $r['data'] = $data;
        echo json_encode($r);
        exit;
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo($url) {
        header('Location: ' . $url);
        exit;
    }
}
?>
