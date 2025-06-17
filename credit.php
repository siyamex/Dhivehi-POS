<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_payment') {
        $creditSaleId = $_POST['credit_sale_id'];
        $amount = $_POST['amount'];
        $paymentMethod = $_POST['payment_method'];
        $notes = $_POST['notes'] ?? '';
        
        try {
            $db->beginTransaction();
            
            // Get credit sale details
            $stmt = $db->prepare("SELECT * FROM credit_sales WHERE id = ?");
            $stmt->execute([$creditSaleId]);
            $creditSale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$creditSale) {
                throw new Exception("Credit sale not found");
            }
            
            if ($amount > $creditSale['remaining_amount']) {
                throw new Exception("Payment amount cannot exceed remaining balance");
            }
            
            // Insert payment record
            $stmt = $db->prepare("
                INSERT INTO credit_payments (credit_sale_id, amount, payment_method, notes, user_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$creditSaleId, $amount, $paymentMethod, $notes, $_SESSION['user_id']]);
            
            // Update credit sale
            $newPaidAmount = $creditSale['paid_amount'] + $amount;
            $newRemainingAmount = $creditSale['original_amount'] - $newPaidAmount;
            
            $status = 'pending';
            if ($newRemainingAmount == 0) {
                $status = 'paid';
            } elseif ($newPaidAmount > 0) {
                $status = 'partial';
            }
            
            $stmt = $db->prepare("
                UPDATE credit_sales 
                SET paid_amount = ?, remaining_amount = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$newPaidAmount, $newRemainingAmount, $status, $creditSaleId]);
            
            $db->commit();
            showAlert('Payment recorded successfully', 'success');
            redirect('credit.php');
            
        } catch (Exception $e) {
            $db->rollBack();
            showAlert('Error recording payment: ' . $e->getMessage(), 'error');
        }
    }
}

// Get credit sales list
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $where = "WHERE 1=1";
    $params = [];
    
    if ($search) {
        $where .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR s.sale_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($status) {
        $where .= " AND cs.status = ?";
        $params[] = $status;
    }
    
    $stmt = $db->prepare("
        SELECT cs.*, 
               s.sale_number, s.created_at as sale_date,
               c.first_name, c.last_name, c.email, c.phone,
               u.first_name as cashier_first_name, u.last_name as cashier_last_name
        FROM credit_sales cs
        JOIN sales s ON cs.sale_id = s.id
        JOIN customers c ON cs.customer_id = c.id
        JOIN users u ON s.user_id = u.id
        $where
        ORDER BY cs.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $creditSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM credit_sales cs
        JOIN sales s ON cs.sale_id = s.id
        JOIN customers c ON cs.customer_id = c.id
        $where
    ");
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get summary statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_credit_sales,
            SUM(original_amount) as total_credit_amount,
            SUM(paid_amount) as total_paid_amount,
            SUM(remaining_amount) as total_outstanding
        FROM credit_sales 
        WHERE status != 'paid'
    ");
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get specific credit sale for payment
if ($action === 'pay' && $id) {
    $stmt = $db->prepare("
        SELECT cs.*, 
               s.sale_number, s.created_at as sale_date,
               c.first_name, c.last_name, c.email, c.phone
        FROM credit_sales cs
        JOIN sales s ON cs.sale_id = s.id
        JOIN customers c ON cs.customer_id = c.id
        WHERE cs.id = ?
    ");
    $stmt->execute([$id]);
    $creditSale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$creditSale) {
        showAlert('Credit sale not found', 'error');
        redirect('credit.php');
    }
    
    // Get payment history
    $stmt = $db->prepare("
        SELECT cp.*, u.first_name, u.last_name 
        FROM credit_payments cp
        JOIN users u ON cp.user_id = u.id
        WHERE cp.credit_sale_id = ?
        ORDER BY cp.payment_date DESC
    ");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <?php if ($action === 'pay'): ?>
                Credit Payment
            <?php else: ?>
                Credit Management
            <?php endif; ?>
        </h1>
        <?php if ($action === 'pay'): ?>
            <a href="credit.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>Back to Credit Sales
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-yellow-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Credit Sales</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($summary['total_credit_sales']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-money-bill-wave text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Credit Amount</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_credit_amount']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Paid</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_paid_amount']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Outstanding Amount</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_outstanding']); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="action" value="list">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Customer name or sale number"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial Payment</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
                
                <div class="flex items-end">
                    <a href="credit.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg w-full text-center">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Credit Sales Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($creditSales)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                No credit sales found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($creditSales as $creditSale): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($creditSale['sale_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($creditSale['first_name'] . ' ' . $creditSale['last_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($creditSale['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($creditSale['sale_date'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($creditSale['sale_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatCurrency($creditSale['original_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo formatCurrency($creditSale['paid_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatCurrency($creditSale['remaining_amount']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $creditSale['due_date'] ? date('M j, Y', strtotime($creditSale['due_date'])) : 'No due date'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php 
                                        switch($creditSale['status']) {
                                            case 'paid': echo 'bg-green-100 text-green-800'; break;
                                            case 'partial': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'overdue': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($creditSale['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($creditSale['status'] !== 'paid'): ?>
                                        <a href="credit.php?action=pay&id=<?php echo $creditSale['id']; ?>" class="text-primary hover:text-blue-700 mr-3">
                                            <i class="fas fa-money-bill mr-1"></i>Pay
                                        </a>
                                    <?php endif; ?>
                                    <a href="sales.php?action=view&id=<?php echo $creditSale['sale_id']; ?>" class="text-gray-600 hover:text-gray-800">
                                        <i class="fas fa-eye mr-1"></i>View Sale
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?action=list&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?action=list&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?action=list&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                                   <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($action === 'pay' && isset($creditSale)): ?>
        <!-- Payment Form -->
        <div class="max-w-4xl mx-auto">
            <!-- Credit Sale Information -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Credit Sale Information</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Sale Number</dt>
                                    <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($creditSale['sale_number']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Customer</dt>
                                    <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($creditSale['first_name'] . ' ' . $creditSale['last_name']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Sale Date</dt>
                                    <dd class="text-sm text-gray-600"><?php echo date('F j, Y \a\t H:i', strtotime($creditSale['sale_date'])); ?></dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Original Amount</dt>
                                    <dd class="text-sm text-gray-600"><?php echo formatCurrency($creditSale['original_amount']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Paid Amount</dt>
                                    <dd class="text-sm text-gray-600"><?php echo formatCurrency($creditSale['paid_amount']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-900">Remaining Balance</dt>
                                    <dd class="text-lg font-bold text-red-600"><?php echo formatCurrency($creditSale['remaining_amount']); ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Record Payment</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="credit.php?action=add_payment">
                        <input type="hidden" name="credit_sale_id" value="<?php echo $creditSale['id']; ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount *</label>
                                <input type="number" name="amount" step="0.01" min="0.01" max="<?php echo $creditSale['remaining_amount']; ?>" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="<?php echo formatCurrency($creditSale['remaining_amount']); ?>">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                                <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Select Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="digital">Digital</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Optional payment notes"></textarea>
                        </div>
                        
                        <div class="flex justify-end mt-6">
                            <button type="submit" class="bg-primary hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                <i class="fas fa-save mr-2"></i>Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payments)): ?>
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Payment History</h3>
                    </div>
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M j, Y H:i', strtotime($payment['payment_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo formatCurrency($payment['amount']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo ucfirst($payment['payment_method']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($payment['notes'] ?: 'No notes'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
