<?php
/**
 * URL Update Script for Deployment
 * Run this once after uploading to update all URLs
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>CarBazar - URL Update Tool</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1a1a2e; border-bottom: 3px solid #f0c040; padding-bottom: 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        input[type='text'] { width: 100%; padding: 10px; font-size: 16px; border: 2px solid #ddd; border-radius: 5px; }
        button { background: #f0c040; color: #1a1a2e; padding: 12px 30px; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { background: #e0b030; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #f0c040; }
    </style>
</head>
<body>
<div class='container'>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_url = rtrim($_POST['new_url'] ?? '', '/') . '/';
    
    if (empty($new_url) || $new_url === '/') {
        echo "<div class='error'>❌ Please enter a valid URL!</div>";
    } else {
        echo "<h1>🔄 Updating URLs...</h1>";
        
        $old_url = 'http://localhost/carbazar/';
        $files_updated = 0;
        $errors = [];
        
        // Files to update
        $files = [
            'backend/config/db.php',
            'includes/seo.php',
            'sitemap.xml',
            'robots.txt'
        ];
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $errors[] = "File not found: $file";
                continue;
            }
            
            $content = file_get_contents($file);
            $updated_content = str_replace($old_url, $new_url, $content);
            
            if (file_put_contents($file, $updated_content)) {
                echo "<div class='success'>✅ Updated: $file</div>";
                $files_updated++;
            } else {
                $errors[] = "Failed to update: $file";
            }
        }
        
        echo "<div class='info'>";
        echo "<h3>📊 Summary:</h3>";
        echo "<p><strong>Old URL:</strong> $old_url</p>";
        echo "<p><strong>New URL:</strong> $new_url</p>";
        echo "<p><strong>Files Updated:</strong> $files_updated / " . count($files) . "</p>";
        echo "</div>";
        
        if (!empty($errors)) {
            echo "<div class='error'>";
            echo "<h3>⚠️ Errors:</h3>";
            foreach ($errors as $error) {
                echo "<p>• $error</p>";
            }
            echo "</div>";
        }
        
        if ($files_updated > 0) {
            echo "<div class='success'>";
            echo "<h3>🎉 Success!</h3>";
            echo "<p>Your website URLs have been updated successfully!</p>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li>Delete this file (update-urls.php) for security</li>";
            echo "<li>Test your website: <a href='$new_url' target='_blank'>$new_url</a></li>";
            echo "<li>Login to admin panel: <a href='{$new_url}backend/admin/login.php' target='_blank'>Admin Login</a></li>";
            echo "</ol>";
            echo "</div>";
        }
    }
    
    echo "<br><a href='update-urls.php'><button>← Back</button></a>";
    
} else {
    // Show form
    echo "<h1>🚀 CarBazar - URL Update Tool</h1>";
    
    echo "<div class='info'>";
    echo "<h3>📋 Instructions:</h3>";
    echo "<ol>";
    echo "<li>Upload all files to your hosting</li>";
    echo "<li>Import database.sql via phpMyAdmin</li>";
    echo "<li>Update database credentials in backend/config/db.php</li>";
    echo "<li>Enter your new website URL below</li>";
    echo "<li>Click 'Update URLs'</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>🌐 Enter Your Website URL:</h3>";
    echo "<form method='POST'>";
    echo "<input type='text' name='new_url' placeholder='http://carbazar.infinityfreeapp.com' required>";
    echo "<br><button type='submit'>🔄 Update URLs</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📝 Examples:</h3>";
    echo "<p>• http://carbazar.infinityfreeapp.com</p>";
    echo "<p>• http://carbazar.tk</p>";
    echo "<p>• http://yourdomain.com</p>";
    echo "<p><strong>Note:</strong> Don't add trailing slash - it will be added automatically</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
