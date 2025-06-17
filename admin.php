<?php
require_once 'includes/functions.php';
requireRole('admin');

$db = getDB();
$action = $_GET['action'] ?? 'dashboard';

// Handle form submissions
if ($_POST) {
    if ($action === 'add_user' || $action === 'edit_user') {
        $userId = $_GET['id'] ?? null;
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        $password = $_POST['password'] ?? '';
        
        $errors = [];
        
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($firstName)) $errors[] = 'First name is required';
        if (empty($lastName)) $errors[] = 'Last name is required';
        if ($action === 'add_user' && empty($password)) $errors[] = 'Password is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
        
        // Check for duplicate username/email
        if ($action === 'add_user') {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists';
            }
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email already exists';
            }
        }
        
        if (empty($errors)) {
            if ($action === 'add_user') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $role, $status]);
                showAlert('User added successfully!', 'success');
            } else {
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, password = ?, first_name = ?, last_name = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $role, $status, $userId]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $firstName, $lastName, $role, $status, $userId]);
                }
                showAlert('User updated successfully!', 'success');
            }
            redirect('admin.php?action=users');
        }
    } elseif ($action === 'add_category' || $action === 'edit_category') {
        $categoryId = $_GET['id'] ?? null;
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        
        $errors = [];
        
        if (empty($name)) $errors[] = 'Category name is required';
        
        // Check for duplicate name
        if ($action === 'add_category') {
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $errors[] = 'Category name already exists';
            }
        } else {
            $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$name, $categoryId]);
            if ($stmt->fetch()) {
                $errors[] = 'Category name already exists';
            }
        }
        
        if (empty($errors)) {
            if ($action === 'add_category') {
                $stmt = $db->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $status]);
                showAlert('Category added successfully!', 'success');
            } else {
                $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");                $stmt->execute([$name, $description, $status, $categoryId]);
                showAlert('Category updated successfully!', 'success');
            }
            redirect('admin.php?action=categories');
        }
    }
    
    // Handle system settings update
    if ($action === 'system_settings') {
        $errors = [];
        
        try {
            $db->beginTransaction();
              // Update text settings
            $textSettings = ['system_name', 'receipt_header', 'receipt_footer', 'store_address', 'store_phone', 'store_email', 'currency_symbol', 'tax_rate', 'default_language'];
            
            foreach ($textSettings as $setting) {
                if (isset($_POST[$setting])) {
                    $value = trim($_POST[$setting]);
                    $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
                    $stmt->execute([$value, $setting]);
                }
            }
            
            // Handle logo upload
            if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/system/';
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $maxFileSize = 2 * 1024 * 1024; // 2MB
                
                $fileType = $_FILES['system_logo']['type'];
                $fileSize = $_FILES['system_logo']['size'];
                $fileName = $_FILES['system_logo']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Validate file type
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Invalid logo file type. Only JPEG, PNG, GIF, and WebP are allowed.';
                }
                
                // Validate file size
                if ($fileSize > $maxFileSize) {
                    $errors[] = 'Logo file size must be less than 2MB.';
                }
                
                if (empty($errors)) {
                    // Generate unique filename
                    $logoName = 'logo_' . time() . '_' . uniqid() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $logoName;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Get old logo to delete
                    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'system_logo'");
                    $stmt->execute();
                    $oldLogo = $stmt->fetchColumn();
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $uploadPath)) {
                        // Update database
                        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'system_logo'");
                        $stmt->execute([$logoName]);
                        
                        // Delete old logo file
                        if ($oldLogo && file_exists($uploadDir . $oldLogo)) {
                            unlink($uploadDir . $oldLogo);
                        }
                    } else {
                        $errors[] = 'Failed to upload logo. Please try again.';
                    }
                }
            }
            
            if (empty($errors)) {
                $db->commit();
                showAlert('System settings updated successfully!', 'success');
            } else {
                $db->rollBack();
                foreach ($errors as $error) {
                    showAlert($error, 'error');
                }
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            showAlert('Error updating settings: ' . $e->getMessage(), 'error');
        }
        
        redirect('admin.php?action=system_settings');
    }
}

