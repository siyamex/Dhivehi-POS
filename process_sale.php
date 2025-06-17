<?php
require_once 'includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale data']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Calculate totals
    $subtotal = 0;
    $taxAmount = 0;
    $saleItems = [];
    
    // Validate items and calculate totals
    foreach ($input['items'] as $item) {
        // Get current product data
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found: " . $item['name']);
        }
        
        if ($product['stock_quantity'] < $item['quantity']) {
            throw new Exception("Insufficient stock for product: " . $product['name']);
        }
        
        $itemTotal = $product['selling_price'] * $item['quantity'];
        $itemTax = ($itemTotal * $product['tax_rate']) / 100;
        
        $saleItems[] = [
            'product_id' => $product['id'],
            'quantity' => $item['quantity'],
            'unit_price' => $product['selling_price'],
            'total_price' => $itemTotal,
            'tax_amount' => $itemTax,
            'name' => $product['name']
        ];
        
        $subtotal += $itemTotal;
        $taxAmount += $itemTax;
    }
    
    // Apply discount if selected
    $discountAmount = 0;
    if (!empty($input['discount_id'])) {
        $stmt = $db->prepare("
            SELECT * FROM discounts 
            WHERE id = ? AND status = 'active' 
            AND (start_date IS NULL OR start_date <= CURDATE()) 
            AND (end_date IS NULL OR end_date >= CURDATE())
        ");
        $stmt->execute([$input['discount_id']]);
        $discount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($discount && $subtotal >= $discount['min_amount']) {
            if ($discount['type'] === 'percentage') {
                $discountAmount = ($subtotal * $discount['value']) / 100;
                if ($discount['max_discount'] && $discountAmount > $discount['max_discount']) {
                    $discountAmount = $discount['max_discount'];
                }
            } else {
                $discountAmount = $discount['value'];
            }
        }
    }
    
    $totalAmount = $subtotal + $taxAmount - $discountAmount;
      // Generate sale number
    $saleNumber = generateSaleNumber();
    
    // Determine payment status based on payment method
    $paymentStatus = ($input['payment_method'] === 'credit') ? 'credit' : 'paid';
    
    // Insert sale record
    $stmt = $db->prepare("
        INSERT INTO sales (sale_number, customer_id, user_id, subtotal, tax_amount, discount_amount, total_amount, payment_method, payment_status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $notes = '';
    if ($input['payment_method'] === 'credit' && !empty($input['credit_notes'])) {
        $notes = $input['credit_notes'];
    }
    
    $stmt->execute([
        $saleNumber,
        $input['customer_id'] ?: null,
        $_SESSION['user_id'],
        $subtotal,
        $taxAmount,
        $discountAmount,
        $totalAmount,
        $input['payment_method'],
        $paymentStatus,
        $notes
    ]);
    
    $saleId = $db->lastInsertId();
    
    // If this is a credit sale, create credit sale record
    if ($input['payment_method'] === 'credit') {
        if (!$input['customer_id']) {
            throw new Exception("Customer is required for credit sales");
        }
        
        $dueDate = null;
        if (!empty($input['credit_due_date'])) {
            $dueDate = $input['credit_due_date'];
        }
        
        $stmt = $db->prepare("
            INSERT INTO credit_sales (sale_id, customer_id, original_amount, remaining_amount, due_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $saleId,
            $input['customer_id'],
            $totalAmount,
            $totalAmount, // Initially, remaining amount equals total amount
            $dueDate,
            $notes
        ]);
    }
    
    // Insert sale items and update stock
    foreach ($saleItems as $item) {
        // Insert sale item
        $stmt = $db->prepare("
            INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price, tax_amount) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $saleId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price'],
            $item['tax_amount']
        ]);
        
        // Update product stock
        $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
        
        // Record stock movement
        $stmt = $db->prepare("
            INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id) 
            VALUES (?, 'out', ?, 'sale', ?, ?)
        ");
        $stmt->execute([$item['product_id'], $item['quantity'], $saleId, $_SESSION['user_id']]);
    }
    
    // Update customer total purchases if customer is selected
    if ($input['customer_id']) {
        $stmt = $db->prepare("UPDATE customers SET total_purchases = total_purchases + ? WHERE id = ?");
        $stmt->execute([$totalAmount, $input['customer_id']]);
        
        // Add loyalty points (1 point per dollar spent)
        $loyaltyPoints = floor($totalAmount);
        $stmt = $db->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?");
        $stmt->execute([$loyaltyPoints, $input['customer_id']]);
    }
    
    $db->commit();
    
    // Prepare response with sale details
    $sale = [
        'id' => $saleId,
        'sale_number' => $saleNumber,
        'subtotal' => $subtotal,
        'tax_amount' => $taxAmount,
        'discount_amount' => $discountAmount,
        'total_amount' => $totalAmount,
        'payment_method' => $input['payment_method'],
        'items' => $saleItems
    ];
    
    echo json_encode(['success' => true, 'sale' => $sale]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
