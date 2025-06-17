<?php
session_start();

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Include language system
require_once __DIR__ . '/language.php';

// Helper functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    // Set language from system settings if not already set in session
    if (!isset($_SESSION['language'])) {
        $defaultLang = getSystemSetting('default_language', 'en');
        setLanguage($defaultLang);
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect('dashboard.php');
    }
}

function formatCurrency($amount) {
    return 'MVR ' . number_format($amount, 2);
}

function generateSaleNumber() {
    return 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generatePONumber() {
    return 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        $alertClass = '';
        
        switch ($alert['type']) {
            case 'success':
                $alertClass = 'bg-green-100 border-green-400 text-green-700';
                break;
            case 'error':
                $alertClass = 'bg-red-100 border-red-400 text-red-700';
                break;
            case 'warning':
                $alertClass = 'bg-yellow-100 border-yellow-400 text-yellow-700';
                break;
            default:
                $alertClass = 'bg-blue-100 border-blue-400 text-blue-700';
        }
        
        echo '<div class="border px-4 py-3 rounded mb-4 ' . $alertClass . '" role="alert">';
        echo '<span class="block sm:inline">' . htmlspecialchars($alert['message']) . '</span>';
        echo '</div>';
        
        unset($_SESSION['alert']);
    }
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination helper
function paginate($query, $params, $page = 1, $perPage = 10) {
    $db = getDB();
    
    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate pagination
    $totalPages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Get paginated results
    $paginatedQuery = $query . " LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($paginatedQuery);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return [
        'data' => $results,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $perPage
    ];
}

// Get system setting by key
function getSystemSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

// Get all system settings as associative array
function getAllSystemSettings() {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}
?>