// Get data based on action
if ($action === 'users') {
    $stmt = $db->prepare("SELECT * FROM users ORDER BY first_name, last_name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit_user') {
    $userId = $_GET['id'] ?? null;
    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            showAlert('User not found!', 'error');
            redirect('admin.php?action=users');
        }
    }
} elseif ($action === 'categories') {
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit_category') {
    $categoryId = $_GET['id'] ?? null;
    if ($categoryId) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            showAlert('Category not found!', 'error');            redirect('admin.php?action=categories');
        }
    }
} elseif ($action === 'system_settings') {
    // Load current system settings
    $stmt = $db->prepare("SELECT * FROM system_settings ORDER BY setting_key");
    $stmt->execute();
    $allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easier access
    $systemSettings = [];
    foreach ($allSettings as $setting) {
        $systemSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}else {
    // Dashboard statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE status = 'active'");
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
    $stmt->execute();
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total_customers FROM customers");
    $stmt->execute();
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as total_categories FROM categories WHERE status = 'active'");
    $stmt->execute();
    $totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
    
    // Recent activity
    $stmt = $db->prepare("
        SELECT 'sale' as type, sale_number as description, created_at, total_amount as amount 
        FROM sales 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Admin Panel';
ob_start();
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
        <div class="text-sm text-gray-600">
            Logged in as: <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="admin.php" class="<?php echo $action === 'dashboard' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <a href="admin.php?action=users" class="<?php echo $action === 'users' || $action === 'add_user' || $action === 'edit_user' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-users mr-2"></i>Users
            </a>            <a href="admin.php?action=categories" class="<?php echo $action === 'categories' || $action === 'add_category' || $action === 'edit_category' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-tags mr-2"></i>Categories
            </a>
            <a href="admin.php?action=system_settings" class="<?php echo $action === 'system_settings' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-cog mr-2"></i>System Settings
            </a>
        </nav>
    </div>

    <?php if ($action === 'dashboard'): ?>
        <!-- Dashboard Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalUsers); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="admin.php?action=users" class="text-sm text-primary hover:text-blue-700">Manage users</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-box text-green-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Products</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalProducts); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="products.php" class="text-sm text-primary hover:text-blue-700">Manage products</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-user-friends text-purple-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Customers</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalCustomers); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="customers.php" class="text-sm text-primary hover:text-blue-700">Manage customers</a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                <i class="fas fa-tags text-yellow-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Categories</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo number_format($totalCategories); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <a href="admin.php?action=categories" class="text-sm text-primary hover:text-blue-700">Manage categories</a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div class="overflow-hidden">
                <?php if (empty($recentActivity)): ?>
                    <div class="px-6 py-8 text-center text-gray-500">No recent activity</div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($recentActivity as $activity): ?>
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-shopping-cart text-green-500"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                New sale: <?php echo htmlspecialchars($activity['description']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-sm font-medium text-green-600">
                                        <?php echo formatCurrency($activity['amount']); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'users'): ?>
        <!-- Users Management -->
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">User Management</h2>
            <a href="admin.php?action=add_user" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                <i class="fas fa-user-plus mr-2"></i>Add User
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                               ($user['role'] === 'manager' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin.php?action=edit_user&id=<?php echo $user['id']; ?>" class="text-primary hover:text-blue-700">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action === 'add_user' || $action === 'edit_user'): ?>
        <!-- Add/Edit User Form -->
        <div class="flex items-center mb-6">
            <a href="admin.php?action=users" class="text-gray-600 hover:text-gray-800 mr-4">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
            <h2 class="text-lg font-medium text-gray-900"><?php echo $action === 'add_user' ? 'Add New User' : 'Edit User'; ?></h2>
        </div>

        <div class="max-w-2xl bg-white shadow rounded-lg">
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
                               value="<?php echo htmlspecialchars($user['first_name'] ?? $_POST['first_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" required 
                               value="<?php echo htmlspecialchars($user['last_name'] ?? $_POST['last_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" name="username" required 
                           value="<?php echo htmlspecialchars($user['username'] ?? $_POST['username'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email'] ?? $_POST['email'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password <?php echo $action === 'add_user' ? '*' : '(leave blank to keep current)'; ?>
                    </label>
                    <input type="password" name="password" 
                           <?php echo $action === 'add_user' ? 'required' : ''; ?>
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                        <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="cashier" <?php echo ($user['role'] ?? $_POST['role'] ?? '') === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                            <option value="manager" <?php echo ($user['role'] ?? $_POST['role'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo ($user['role'] ?? $_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="active" <?php echo ($user['status'] ?? $_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($user['status'] ?? $_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <a href="admin.php?action=users" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition duration-150">
                        <?php echo $action === 'add_user' ? 'Add User' : 'Update User'; ?>
                    </button>
                </div>
            </form>
        </div>

    <?php elseif ($action === 'categories'): ?>
        <!-- Categories Management -->
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">Category Management</h2>
            <a href="admin.php?action=add_category" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                <i class="fas fa-plus mr-2"></i>Add Category
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($category['description'] ?: 'No description'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $category['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($category['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="admin.php?action=edit_category&id=<?php echo $category['id']; ?>" class="text-primary hover:text-blue-700">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action === 'add_category' || $action === 'edit_category'): ?>
        <!-- Add/Edit Category Form -->
        <div class="flex items-center mb-6">
            <a href="admin.php?action=categories" class="text-gray-600 hover:text-gray-800 mr-4">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
            <h2 class="text-lg font-medium text-gray-900"><?php echo $action === 'add_category' ? 'Add New Category' : 'Edit Category'; ?></h2>
        </div>

        <div class="max-w-2xl bg-white shadow rounded-lg">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                    <input type="text" name="name" required 
                           value="<?php echo htmlspecialchars($category['name'] ?? $_POST['name'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($category['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="active" <?php echo ($category['status'] ?? $_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($category['status'] ?? $_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <a href="admin.php?action=categories" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition duration-150">
                        <?php echo $action === 'add_category' ? 'Add Category' : 'Update Category'; ?>
                    </button>
                </div>
            </form>        </div>
    <?php endif; ?>

    <?php if ($action === 'system_settings'): ?>
        <!-- System Settings Content -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">System Settings</h3>
                <p class="text-sm text-gray-600 mt-1">Configure your POS system name, logo, and receipt settings</p>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column - Basic Settings -->
                    <div class="space-y-6">
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2">Basic Information</h4>
                        
                        <!-- System Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">System Name *</label>
                            <input type="text" name="system_name" required 
                                   value="<?php echo htmlspecialchars($systemSettings['system_name'] ?? 'POS System'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Your Business Name">
                            <p class="text-xs text-gray-500 mt-1">This will appear on receipts and throughout the system</p>
                        </div>
                          <!-- Currency Symbol -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                            <input type="text" name="currency_symbol" 
                                   value="<?php echo htmlspecialchars($systemSettings['currency_symbol'] ?? 'MVR'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="MVR">
                        </div>
                        
                        <!-- Default Language -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Language</label>
                            <select name="default_language" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="en" <?php echo ($systemSettings['default_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>
                                    🇺🇸 English
                                </option>
                                <option value="dv" <?php echo ($systemSettings['default_language'] ?? 'en') === 'dv' ? 'selected' : ''; ?>>
                                    🇲🇻 ދިވެހި (Dhivehi)
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Default language for new users and system interface</p>
                        </div>
                        
                        <!-- Tax Rate -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Tax Rate (%)</label>
                            <input type="number" name="tax_rate" step="0.01" min="0" max="100"
                                   value="<?php echo htmlspecialchars($systemSettings['tax_rate'] ?? '6.0'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="6.0">
                        </div>
                        
                        <!-- Store Information -->
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2 mt-8">Store Information</h4>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Store Address</label>
                            <textarea name="store_address" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Your store address"><?php echo htmlspecialchars($systemSettings['store_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="text" name="store_phone" 
                                   value="<?php echo htmlspecialchars($systemSettings['store_phone'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="+960 123-4567">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="store_email" 
                                   value="<?php echo htmlspecialchars($systemSettings['store_email'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="info@yourstore.com">
                        </div>
                    </div>
                    
                    <!-- Right Column - Logo and Receipt Settings -->
                    <div class="space-y-6">
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2">Logo & Branding</h4>
                        
                        <!-- Current Logo Display -->
                        <?php if (!empty($systemSettings['system_logo'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Logo</label>
                                <div class="flex items-center space-x-4">
                                    <img src="uploads/system/<?php echo htmlspecialchars($systemSettings['system_logo']); ?>" 
                                         alt="Current logo" 
                                         class="h-16 w-auto object-contain border border-gray-300 rounded-lg bg-white p-2">
                                    <div class="text-sm text-gray-600">
                                        <p><?php echo htmlspecialchars($systemSettings['system_logo']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo !empty($systemSettings['system_logo']) ? 'Update Logo' : 'Upload Logo'; ?>
                            </label>
                            <input type="file" name="system_logo" accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   onchange="previewLogo(this)">
                            
                            <!-- Logo Preview -->
                            <div id="logo-preview" class="hidden mt-3">
                                <img id="preview-logo" src="" alt="Logo preview" class="h-16 w-auto object-contain border border-gray-300 rounded-lg bg-white p-2">
                            </div>
                            
                            <p class="text-xs text-gray-500 mt-1">
                                Recommended: PNG or JPEG, max 2MB. Best size: 200x80px for receipts.
                            </p>
                        </div>
                        
                        <!-- Receipt Settings -->
                        <h4 class="text-md font-medium text-gray-900 border-b pb-2 mt-8">Receipt Settings</h4>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Header Text</label>
                            <input type="text" name="receipt_header" 
                                   value="<?php echo htmlspecialchars($systemSettings['receipt_header'] ?? 'Thank you for shopping with us!'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Thank you for shopping with us!">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Footer Text</label>
                            <input type="text" name="receipt_footer" 
                                   value="<?php echo htmlspecialchars($systemSettings['receipt_footer'] ?? 'Please come again!'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Please come again!">
                        </div>
                        
                        <!-- Sample Receipt Preview -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Receipt Preview</h5>
                            <div class="bg-white border rounded p-3 text-xs font-mono">
                                <div class="text-center">
                                    <?php if (!empty($systemSettings['system_logo'])): ?>
                                        <img src="uploads/system/<?php echo htmlspecialchars($systemSettings['system_logo']); ?>" 
                                             alt="Logo" class="h-8 w-auto mx-auto mb-2">
                                    <?php endif; ?>
                                    <div class="font-bold"><?php echo htmlspecialchars($systemSettings['system_name'] ?? 'POS System'); ?></div>
                                    <?php if (!empty($systemSettings['store_address'])): ?>
                                        <div class="text-xs"><?php echo nl2br(htmlspecialchars($systemSettings['store_address'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($systemSettings['store_phone'])): ?>
                                        <div class="text-xs">Phone: <?php echo htmlspecialchars($systemSettings['store_phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="border-t border-b py-2 my-2">
                                    <div class="flex justify-between"><span>Sample Item</span><span>MVR 25.00</span></div>
                                    <div class="flex justify-between font-bold"><span>Total:</span><span>MVR 25.00</span></div>
                                </div>
                                <div class="text-center text-xs">
                                    <div><?php echo htmlspecialchars($systemSettings['receipt_header'] ?? 'Thank you for shopping with us!'); ?></div>
                                    <div><?php echo htmlspecialchars($systemSettings['receipt_footer'] ?? 'Please come again!'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end pt-6 border-t">
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition duration-150">
                        <i class="fas fa-save mr-2"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function previewLogo(input) {
    const preview = document.getElementById('logo-preview');
    const previewImg = document.getElementById('preview-logo');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
