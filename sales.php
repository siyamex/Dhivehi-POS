<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$saleId = $_GET['id'] ?? null;

if ($action === 'view' && $saleId) {
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
        showAlert('Sale not found!', 'error');
        redirect('sales.php');
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
}

if ($action === 'list') {
    // Get filters
    $search = $_GET['search'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $paymentMethod = $_GET['payment_method'] ?? '';
    $cashier = $_GET['cashier'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    
    // Build query
    $where = ['1=1'];
    $params = [];
    
    if ($search) {
        $where[] = "(s.sale_number LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($dateFrom) {
        $where[] = "DATE(s.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $where[] = "DATE(s.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    if ($paymentMethod) {
        $where[] = "s.payment_method = ?";
        $params[] = $paymentMethod;
    }
    
    if ($cashier) {
        $where[] = "s.user_id = ?";
        $params[] = $cashier;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $query = "
        SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
               u.first_name as cashier_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE $whereClause 
        ORDER BY s.created_at DESC
    ";
    
    $result = paginate($query, $params, $page, 20);
    $sales = $result['data'];
    $pagination = $result;
    
    // Get summary statistics for the filtered results
    $summaryQuery = "
        SELECT COUNT(*) as total_sales, 
               COALESCE(SUM(total_amount), 0) as total_revenue,
               COALESCE(AVG(total_amount), 0) as avg_sale_amount
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE $whereClause
    ";
    $stmt = $db->prepare($summaryQuery);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all users for cashier filter
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE role IN ('admin', 'cashier', 'manager') ORDER BY first_name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = $action === 'view' ? 'Sale Details' : 'Sales History';
ob_start();
?>

<?php if ($action === 'list'): ?>
<!-- Sales List -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Sales History</h1>
        <a href="pos.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
            <i class="fas fa-cash-register mr-2"></i>New Sale
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-chart-line text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Sales</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo number_format($summary['total_sales']); ?></dd>
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
                            <i class="fas fa-dollar-sign text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_revenue']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-calculator text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Average Sale</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['avg_sale_amount']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <input type="hidden" name="action" value="list">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" placeholder="Sale # or customer..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="card" <?php echo $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                    <option value="digital" <?php echo $paymentMethod === 'digital' ? 'selected' : ''; ?>>Digital</option>
                    <option value="credit" <?php echo $paymentMethod === 'credit' ? 'selected' : ''; ?>>Credit</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cashier</label>
                <select name="cashier" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Cashiers</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $cashier == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-150">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-chart-line text-4xl mb-4"></i>
                                <p>No sales found</p>
                                <a href="pos.php" class="text-primary hover:text-blue-700">Make your first sale</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['sale_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($sale['created_at'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($sale['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatCurrency($sale['total_amount']); ?></div>
                                    <?php if ($sale['discount_amount'] > 0): ?>
                                        <div class="text-sm text-green-600">-<?php echo formatCurrency($sale['discount_amount']); ?> discount</div>
                                    <?php endif; ?>
                                </td>                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : 
                                                   ($sale['payment_method'] === 'card' ? 'bg-blue-100 text-blue-800' : 
                                                   ($sale['payment_method'] === 'credit' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800')); ?>">
                                        <?php echo ucfirst($sale['payment_method']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($sale['cashier_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="sales.php?action=view&id=<?php echo $sale['id']; ?>" class="text-primary hover:text-blue-700 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button onclick="printSaleReceipt(<?php echo $sale['id']; ?>)" class="text-gray-600 hover:text-gray-800">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo ($pagination['current_page'] - 1) * $pagination['per_page'] + 1; ?></span> to 
                                <span class="font-medium"><?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total_records']); ?></span> of 
                                <span class="font-medium"><?php echo $pagination['total_records']; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <?php if ($i == $pagination['current_page']): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-primary text-sm font-medium text-white bg-primary"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Sale Details -->
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <a href="sales.php" class="text-gray-600 hover:text-gray-800 mr-4">
                <i class="fas fa-arrow-left"></i> Back to Sales
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Sale Details</h1>
        </div>
        <button onclick="printSaleReceipt(<?php echo $sale['id']; ?>)" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
            <i class="fas fa-print mr-2"></i>Print Receipt
        </button>
    </div>

    <!-- Sale Information -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Sale Information</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Sale Details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Sale Number</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['sale_number']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Date & Time</dt>
                            <dd class="text-sm text-gray-600"><?php echo date('F j, Y \a\t H:i', strtotime($sale['created_at'])); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Payment Method</dt>                            <dd class="text-sm text-gray-600">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $sale['payment_method'] === 'cash' ? 'bg-green-100 text-green-800' : 
                                               ($sale['payment_method'] === 'card' ? 'bg-blue-100 text-blue-800' : 
                                               ($sale['payment_method'] === 'credit' ? 'bg-yellow-100 text-yellow-800' : 'bg-purple-100 text-purple-800')); ?>">
                                    <?php echo ucfirst($sale['payment_method']); ?>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Cashier</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['cashier_name']); ?></dd>
                        </div>
                    </dl>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Customer Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Customer</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?></dd>
                        </div>
                        <?php if ($sale['customer_email']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Email</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['customer_email']); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if ($sale['customer_phone']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Phone</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['customer_phone']); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if ($sale['notes']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Notes</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($sale['notes']); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Sale Items -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Items Purchased</h2>
        </div>
        <div class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($saleItems as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['sku']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $item['quantity']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo formatCurrency($item['unit_price']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatCurrency($item['total_price']); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Sale Totals -->
            <div class="bg-gray-50 px-6 py-4">
                <div class="flex justify-end">
                    <div class="w-64">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-600">Subtotal:</span>
                            <span class="text-sm font-medium"><?php echo formatCurrency($sale['subtotal']); ?></span>
                        </div>
                        <?php if ($sale['tax_amount'] > 0): ?>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-600">Tax:</span>
                            <span class="text-sm font-medium"><?php echo formatCurrency($sale['tax_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($sale['discount_amount'] > 0): ?>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-600">Discount:</span>
                            <span class="text-sm font-medium text-green-600">-<?php echo formatCurrency($sale['discount_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-base font-medium text-gray-900">Total:</span>
                            <span class="text-base font-bold text-gray-900"><?php echo formatCurrency($sale['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function printSaleReceipt(saleId) {
    try {
        const response = await fetch(`print_receipt.php?id=${saleId}`);
        const data = await response.json();
        
        if (data.success) {
            const printWindow = window.open('', '', 'width=300,height=600');
            printWindow.document.write(data.html);
            printWindow.document.close();
            printWindow.print();
            printWindow.close();
        } else {
            alert('Error generating receipt: ' + data.message);
        }
    } catch (error) {
        alert('Error printing receipt: ' + error.message);
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
