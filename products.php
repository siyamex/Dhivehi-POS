<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $sku = trim($_POST['sku']);
        $barcode = trim($_POST['barcode']);
        $categoryId = $_POST['category_id'] ?: null;
        $costPrice = floatval($_POST['cost_price']);
        $sellingPrice = floatval($_POST['selling_price']);
        $stockQuantity = intval($_POST['stock_quantity']);
        $minStockLevel = intval($_POST['min_stock_level']);        $taxRate = floatval($_POST['tax_rate']);
        $status = $_POST['status'];
        
        $errors = [];
        
        // Handle image upload
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/products/';
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            $fileName = $_FILES['image']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.';
            }
            
            // Validate file size
            if ($fileSize > $maxFileSize) {
                $errors[] = 'Image file size must be less than 5MB.';
            }
            
            if (empty($errors)) {
                // Generate unique filename
                $imageName = 'product_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $imageName;
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Move uploaded file
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $errors[] = 'Failed to upload image. Please try again.';
                    $imageName = null;
                }
            }
        }
        
        // Get existing image if editing
        $existingImage = null;
        if ($action === 'edit' && $productId) {
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $existingImage = $result['image'] ?? null;
        }
        
        if (empty($name)) $errors[] = 'Product name is required';
        if (empty($sku)) $errors[] = 'SKU is required';
        if ($costPrice < 0) $errors[] = 'Cost price must be positive';
        if ($sellingPrice < 0) $errors[] = 'Selling price must be positive';
        if ($stockQuantity < 0) $errors[] = 'Stock quantity must be positive';
        if ($minStockLevel < 0) $errors[] = 'Minimum stock level must be positive';
        
        // Check for duplicate SKU
        if ($action === 'add') {
            $stmt = $db->prepare("SELECT id FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetch()) {
                $errors[] = 'SKU already exists';
            }
        } else {
            $stmt = $db->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $stmt->execute([$sku, $productId]);
            if ($stmt->fetch()) {
                $errors[] = 'SKU already exists';
            }
        }
        
        // Check for duplicate barcode if provided
        if (!empty($barcode)) {
            if ($action === 'add') {
                $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ?");
                $stmt->execute([$barcode]);
                if ($stmt->fetch()) {
                    $errors[] = 'Barcode already exists';
                }
            } else {
                $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND id != ?");
                $stmt->execute([$barcode, $productId]);
                if ($stmt->fetch()) {
                    $errors[] = 'Barcode already exists';
                }
            }
        }
          if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO products (name, description, sku, barcode, category_id, cost_price, selling_price, stock_quantity, min_stock_level, tax_rate, status, image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $sku, $barcode ?: null, $categoryId, $costPrice, $sellingPrice, $stockQuantity, $minStockLevel, $taxRate, $status, $imageName]);
                
                $newProductId = $db->lastInsertId();
                
                // Record initial stock movement
                if ($stockQuantity > 0) {
                    $stmt = $db->prepare("
                        INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id, notes) 
                        VALUES (?, 'in', ?, 'adjustment', NULL, ?, 'Initial stock')
                    ");
                    $stmt->execute([$newProductId, $stockQuantity, $_SESSION['user_id']]);
                }
                
                showAlert('Product added successfully!', 'success');            } else {
                // Get current stock for comparison
                $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentStock = $currentProduct['stock_quantity'];
                
                // Handle image update - use new image if uploaded, otherwise keep existing
                $finalImageName = $imageName ?: $existingImage;
                
                // Delete old image if new one is uploaded
                if ($imageName && $existingImage && file_exists('uploads/products/' . $existingImage)) {
                    unlink('uploads/products/' . $existingImage);
                }
                
                $stmt = $db->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, sku = ?, barcode = ?, category_id = ?, cost_price = ?, selling_price = ?, stock_quantity = ?, min_stock_level = ?, tax_rate = ?, status = ?, image = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $sku, $barcode ?: null, $categoryId, $costPrice, $sellingPrice, $stockQuantity, $minStockLevel, $taxRate, $status, $finalImageName, $productId]);
                
                // Record stock adjustment if quantity changed
                if ($stockQuantity != $currentStock) {
                    $difference = $stockQuantity - $currentStock;
                    $movementType = $difference > 0 ? 'in' : 'out';
                    $quantity = abs($difference);
                    
                    $stmt = $db->prepare("
                        INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id, notes) 
                        VALUES (?, ?, ?, 'adjustment', NULL, ?, 'Stock adjustment')
                    ");
                    $stmt->execute([$productId, $movementType, $quantity, $_SESSION['user_id']]);
                }
                
                showAlert('Product updated successfully!', 'success');
            }            redirect('products.php');
        }
    }
}

// Handle product deletion
if ($action === 'delete' && $productId) {
    try {
        // Get product details including image
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            showAlert('Product not found!', 'error');
            redirect('products.php');
        }
        
        // Check if product has been sold (has sales records)
        $stmt = $db->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
        $stmt->execute([$productId]);
        $salesCount = $stmt->fetchColumn();
        
        if ($salesCount > 0) {
            // Don't delete, just deactivate
            $stmt = $db->prepare("UPDATE products SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$productId]);
            showAlert('Product deactivated successfully! (Cannot delete products with sales history)', 'success');
        } else {
            // Safe to delete completely
            $db->beginTransaction();
            
            // Delete associated stock movements
            $stmt = $db->prepare("DELETE FROM stock_movements WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Delete the product
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            
            // Delete associated image file
            if ($product['image'] && file_exists('uploads/products/' . $product['image'])) {
                unlink('uploads/products/' . $product['image']);
            }
            
            $db->commit();
            showAlert('Product deleted successfully!', 'success');
        }
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        showAlert('Error deleting product: ' . $e->getMessage(), 'error');
    }
    
    redirect('products.php');
}

// Get categories for dropdown
$stmt = $db->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($action === 'edit' && $productId) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        showAlert('Product not found!', 'error');
        redirect('products.php');
    }
}

