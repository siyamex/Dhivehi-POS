<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$customerId = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    if ($action === 'add' || $action === 'edit') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $postalCode = trim($_POST['postal_code']);
        
        $errors = [];
        
        if (empty($firstName)) $errors[] = 'First name is required';
        if (empty($lastName)) $errors[] = 'Last name is required';
        
        // Check for duplicate email if provided
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            } else {
                if ($action === 'add') {
                    $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Email already exists';
                    }
                } else {
                    $stmt = $db->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $customerId]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Email already exists';
                    }
                }
            }
        }
        
        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO customers (first_name, last_name, email, phone, address, city, postal_code) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$firstName, $lastName, $email ?: null, $phone, $address, $city, $postalCode]);
                showAlert('Customer added successfully!', 'success');
            } else {
                $stmt = $db->prepare("
                    UPDATE customers 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$firstName, $lastName, $email ?: null, $phone, $address, $city, $postalCode, $customerId]);
                showAlert('Customer updated successfully!', 'success');
            }
            redirect('customers.php');
        }
    }
}

if ($action === 'edit' && $customerId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        showAlert('Customer not found!', 'error');
        redirect('customers.php');
    }
}

if ($action === 'view' && $customerId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        showAlert('Customer not found!', 'error');
        redirect('customers.php');
    }
    
    // Get customer's purchase history
    $stmt = $db->prepare("
        SELECT s.*, u.first_name as cashier_name
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.customer_id = ?
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$customerId]);
    $recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($action === 'list') {
    // Get filters
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    
    // Build query
    $where = ['1=1'];
    $params = [];
    
    if ($search) {
        $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $query = "
        SELECT *, CONCAT(first_name, ' ', last_name) as full_name
        FROM customers 
        WHERE $whereClause 
        ORDER BY first_name, last_name
    ";
    
    $result = paginate($query, $params, $page, 20);
    $customers = $result['data'];
    $pagination = $result;
}

$pageTitle = $action === 'add' ? 'Add Customer' : ($action === 'edit' ? 'Edit Customer' : ($action === 'view' ? 'Customer Details' : 'Customers'));
ob_start();
?>

<?php if ($action === 'list'): ?>
<!-- Customers List -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
        <a href="customers.php?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
            <i class="fas fa-user-plus mr-2"></i>Add Customer
        </a>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="flex gap-4">
            <input type="hidden" name="action" value="list">
            <div class="flex-1">
                <input type="text" name="search" placeholder="Search customers by name, email, or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-150">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Summary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4"></i>
                                <p>No customers found</p>
                                <a href="customers.php?action=add" class="text-primary hover:text-blue-700">Add your first customer</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center">
                                                <span class="text-white font-medium text-sm">
                                                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                            <div class="text-sm text-gray-500">Customer since <?php echo date('M Y', strtotime($customer['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($customer['email'] ?: 'No email'); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['phone'] ?: 'No phone'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($customer['city'] ?: 'No city'); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['postal_code'] ?: 'No postal code'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo formatCurrency($customer['total_purchases']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo number_format($customer['loyalty_points']); ?> loyalty points</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="customers.php?action=view&id=<?php echo $customer['id']; ?>" class="text-primary hover:text-blue-700 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="customers.php?action=edit&id=<?php echo $customer['id']; ?>" class="text-green-600 hover:text-green-700">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
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

<?php elseif ($action === 'view'): ?>
<!-- Customer Details -->
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center">
        <a href="customers.php" class="text-gray-600 hover:text-gray-800 mr-4">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Customer Details</h1>
    </div>

    <!-- Customer Info Card -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
            <a href="customers.php?action=edit&id=<?php echo $customer['id']; ?>" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                <i class="fas fa-edit mr-2"></i>Edit Customer
            </a>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Personal Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Full Name</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Email</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($customer['email'] ?: 'Not provided'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Phone</dt>
                            <dd class="text-sm text-gray-600"><?php echo htmlspecialchars($customer['phone'] ?: 'Not provided'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Customer Since</dt>
                            <dd class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></dd>
                        </div>
                    </dl>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-4">Address & Purchase Summary</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Address</dt>
                            <dd class="text-sm text-gray-600">
                                <?php if ($customer['address']): ?>
                                    <?php echo htmlspecialchars($customer['address']); ?><br>
                                    <?php echo htmlspecialchars($customer['city']); ?> <?php echo htmlspecialchars($customer['postal_code']); ?>
                                <?php else: ?>
                                    Not provided
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Total Purchases</dt>
                            <dd class="text-sm text-gray-600"><?php echo formatCurrency($customer['total_purchases']); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-900">Loyalty Points</dt>
                            <dd class="text-sm text-gray-600"><?php echo number_format($customer['loyalty_points']); ?> points</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Purchases -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Purchases</h2>
        </div>
        <div class="overflow-hidden">
            <?php if (empty($recentSales)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                    <p>No purchases yet</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($sale['sale_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y H:i', strtotime($sale['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatCurrency($sale['total_amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <?php echo ucfirst($sale['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($sale['cashier_name']); ?>
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

<?php else: ?>
<!-- Add/Edit Customer Form -->
<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="customers.php" class="text-gray-600 hover:text-gray-800 mr-4">
            <i class="fas fa-arrow-left"></i> Back to Customers
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $action === 'add' ? 'Add New Customer' : 'Edit Customer'; ?></h1>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form method="POST" class="p-6 space-y-6">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" required 
                           value="<?php echo htmlspecialchars($customer['first_name'] ?? $_POST['first_name'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" required 
                           value="<?php echo htmlspecialchars($customer['last_name'] ?? $_POST['last_name'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" 
                           value="<?php echo htmlspecialchars($customer['email'] ?? $_POST['email'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" 
                           value="<?php echo htmlspecialchars($customer['phone'] ?? $_POST['phone'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($customer['address'] ?? $_POST['address'] ?? ''); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" 
                           value="<?php echo htmlspecialchars($customer['city'] ?? $_POST['city'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                    <input type="text" name="postal_code" 
                           value="<?php echo htmlspecialchars($customer['postal_code'] ?? $_POST['postal_code'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t">
                <a href="customers.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition duration-150">
                    <?php echo $action === 'add' ? 'Add Customer' : 'Update Customer'; ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
