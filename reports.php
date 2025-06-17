<?php
require_once 'includes/functions.php';
requireLogin();

// Check if user has permission to view reports
if (!hasRole('admin') && !hasRole('manager')) {
    showAlert('Access denied. You do not have permission to view reports.', 'error');
    redirect('dashboard.php');
}

$db = getDB();

// Get date range for reports
$dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$dateTo = $_GET['date_to'] ?? date('Y-m-d'); // Today
$reportType = $_GET['report_type'] ?? 'summary';

// Summary Statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_sales,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(subtotal), 0) as total_subtotal,
        COALESCE(SUM(tax_amount), 0) as total_tax,
        COALESCE(SUM(discount_amount), 0) as total_discounts,
        COALESCE(AVG(total_amount), 0) as avg_sale_amount
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ? 
    AND payment_status = 'paid'
");
$stmt->execute([$dateFrom, $dateTo]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Daily Sales Chart Data
$dailySales = [];
$current = strtotime($dateFrom);
$end = strtotime($dateTo);

while ($current <= $end) {
    $date = date('Y-m-d', $current);
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as daily_total 
        FROM sales 
        WHERE DATE(created_at) = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$date]);
    $dayTotal = $stmt->fetch(PDO::FETCH_ASSOC)['daily_total'];
    
    $dailySales[] = [
        'date' => date('M j', $current),
        'total' => $dayTotal
    ];
    
    $current = strtotime('+1 day', $current);
}

// Top Selling Products
$stmt = $db->prepare("
    SELECT p.name, p.sku, SUM(si.quantity) as total_quantity, 
           SUM(si.total_price) as total_revenue,
           COUNT(DISTINCT si.sale_id) as times_sold
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ? AND s.payment_status = 'paid'
    GROUP BY p.id, p.name, p.sku
    ORDER BY total_quantity DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment Method Breakdown
$stmt = $db->prepare("
    SELECT payment_method, 
           COUNT(*) as transaction_count,
           SUM(total_amount) as total_amount
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'
    GROUP BY payment_method
    ORDER BY total_amount DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Customers
$stmt = $db->prepare("
    SELECT CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           c.email, COUNT(s.id) as purchase_count,
           SUM(s.total_amount) as total_spent
    FROM customers c
    JOIN sales s ON c.id = s.customer_id
    WHERE DATE(s.created_at) BETWEEN ? AND ? AND s.payment_status = 'paid'
    GROUP BY c.id, c.first_name, c.last_name, c.email
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$dateFrom, $dateTo]);
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low Stock Products
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock_quantity <= p.min_stock_level AND p.status = 'active'
    ORDER BY (p.stock_quantity / p.min_stock_level) ASC
    LIMIT 10
");
$stmt->execute();
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cashier Performance
$stmt = $db->prepare("
    SELECT u.first_name, u.last_name,
           COUNT(s.id) as sales_count,
           SUM(s.total_amount) as total_sales
    FROM users u
    JOIN sales s ON u.id = s.user_id
    WHERE DATE(s.created_at) BETWEEN ? AND ? AND s.payment_status = 'paid'
    GROUP BY u.id, u.first_name, u.last_name
    ORDER BY total_sales DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$cashierPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Reports & Analytics';
ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
        <button onclick="window.print()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-search mr-2"></i>Update Report
            </button>
        </form>
        <p class="text-sm text-gray-600 mt-2">
            Report Period: <?php echo date('F j, Y', strtotime($dateFrom)); ?> to <?php echo date('F j, Y', strtotime($dateTo)); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_revenue']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm text-gray-600"><?php echo number_format($summary['total_sales']); ?> transactions</div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-calculator text-blue-600"></i>
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
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm text-gray-600">Per transaction</div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-tags text-purple-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Discounts</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_discounts']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm text-gray-600">
                    <?php 
                    $discountPercentage = $summary['total_revenue'] > 0 ? ($summary['total_discounts'] / ($summary['total_revenue'] + $summary['total_discounts']) * 100) : 0;
                    echo number_format($discountPercentage, 1); 
                    ?>% of gross sales
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-receipt text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Tax Collected</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($summary['total_tax']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm text-gray-600">Total tax amount</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Sales Chart -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Daily Sales Trend</h3>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Payment Methods</h3>
            </div>
            <div class="p-6">
                <?php if (empty($paymentMethods)): ?>
                    <div class="text-center text-gray-500 py-8">No sales data available</div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($paymentMethods as $method): ?>
                            <?php 
                            $percentage = $summary['total_revenue'] > 0 ? ($method['total_amount'] / $summary['total_revenue'] * 100) : 0;
                            ?>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-900"><?php echo ucfirst($method['payment_method']); ?></span>
                                    <span class="text-sm text-gray-600"><?php echo formatCurrency($method['total_amount']); ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo $method['transaction_count']; ?> transactions (<?php echo number_format($percentage, 1); ?>%)
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Selling Products -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Selling Products</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($topProducts)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">No product sales data</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format($product['total_quantity']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($product['total_revenue']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Customers</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($topCustomers)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">No customer data available</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchases</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($topCustomers as $customer): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format($customer['purchase_count']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($customer['total_spent']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cashier Performance & Low Stock -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Cashier Performance -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Cashier Performance</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($cashierPerformance)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">No cashier data available</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cashier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sales</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($cashierPerformance as $cashier): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($cashier['first_name'] . ' ' . $cashier['last_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format($cashier['sales_count']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo formatCurrency($cashier['total_sales']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Low Stock Alert</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                        <p>All products are well stocked!</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium <?php echo $product['stock_quantity'] <= 0 ? 'text-red-600' : 'text-yellow-600'; ?>">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </div>
                                        <div class="text-sm text-gray-500">Min: <?php echo $product['min_stock_level']; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="px-6 py-3 bg-gray-50">
                        <a href="products.php?filter=low_stock" class="text-sm text-primary hover:text-blue-700">
                            View all low stock items →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const ctx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesData = <?php echo json_encode($dailySales); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailySalesData.map(item => item.date),
        datasets: [{
            label: 'Daily Sales',
            data: dailySalesData.map(item => parseFloat(item.total)),
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(2);
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
