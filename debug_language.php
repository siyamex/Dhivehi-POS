<?php
// Language system test and debug script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Language System Debug Test</h1>";
echo "<pre>";

echo "1. Testing basic includes...\n";
try {
    require_once 'includes/language.php';
    echo "✓ language.php included successfully\n";
} catch (Exception $e) {
    echo "✗ Error including language.php: " . $e->getMessage() . "\n";
}

echo "\n2. Testing language functions...\n";
try {
    echo "Current language: " . getCurrentLanguage() . "\n";
    echo "Is RTL: " . (isRTL() ? 'true' : 'false') . "\n";
    echo "✓ Basic language functions work\n";
} catch (Exception $e) {
    echo "✗ Error with language functions: " . $e->getMessage() . "\n";
}

echo "\n3. Testing translations...\n";
try {
    echo "English test: " . t('dashboard') . "\n";
    setLanguage('dv');
    echo "Dhivehi test: " . t('dashboard') . "\n";
    setLanguage('en');
    echo "✓ Translation functions work\n";
} catch (Exception $e) {
    echo "✗ Error with translations: " . $e->getMessage() . "\n";
}

echo "\n4. Testing JavaScript translations...\n";
try {
    $jsTranslations = getJSTranslations();
    echo "JS translations generated: " . strlen($jsTranslations) . " characters\n";
    echo "✓ JavaScript translations work\n";
} catch (Exception $e) {
    echo "✗ Error with JS translations: " . $e->getMessage() . "\n";
}

echo "\n5. Testing functions.php...\n";
try {
    require_once 'includes/functions.php';
    echo "✓ functions.php included successfully\n";
} catch (Exception $e) {
    echo "✗ Error including functions.php: " . $e->getMessage() . "\n";
}

echo "\n6. Testing database connection...\n";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n7. Testing system settings...\n";
try {
    $settings = getAllSystemSettings();
    echo "System settings count: " . count($settings) . "\n";
    if (isset($settings['default_language'])) {
        echo "Default language setting: " . $settings['default_language'] . "\n";
    } else {
        echo "⚠ Default language not found in settings\n";
    }
    echo "✓ System settings work\n";
} catch (Exception $e) {
    echo "✗ System settings error: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Test the actual translation output
echo "<h2>Translation Test</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Key</th><th>English</th><th>Dhivehi</th></tr>";

$testKeys = ['dashboard', 'pos', 'products', 'customers', 'sales', 'credit', 'cash', 'card', 'total'];

foreach ($testKeys as $key) {
    setLanguage('en');
    $english = t($key);
    setLanguage('dv');
    $dhivehi = t($key);
    echo "<tr><td>$key</td><td>$english</td><td>$dhivehi</td></tr>";
}
echo "</table>";

echo "<h2>RTL Test</h2>";
setLanguage('dv');
echo "<div style='direction: rtl; text-align: right; font-family: Arial, sans-serif;'>";
echo "<p>This text should be right-aligned: " . t('point_of_sale') . "</p>";
echo "</div>";
?>
