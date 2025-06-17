<?php
require_once 'includes/language.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['language'])) {
    $language = $_POST['language'];
    
    // Validate language
    if (in_array($language, ['en', 'dv'])) {
        setLanguage($language);
        
        // Store in system settings if user is admin
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            require_once 'includes/functions.php';
            $db = getDB();
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_type, description) 
                    VALUES ('default_language', ?, 'text', 'Default system language')
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$language]);
            } catch (Exception $e) {
                // Ignore if system_settings table doesn't exist yet
            }
        }
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'language' => $language]);
            exit;
        }
    }
}

// Redirect back to the referring page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
header("Location: $redirect");
exit;
?>
