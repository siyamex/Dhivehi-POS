<?php
// Test file to showcase all available Dhivehi translations
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/language.php';

// Set language to Dhivehi for testing
setLanguage('dv');

echo "<!DOCTYPE html>";
echo "<html lang='" . getCurrentLanguage() . "' " . (isRTL() ? 'dir="rtl"' : 'dir="ltr"') . ">";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Complete Dhivehi Translation Test</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
if (isRTL()) {
    echo "<link href='https://fonts.googleapis.com/css2?family=Noto+Sans+Thaana:wght@400;500;600;700&display=swap' rel='stylesheet'>";
    echo "<style>body { font-family: 'Noto Sans Thaana', sans-serif; direction: rtl; }</style>";
}
echo "</head>";
echo "<body class='bg-gray-50 p-8'>";

echo "<div class='max-w-6xl mx-auto'>";
echo "<h1 class='text-3xl font-bold mb-8 text-center'>" . t('dhivehi') . " " . t('language') . " Test</h1>";

// Navigation translations
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>" . t('dashboard') . " & Navigation</h2>";
echo "<div class='grid grid-cols-2 md:grid-cols-4 gap-4'>";
$navItems = ['dashboard', 'pos', 'products', 'customers', 'sales', 'credit', 'reports', 'admin'];
foreach ($navItems as $item) {
    echo "<div class='p-3 bg-gray-100 rounded text-center'>";
    echo "<strong>" . t($item) . "</strong>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Forms and buttons
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>Form Actions & Buttons</h2>";
echo "<div class='grid grid-cols-3 md:grid-cols-6 gap-3'>";
$formActions = ['add', 'edit', 'update', 'delete', 'save', 'cancel', 'search', 'filter', 'clear', 'reset', 'view', 'back'];
foreach ($formActions as $action) {
    echo "<button class='px-3 py-2 bg-blue-500 text-white rounded text-sm'>" . t($action) . "</button>";
}
echo "</div>";
echo "</div>";

// Product management
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>" . t('products') . " Management</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
$productTerms = ['add_product', 'product_name', 'product_description', 'cost_price', 'selling_price', 'stock_quantity', 'barcode', 'product_image'];
foreach ($productTerms as $term) {
    echo "<div class='p-3 border rounded'>";
    echo "<label class='block text-sm font-medium text-gray-700'>" . t($term) . "</label>";
    echo "<input type='text' class='mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm' placeholder='" . t($term) . "'>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Customer management
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>" . t('customers') . " Management</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
$customerTerms = ['first_name', 'last_name', 'email', 'phone', 'address', 'city'];
foreach ($customerTerms as $term) {
    echo "<div class='p-3 border rounded'>";
    echo "<label class='block text-sm font-medium text-gray-700'>" . t($term) . "</label>";
    echo "<input type='text' class='mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm' placeholder='" . t($term) . "'>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Payment and sales
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>" . t('sales') . " & " . t('payment_method') . "</h2>";
echo "<div class='grid grid-cols-2 md:grid-cols-4 gap-4'>";
$paymentTerms = ['cash', 'card', 'digital', 'credit', 'subtotal', 'tax', 'discount', 'total'];
foreach ($paymentTerms as $term) {
    echo "<div class='p-3 bg-green-50 border border-green-200 rounded text-center'>";
    echo "<strong>" . t($term) . "</strong>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Status and messages
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>Status & Messages</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
$statusTerms = ['active', 'inactive', 'pending', 'paid', 'overdue', 'success', 'error', 'warning', 'loading'];
foreach ($statusTerms as $term) {
    $colorClass = in_array($term, ['success', 'paid', 'active']) ? 'bg-green-100 text-green-800' : 
                  (in_array($term, ['error', 'overdue']) ? 'bg-red-100 text-red-800' : 
                  (in_array($term, ['warning', 'pending']) ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
    echo "<div class='p-3 rounded text-center $colorClass'>";
    echo "<strong>" . t($term) . "</strong>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// System settings
echo "<div class='bg-white rounded-lg shadow p-6 mb-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>" . t('system_settings') . "</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";
$systemTerms = ['system_name', 'store_address', 'store_phone', 'currency_symbol', 'default_language', 'receipt_header', 'receipt_footer'];
foreach ($systemTerms as $term) {
    echo "<div class='p-3 border rounded'>";
    echo "<label class='block text-sm font-medium text-gray-700'>" . t($term) . "</label>";
    echo "<input type='text' class='mt-1 w-full px-3 py-2 border border-gray-300 rounded-md text-sm' placeholder='" . t($term) . "'>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Statistics
echo "<div class='bg-white rounded-lg shadow p-6'>";
echo "<h2 class='text-xl font-semibold mb-4'>Translation Statistics</h2>";
global $translations;
$totalEnglish = count($translations['en']);
$totalDhivehi = count($translations['dv']);
$completeness = round(($totalDhivehi / $totalEnglish) * 100, 1);

echo "<div class='grid grid-cols-1 md:grid-cols-3 gap-4 text-center'>";
echo "<div class='p-4 bg-blue-50 rounded'>";
echo "<div class='text-2xl font-bold text-blue-600'>$totalEnglish</div>";
echo "<div class='text-sm text-gray-600'>English Terms</div>";
echo "</div>";
echo "<div class='p-4 bg-green-50 rounded'>";
echo "<div class='text-2xl font-bold text-green-600'>$totalDhivehi</div>";
echo "<div class='text-sm text-gray-600'>Dhivehi Terms</div>";
echo "</div>";
echo "<div class='p-4 bg-purple-50 rounded'>";
echo "<div class='text-2xl font-bold text-purple-600'>$completeness%</div>";
echo "<div class='text-sm text-gray-600'>Translation Complete</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>"; // Close container
echo "</body>";
echo "</html>";
?>