if ($action === 'list') {
    // Get filters
    $search = $_GET['search'] ?? '';
    $categoryFilter = $_GET['category'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $stockFilter = $_GET['filter'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    
    // Build query
    $where = ['1=1'];
    $params = [];
    
    if ($search) {
        $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoryFilter) {
        $where[] = "p.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    if ($statusFilter) {
        $where[] = "p.status = ?";
        $params[] = $statusFilter;
    }
    
    if ($stockFilter === 'low_stock') {
        $where[] = "p.stock_quantity <= p.min_stock_level";
    }
    
    $whereClause = implode(' AND ', $where);
    
    $query = "
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $whereClause 
        ORDER BY p.name
    ";
    
    $result = paginate($query, $params, $page, 20);
    $products = $result['data'];
    $pagination = $result;
}

$pageTitle = $action === 'add' ? 'Add Product' : ($action === 'edit' ? 'Edit Product' : 'Products');
ob_start();
?>

<?php if ($action === 'list'): ?>
<!-- Products List -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <a href="products.php?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <input type="hidden" name="action" value="list">
            
            <div>
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div>
                <select name="filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Stock Levels</option>
                    <option value="low_stock" <?php echo $stockFilter === 'low_stock' ? 'selected' : ''; ?>>Low Stock Only</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-150">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-box text-4xl mb-4"></i>
                                <p>No products found</p>
                                <a href="products.php?action=add" class="text-primary hover:text-blue-700">Add your first product</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($product['image']): ?>
                                                <img class="h-10 w-10 rounded object-cover" src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded bg-gray-100 flex items-center justify-center">
                                                    <i class="fas fa-box text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['category_name'] ?: 'No Category'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo formatCurrency($product['selling_price']); ?></div>
                                    <div class="text-sm text-gray-500">Cost: <?php echo formatCurrency($product['cost_price']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $product['stock_quantity']; ?> units</div>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                                    <?php elseif ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="text-primary hover:text-blue-700 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="product_history.php?id=<?php echo $product['id']; ?>" class="text-gray-600 hover:text-gray-800 mr-3">
                                        <i class="fas fa-history"></i> History
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')" 
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i> Delete
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
<!-- Add/Edit Product Form -->
<div class="max-w-4xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="products.php" class="text-gray-600 hover:text-gray-800 mr-4">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h1>
    </div>    <div class="bg-white rounded-lg shadow">
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
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
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                        <input type="text" name="name" required 
                               value="<?php echo htmlspecialchars($product['name'] ?? $_POST['name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($product['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">No Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($product['category_id'] ?? $_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="active" <?php echo ($product['status'] ?? $_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($product['status'] ?? $_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <!-- Product Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                        <div class="space-y-2">
                            <!-- Current Image Display -->
                            <?php if ($action === 'edit' && !empty($product['image'])): ?>
                                <div class="flex items-center space-x-3">
                                    <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="Current product image" 
                                         class="h-16 w-16 object-cover rounded-lg border border-gray-300">
                                    <div class="text-sm text-gray-600">
                                        <p>Current image</p>
                                        <p class="text-xs"><?php echo htmlspecialchars($product['image']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- File Upload Input -->
                            <input type="file" 
                                   name="image" 
                                   accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   onchange="previewImage(this)">
                            
                            <!-- Image Preview -->
                            <div id="image-preview" class="hidden">
                                <img id="preview-img" src="" alt="Image preview" class="h-16 w-16 object-cover rounded-lg border border-gray-300">
                            </div>
                            
                            <p class="text-xs text-gray-500">
                                Accepted formats: JPEG, PNG, GIF, WebP. Maximum size: 5MB.
                                <?php echo $action === 'edit' ? 'Leave empty to keep current image.' : ''; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Product Details</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                        <input type="text" name="sku" required 
                               value="<?php echo htmlspecialchars($product['sku'] ?? $_POST['sku'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                        <input type="text" name="barcode" 
                               value="<?php echo htmlspecialchars($product['barcode'] ?? $_POST['barcode'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price *</label>
                            <input type="number" name="cost_price" step="0.01" min="0" required 
                                   value="<?php echo htmlspecialchars($product['cost_price'] ?? $_POST['cost_price'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price *</label>
                            <input type="number" name="selling_price" step="0.01" min="0" required 
                                   value="<?php echo htmlspecialchars($product['selling_price'] ?? $_POST['selling_price'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" min="0" required 
                                   value="<?php echo htmlspecialchars($product['stock_quantity'] ?? $_POST['stock_quantity'] ?? '0'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Stock Level *</label>
                            <input type="number" name="min_stock_level" min="0" required 
                                   value="<?php echo htmlspecialchars($product['min_stock_level'] ?? $_POST['min_stock_level'] ?? '5'); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" step="0.01" min="0" max="100" 
                               value="<?php echo htmlspecialchars($product['tax_rate'] ?? $_POST['tax_rate'] ?? '0'); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t">
                <a href="products.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition duration-150">
                    <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
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

// Confirm product deletion
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete the product "${name}"? This action cannot be undone.`)) {
        window.location.href = `products.php?action=delete&id=${id}`;
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
