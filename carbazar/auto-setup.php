<?php
/**
 * CarBazar - Automatic Setup Script
 * Run this ONCE after uploading files
 */

// Prevent running multiple times
$setup_complete_file = 'setup_complete.txt';
if (file_exists($setup_complete_file)) {
    die("Setup already completed! Delete 'setup_complete.txt' to run again.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBazar - Auto Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 800px; width: 100%; padding: 40px; }
        h1 { color: #1a1a2e; font-size: 2rem; margin-bottom: 10px; display: flex; align-items: center; gap: 15px; }
        h1 i { color: #f0c040; font-size: 2.5rem; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 1.1rem; }
        .step { background: #f8f9fa; border-left: 4px solid #f0c040; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .step h3 { color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .step-number { background: #f0c040; color: #1a1a2e; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="url"] { width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 15px; margin: 8px 0; transition: border 0.3s; }
        input:focus { outline: none; border-color: #f0c040; }
        label { display: block; color: #333; font-weight: 600; margin-top: 15px; margin-bottom: 5px; }
        .help-text { color: #666; font-size: 13px; margin-top: 5px; }
        button { background: #f0c040; color: #1a1a2e; padding: 15px 40px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; transition: all 0.3s; }
        button:hover { background: #e0b030; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(240,192,64,0.3); }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; font-size: 14px; }
        .progress { display: none; text-align: center; padding: 30px; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #f0c040; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 4px; color: #e83e8c; font-family: monospace; }
        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 10px; margin: 5px 0; background: white; border-radius: 5px; display: flex; align-items: center; gap: 10px; }
        .checklist li:before { content: "✓"; color: #28a745; font-weight: bold; font-size: 18px; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-rocket"></i> CarBazar Auto Setup</h1>
    <p class="subtitle">Automatic deployment configuration - Just fill the form below!</p>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo '<div class="progress" id="progress">
                <div class="spinner"></div>
                <h3>Setting up your website...</h3>
                <p>Please wait, this may take a few seconds.</p>
              </div>';
        
        echo '<script>document.getElementById("progress").style.display = "block";</script>';
        
        $errors = [];
        $success_messages = [];
        
        // Get form data
        $db_host = trim($_POST['db_host'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = trim($_POST['db_pass'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $site_url = rtrim(trim($_POST['site_url'] ?? ''), '/') . '/';
        
        // Validate inputs
        if (empty($db_host) || empty($db_user) || empty($db_name)) {
            $errors[] = "Database credentials are required!";
        }
        
        if (empty($site_url) || $site_url === '/') {
            $errors[] = "Website URL is required!";
        }
        
        if (empty($errors)) {
            // Step 1: Update db.php
            $db_config = "<?php
// ============================================================
// SESSION - Start first
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Database Config
// ============================================================
if (!defined('DB_HOST')) define('DB_HOST', '$db_host');
if (!defined('DB_USER')) define('DB_USER', '$db_user');
if (!defined('DB_PASS')) define('DB_PASS', '$db_pass');
if (!defined('DB_NAME')) define('DB_NAME', '$db_name');

// ============================================================
// Connection
// ============================================================
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (\$conn->connect_error) {
    \$err = \$conn->connect_error;
    if (!empty(\$_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . \$err]);
    } else {
        echo \"<!DOCTYPE html><html><body style='font-family:sans-serif;padding:40px'>
        <h2 style='color:red'>&#10060; Database Connection Failed</h2>
        <p><b>Error:</b> \" . htmlspecialchars(\$err) . \"</p>
        <hr>
        <p>&#9989; Check database credentials in backend/config/db.php</p>
        </body></html>\";
    }
    exit;
}

\$conn->set_charset(\"utf8mb4\");

// ============================================================
// Paths
// ============================================================
if (!defined('ROOT_PATH'))   define('ROOT_PATH',   realpath(__DIR__ . '/../../'));
if (!defined('BASE_URL'))    define('BASE_URL',    '$site_url');
if (!defined('UPLOADS_DIR')) define('UPLOADS_DIR', ROOT_PATH . '/uploads/');

// ============================================================
// Error Logging
// ============================================================
\$log_dir = __DIR__ . '/../logs/';
if (!is_dir(\$log_dir)) mkdir(\$log_dir, 0755, true);
if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', \$log_dir . 'error.log');

// ============================================================
// Helper Functions
// ============================================================
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return !empty(\$_SESSION['user_id']);
    }
}

if (!function_exists('isSeller')) {
    function isSeller() {
        return isset(\$_SESSION['user_type']) && \$_SESSION['user_type'] === 'seller';
    }
}

if (!function_exists('isBuyer')) {
    function isBuyer() {
        return isset(\$_SESSION['user_type']) && \$_SESSION['user_type'] === 'buyer';
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset(\$_SESSION['user_type']) && \$_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        return \$_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(\$status, \$message, \$data = null) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        \$r = ['status' => \$status, 'message' => \$message];
        if (\$data !== null) \$r['data'] = \$data;
        echo json_encode(\$r);
        exit;
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo(\$url) {
        header('Location: ' . \$url);
        exit;
    }
}
?>";
            
            if (file_put_contents('backend/config/db.php', $db_config)) {
                $success_messages[] = "✓ Database configuration updated";
            } else {
                $errors[] = "Failed to update database configuration";
            }
            
            // Step 2: Update SEO file
            if (file_exists('includes/seo.php')) {
                $seo_content = file_get_contents('includes/seo.php');
                $seo_content = str_replace('http://localhost/carbazar/', $site_url, $seo_content);
                if (file_put_contents('includes/seo.php', $seo_content)) {
                    $success_messages[] = "✓ SEO configuration updated";
                }
            }
            
            // Step 3: Update sitemap
            if (file_exists('sitemap.xml')) {
                $sitemap_content = file_get_contents('sitemap.xml');
                $sitemap_content = str_replace('http://localhost/carbazar/', $site_url, $sitemap_content);
                if (file_put_contents('sitemap.xml', $sitemap_content)) {
                    $success_messages[] = "✓ Sitemap updated";
                }
            }
            
            // Step 4: Update robots.txt
            if (file_exists('robots.txt')) {
                $robots_content = file_get_contents('robots.txt');
                $robots_content = str_replace('http://localhost/carbazar/', $site_url, $robots_content);
                if (file_put_contents('robots.txt', $robots_content)) {
                    $success_messages[] = "✓ Robots.txt updated";
                }
            }
            
            // Mark setup as complete
            file_put_contents($setup_complete_file, date('Y-m-d H:i:s'));
        }
        
        echo '<script>document.getElementById("progress").style.display = "none";</script>';
        
        if (!empty($errors)) {
            echo '<div class="error"><h3>❌ Setup Failed</h3>';
            foreach ($errors as $error) {
                echo "<p>• $error</p>";
            }
            echo '</div>';
        }
        
        if (!empty($success_messages)) {
            echo '<div class="success">';
            echo '<h3>🎉 Setup Completed Successfully!</h3>';
            echo '<ul class="checklist">';
            foreach ($success_messages as $msg) {
                echo "<li>$msg</li>";
            }
            echo '</ul>';
            echo '<h4 style="margin-top:20px;">Next Steps:</h4>';
            echo '<ol style="margin-left:20px;">';
            echo '<li>Import <code>database.sql</code> via phpMyAdmin</li>';
            echo '<li>Delete <code>auto-setup.php</code> for security</li>';
            echo '<li>Visit your website: <a href="' . $site_url . '" target="_blank">' . $site_url . '</a></li>';
            echo '<li>Login to admin: <a href="' . $site_url . 'backend/admin/login.php" target="_blank">Admin Panel</a></li>';
            echo '</ol>';
            echo '<div class="info" style="margin-top:20px;">';
            echo '<strong>Default Admin Credentials:</strong><br>';
            echo 'Email: <code>admin@carbazar.com</code><br>';
            echo 'Password: <code>admin123</code><br>';
            echo '<small>⚠️ Change password after first login!</small>';
            echo '</div>';
            echo '</div>';
        }
        
    } else {
        // Show form
        ?>
        
        <div class="info">
            <strong>📋 Before you start:</strong><br>
            1. Create MySQL database in your hosting control panel<br>
            2. Note down database credentials<br>
            3. Fill the form below
        </div>

        <form method="POST" id="setupForm">
            <div class="step">
                <h3><span class="step-number">1</span> Database Configuration</h3>
                <p class="help-text">Get these from your hosting Control Panel → MySQL Databases</p>
                
                <label for="db_host">Database Host *</label>
                <input type="text" id="db_host" name="db_host" placeholder="e.g., sql123.infinityfree.net" required>
                <p class="help-text">Usually starts with "sql" followed by numbers</p>
                
                <label for="db_user">Database Username *</label>
                <input type="text" id="db_user" name="db_user" placeholder="e.g., epiz_12345678" required>
                <p class="help-text">Usually starts with "epiz_"</p>
                
                <label for="db_pass">Database Password *</label>
                <input type="password" id="db_pass" name="db_pass" placeholder="Your database password" required>
                <p class="help-text">The password you set when creating database</p>
                
                <label for="db_name">Database Name *</label>
                <input type="text" id="db_name" name="db_name" placeholder="e.g., epiz_12345678_carbazar" required>
                <p class="help-text">Your database name (usually epiz_XXXXX_carbazar)</p>
            </div>

            <div class="step">
                <h3><span class="step-number">2</span> Website URL</h3>
                <p class="help-text">Your website's full URL</p>
                
                <label for="site_url">Website URL *</label>
                <input type="url" id="site_url" name="site_url" placeholder="http://carbazar.infinityfreeapp.com" required>
                <p class="help-text">Don't add trailing slash - it will be added automatically</p>
            </div>

            <button type="submit">
                <i class="fas fa-rocket"></i> Start Automatic Setup
            </button>
        </form>

        <div class="info" style="margin-top:30px;">
            <strong>⏱️ This will take about 5 seconds</strong><br>
            The script will automatically configure all files for your hosting environment.
        </div>
        
        <?php
    }
    ?>
</div>
</body>
</html>
