<?php
require_once 'includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$saleId = $_GET['id'] ?? null;

if (!$saleId) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$db = getDB();

// Get system settings
$systemSettings = getAllSystemSettings();

// Get sale details
$stmt = $db->prepare("
    SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
           c.email as customer_email, c.phone as customer_phone,
           u.first_name as cashier_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$saleId]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

// Get sale items
$stmt = $db->prepare("
    SELECT si.*, p.name as product_name, p.sku
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$saleId]);
$saleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate receipt HTML
$systemName = $systemSettings['system_name'] ?? 'POS System';
$systemLogo = $systemSettings['system_logo'] ?? '';
$storeAddress = $systemSettings['store_address'] ?? '';
$storePhone = $systemSettings['store_phone'] ?? '';
$storeEmail = $systemSettings['store_email'] ?? '';
$receiptHeader = $systemSettings['receipt_header'] ?? 'Thank you for your purchase!';
$receiptFooter = $systemSettings['receipt_footer'] ?? 'Please come again!';

$html = '
<html>
<head>
    <title>Receipt - ' . htmlspecialchars($sale['sale_number']) . '</title>
    <style>
        body { 
            font-family: monospace; 
            font-size: 12px; 
            margin: 10px; 
            width: 280px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; margin-top: 10px; padding-top: 5px; }
        .border-bottom { border-bottom: 1px dashed #000; margin-bottom: 10px; padding-bottom: 5px; }
        .flex { display: flex; justify-content: space-between; }
        .item { margin-bottom: 3px; }
        .item-name { font-weight: bold; }        .item-details { font-size: 10px; color: #666; }
        .total-section { margin-top: 10px; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .final-total { font-weight: bold; font-size: 14px; }
        .logo { max-height: 60px; max-width: 200px; margin-bottom: 5px; display: block; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
    <div class="center">';

// Add logo if available
if (!empty($systemLogo) && file_exists('uploads/system/' . $systemLogo)) {
    $html .= '<img src="uploads/system/' . htmlspecialchars($systemLogo) . '" alt="Logo" class="logo"><br>';
}

$html .= '
        <div class="bold" style="font-size: 16px;">' . htmlspecialchars($systemName) . '</div>';

// Add store information
if (!empty($storeAddress)) {
    $html .= '<div style="font-size: 10px;">' . nl2br(htmlspecialchars($storeAddress)) . '</div>';
}
if (!empty($storePhone)) {
    $html .= '<div style="font-size: 10px;">Phone: ' . htmlspecialchars($storePhone) . '</div>';
}
if (!empty($storeEmail)) {
    $html .= '<div style="font-size: 10px;">Email: ' . htmlspecialchars($storeEmail) . '</div>';
}

$html .= '
        <div style="margin: 10px 0;">
            Receipt #' . htmlspecialchars($sale['sale_number']) . '<br>
            ' . date('M j, Y H:i', strtotime($sale['created_at'])) . '
        </div>
    </div>
    
    <div class="border-top border-bottom">';

foreach ($saleItems as $item) {
    $html .= '
        <div class="item">
            <div class="item-name">' . htmlspecialchars($item['product_name']) . '</div>
            <div class="flex">
                <span>' . $item['quantity'] . ' x ' . formatCurrency($item['unit_price']) . '</span>
                <span>' . formatCurrency($item['total_price']) . '</span>
            </div>
        </div>';
}

$html .= '
    </div>
    
    <div class="total-section">
        <div class="total-line">
            <span>Subtotal:</span>
            <span>' . formatCurrency($sale['subtotal']) . '</span>
        </div>';

if ($sale['tax_amount'] > 0) {
    $html .= '
        <div class="total-line">
            <span>Tax:</span>
            <span>' . formatCurrency($sale['tax_amount']) . '</span>
        </div>';
}

if ($sale['discount_amount'] > 0) {
    $html .= '
        <div class="total-line">
            <span>Discount:</span>
            <span>-' . formatCurrency($sale['discount_amount']) . '</span>
        </div>';
}

$html .= '
        <div class="total-line final-total border-top">
            <span>TOTAL:</span>
            <span>' . formatCurrency($sale['total_amount']) . '</span>
        </div>
    </div>
      <div class="center" style="margin-top: 15px;">
        <div>Payment: ' . strtoupper($sale['payment_method']) . '</div>';

if ($sale['customer_name']) {
    $html .= '<div>Customer: ' . htmlspecialchars($sale['customer_name']) . '</div>';
}

$html .= '
        <div>Served by: ' . htmlspecialchars($sale['cashier_name']) . '</div>
        <div style="margin-top: 10px; font-size: 10px;">';

// Add custom receipt header and footer
if (!empty($receiptHeader)) {
    $html .= htmlspecialchars($receiptHeader) . '<br>';
}
if (!empty($receiptFooter)) {
    $html .= htmlspecialchars($receiptFooter);
}

$html .= '
        </div>
    </div>
</body>
</html>';

echo json_encode(['success' => true, 'html' => $html]);
?>
