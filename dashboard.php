<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();

// Get dashboard statistics
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

// Today's sales
$stmt = $db->prepare("
    SELECT COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_revenue 
    FROM sales 
    WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid'
");
$stmt->execute([$todayStart, $todayEnd]);
$todayStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Low stock products
$stmt = $db->prepare("
    SELECT COUNT(*) as low_stock_count 
    FROM products 
    WHERE stock_quantity <= min_stock_level AND status = 'active'
");
$stmt->execute();
$lowStockCount = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock_count'];

// Total customers
$stmt = $db->prepare("SELECT COUNT(*) as total_customers FROM customers");
$stmt->execute();
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Recent sales
$stmt = $db->prepare("
    SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, u.first_name as cashier_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock_quantity <= p.min_stock_level AND p.status = 'active'
    ORDER BY p.stock_quantity ASC
    LIMIT 5
");
$stmt->execute();
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sales chart data for the last 7 days
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as daily_sales 
        FROM sales 
        WHERE DATE(created_at) = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$date]);
    $dailySales = $stmt->fetch(PDO::FETCH_ASSOC)['daily_sales'];
    $chartData[] = [
        'date' => date('M j', strtotime($date)),
        'sales' => $dailySales
    ];
}

$pageTitle = 'Dashboard';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! Here's what's happening in your store today.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Sales -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-green-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Today's Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($todayStats['total_revenue']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-gray-600"><?php echo $todayStats['total_sales']; ?> sales today</span>
                </div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-box text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Products</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalProducts); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="products.php" class="text-blue-600 hover:text-blue-500">View all products</a>
                </div>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-users text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalCustomers); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="customers.php" class="text-purple-600 hover:text-purple-500">View all customers</a>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Low Stock Items</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $lowStockCount; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <?php if ($lowStockCount > 0): ?>
                        <span class="text-red-600">Requires attention</span>
                    <?php else: ?>
                        <span class="text-green-600">All items in stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Sales -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Sales</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($recentSales)): ?>
                    <div class="px-6 py-4 text-center text-gray-500">
                        No sales recorded yet today.
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($recentSales as $sale): ?>
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($sale['sale_number']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?> • 
                                                <?php echo htmlspecialchars($sale['cashier_name']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo formatCurrency($sale['total_amount']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('H:i', strtotime($sale['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="px-6 py-3 bg-gray-50">
                        <a href="sales.php" class="text-sm text-primary hover:text-blue-700">View all sales →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Low Stock Alert</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                        <p>All products are well stocked!</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($product['category_name'] ?: 'No Category'); ?> • 
                                                SKU: <?php echo htmlspecialchars($product['sku']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium <?php echo $product['stock_quantity'] <= 0 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Min: <?php echo $product['min_stock_level']; ?>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="px-6 py-3 bg-gray-50">
                        <a href="products.php?filter=low_stock" class="text-sm text-primary hover:text-blue-700">View all low stock items →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="pos.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-150">
                <i class="fas fa-cash-register text-2xl text-primary mb-2"></i>
                <span class="text-sm font-medium text-gray-900">New Sale</span>
            </a>
            <a href="products.php?action=add" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-150">
                <i class="fas fa-plus-circle text-2xl text-green-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Add Product</span>
            </a>
            <a href="customers.php?action=add" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-150">
                <i class="fas fa-user-plus text-2xl text-purple-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Add Customer</span>
            </a>
            <?php if (hasRole('admin') || hasRole('manager')): ?>
            <a href="reports.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-150">
                <i class="fas fa-chart-bar text-2xl text-blue-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">View Reports</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto-refresh dashboard every 5 minutes
    setTimeout(() => {
        window.location.reload();
    }, 300000);
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
