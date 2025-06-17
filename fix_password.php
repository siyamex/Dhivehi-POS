<?php
// Fix admin password script
// Run this once in browser: http://localhost/pos/fix_password.php

require_once 'config/database.php';

$password = 'admin123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $db = getDB();
    
    // Update admin user password
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result = $stmt->execute([$hashedPassword]);
    
    if ($result) {
        echo "<h2>✅ Admin password updated successfully!</h2>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p>You can now <a href='login.php'>login here</a></p>";
        
        // Verify the password works
        $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✅ Password verification: SUCCESS</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification: FAILED</p>";
        }
        
    } else {
        echo "<h2>❌ Failed to update password</h2>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Make sure you have:</p>";
    echo "<ul>";
    echo "<li>Imported the database (pos_system.sql)</li>";
    echo "<li>Started MySQL service in XAMPP</li>";
    echo "<li>Correct database configuration</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this file (fix_password.php) after use for security.</p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        h2 { color: #333; }
        ul { background: #f5f5f5; padding: 15px; }
    </style>
</head>
<body>
    <!-- Content generated above -->
</body>
</html>
