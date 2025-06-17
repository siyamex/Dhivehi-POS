<?php
require_once 'includes/functions.php';
requireLogin();

$db = getDB();

// Get system settings
$systemSettings = getAllSystemSettings();

// Get all active products for the POS
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.name
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filtering
$stmt = $db->prepare("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customers for selection
$stmt = $db->prepare("SELECT * FROM customers ORDER BY first_name, last_name");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available discounts
$stmt = $db->prepare("
    SELECT * FROM discounts 
    WHERE status = 'active' 
    AND (start_date IS NULL OR start_date <= CURDATE()) 
    AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY name
");
$stmt->execute();
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = t('point_of_sale');
ob_start();
?>

<div class="flex flex-col lg:flex-row gap-6 h-screen <?php echo isRTL() ? 'lg:flex-row-reverse' : ''; ?>">
    <!-- Left Panel - Products -->
    <div class="lg:w-2/3 bg-white rounded-lg shadow overflow-hidden">
        <!-- Search and Filter Header -->
        <div class="bg-gray-50 p-4 border-b">
            <div class="flex flex-col sm:flex-row gap-4 <?php echo isRTL() ? 'sm:flex-row-reverse' : ''; ?>">
                <div class="flex-1">
                    <input type="text" 
                           id="product-search" 
                           placeholder="<?php echo t('search_products'); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent <?php echo isRTL() ? 'text-right' : ''; ?>">
                </div>
                <div class="sm:w-48">
                    <select id="category-filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent <?php echo isRTL() ? 'text-right' : ''; ?>">
                        <option value=""><?php echo t('all_categories'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="p-4 overflow-y-auto" style="height: calc(100vh - 200px);">
            <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer" 
                         data-product='<?php echo json_encode($product); ?>'
                         data-category="<?php echo $product['category_id']; ?>"
                         data-name="<?php echo strtolower($product['name']); ?>"
                         data-sku="<?php echo strtolower($product['sku']); ?>"
                         data-barcode="<?php echo strtolower($product['barcode'] ?? ''); ?>">
                        
                        <div class="text-center">
                            <?php if ($product['image']): ?>
                                <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-24 object-cover rounded mb-2">
                            <?php else: ?>
                                <div class="w-full h-24 bg-gray-100 rounded mb-2 flex items-center justify-center">
                                    <i class="fas fa-box text-gray-400 text-2xl"></i>
                                </div>
                            <?php endif; ?>
                              <h3 class="font-medium text-sm text-gray-900 mb-1 line-clamp-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-xs text-gray-500 mb-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('sku'); ?>: <?php echo htmlspecialchars($product['sku']); ?></p>
                            <p class="text-lg font-bold text-primary"><?php echo formatCurrency($product['selling_price']); ?></p>
                            
                            <?php if ($product['stock_quantity'] <= 0): ?>
                                <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full mt-1"><?php echo t('out_of_stock'); ?></span>
                            <?php elseif ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                <span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full mt-1"><?php echo t('low_stock'); ?> (<?php echo $product['stock_quantity']; ?>)</span>
                            <?php else: ?>
                                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full mt-1"><?php echo t('in_stock'); ?> (<?php echo $product['stock_quantity']; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel - Cart -->
    <div class="lg:w-1/3 bg-white rounded-lg shadow flex flex-col">        <!-- Cart Header -->
        <div class="bg-primary text-white p-4 rounded-t-lg <?php echo isRTL() ? 'text-right' : ''; ?>">
            <h2 class="text-lg font-semibold"><?php echo t('current_sale'); ?></h2>
        </div>

        <!-- Customer Selection -->
        <div class="p-4 border-b">
            <label class="block text-sm font-medium text-gray-700 mb-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('customer_optional'); ?></label>
            <select id="customer-select" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent <?php echo isRTL() ? 'text-right' : ''; ?>">
                <option value=""><?php echo t('walk_in_customer'); ?></option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>">
                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                        <?php if ($customer['phone']): ?>
                            - <?php echo htmlspecialchars($customer['phone']); ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto">
            <div id="cart-items" class="p-4">
                <div id="empty-cart" class="text-center text-gray-500 py-8">
                    <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                    <p><?php echo t('cart_empty'); ?></p>
                    <p class="text-sm"><?php echo t('click_products_add'); ?></p>
                </div>
            </div>
        </div>

        <!-- Cart Totals -->
        <div class="border-t p-4">            <!-- Discount Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('apply_discount'); ?></label>
                <select id="discount-select" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent <?php echo isRTL() ? 'text-right' : ''; ?>">
                    <option value=""><?php echo t('no_discount'); ?></option>
                    <?php foreach ($discounts as $discount): ?>
                        <option value="<?php echo $discount['id']; ?>" 
                                data-type="<?php echo $discount['type']; ?>"
                                data-value="<?php echo $discount['value']; ?>"
                                data-min="<?php echo $discount['min_amount']; ?>"
                                data-max="<?php echo $discount['max_discount'] ?? ''; ?>">
                            <?php echo htmlspecialchars($discount['name']); ?> 
                            (<?php echo $discount['type'] === 'percentage' ? $discount['value'] . '%' : formatCurrency($discount['value']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>            <!-- Totals -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between <?php echo isRTL() ? 'flex-row-reverse' : ''; ?>">
                    <span><?php echo t('subtotal'); ?>:</span>
                    <span id="subtotal">MVR 0.00</span>
                </div>
                <div class="flex justify-between <?php echo isRTL() ? 'flex-row-reverse' : ''; ?>">
                    <span><?php echo t('tax'); ?>:</span>
                    <span id="tax-amount">MVR 0.00</span>
                </div>
                <div class="flex justify-between <?php echo isRTL() ? 'flex-row-reverse' : ''; ?>" id="discount-row" style="display: none;">
                    <span><?php echo t('discount'); ?>:</span>
                    <span id="discount-amount" class="text-green-600">-MVR 0.00</span>
                </div>
                <div class="flex justify-between <?php echo isRTL() ? 'flex-row-reverse' : ''; ?> text-lg font-bold border-t pt-2">
                    <span><?php echo t('total'); ?>:</span>
                    <span id="total-amount">MVR 0.00</span>
                </div>
            </div>            <!-- Payment Method -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('payment_method'); ?></label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="payment-method active" data-method="cash">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="text-xs font-medium"><?php echo t('cash'); ?></span>
                    </button>
                    <button type="button" class="payment-method" data-method="card">
                        <i class="fas fa-credit-card"></i>
                        <span class="text-xs font-medium"><?php echo t('card'); ?></span>
                    </button>
                    <button type="button" class="payment-method" data-method="digital">
                        <i class="fas fa-mobile-alt"></i>
                        <span class="text-xs font-medium"><?php echo t('digital'); ?></span>
                    </button>
                    <button type="button" class="payment-method" data-method="credit">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="text-xs font-medium"><?php echo t('credit'); ?></span>
                    </button>
                </div>
                <div class="mt-2 text-center">
                    <span class="text-xs text-gray-500"><?php echo t('selected'); ?>: </span>
                    <span id="selected-payment-text" class="text-xs font-semibold text-blue-600"><?php echo t('cash'); ?></span>
                </div>
            </div>

            <!-- Credit Options (shown when credit is selected) -->
            <div id="credit-options" class="mt-4 hidden">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <h4 class="text-sm font-medium text-yellow-800 mb-2 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('credit_sale_options'); ?></h4>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('due_date_optional'); ?></label>
                            <input type="date" id="credit-due-date" class="w-full text-xs px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-primary <?php echo isRTL() ? 'text-right' : ''; ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 <?php echo isRTL() ? 'text-right' : ''; ?>"><?php echo t('notes'); ?></label>
                            <textarea id="credit-notes" rows="2" placeholder="<?php echo t('payment_terms_placeholder'); ?>" 
                                    class="w-full text-xs px-2 py-1 border border-gray-300 rounded focus:ring-1 focus:ring-primary resize-none <?php echo isRTL() ? 'text-right' : ''; ?>"></textarea>
                        </div>
                        <div class="text-xs text-yellow-700 <?php echo isRTL() ? 'text-right' : ''; ?>">
                            <i class="fas fa-info-circle <?php echo isRTL() ? 'ml-1' : 'mr-1'; ?>"></i>
                            <?php echo t('customer_required_credit'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 space-y-2">
                <button id="complete-sale" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-150" disabled>
                    <?php echo t('complete_sale'); ?>
                </button>
                <button id="clear-cart" class="w-full bg-gray-300 text-gray-700 py-2 rounded-lg font-semibold hover:bg-gray-400 transition duration-150">
                    <?php echo t('clear_cart'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receipt-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 <?php echo isRTL() ? 'flex-row-reverse' : ''; ?>">
                    <h3 class="text-lg font-semibold"><?php echo t('sale_complete'); ?></h3>
                    <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="receipt-content" class="border border-gray-200 p-4 bg-gray-50 text-sm font-mono <?php echo isRTL() ? 'text-right' : ''; ?>">
                    <!-- Receipt content will be generated here -->
                </div>
                <div class="mt-4 flex space-x-2 <?php echo isRTL() ? 'space-x-reverse' : ''; ?>">
                    <button onclick="printReceipt()" class="flex-1 bg-primary text-white py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-print <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('print'); ?>
                    </button>
                    <button onclick="closeReceiptModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400">
                        <?php echo t('close'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method {
    @apply flex flex-col items-center justify-center p-2 border-2 border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200 cursor-pointer;
    min-height: 50px;
}
.payment-method.active {
    @apply border-blue-500 bg-blue-100 text-blue-700 shadow-lg transform scale-105;
    border-width: 3px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.payment-method:hover {
    @apply border-gray-400 shadow-md transform scale-102;
}
.payment-method.active:hover {
    @apply border-blue-600 bg-blue-200;
}
.payment-method:active {
    @apply transform scale-95;
}
.payment-method i {
    font-size: 1rem;
    margin-bottom: 2px;
}
.payment-method.active i {
    color: #1d4ed8;
    font-weight: bold;
    transform: scale(1.1);
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
/* Custom scale classes for fine-tuned hover effects */
.scale-102 {
    transform: scale(1.02);
}
</style>

<script>
// System settings from PHP
const systemSettings = <?php echo json_encode($systemSettings); ?>;

let cart = [];
let selectedPaymentMethod = 'cash';

// DOM elements
const productsGrid = document.getElementById('products-grid');
const cartItems = document.getElementById('cart-items');
const emptyCart = document.getElementById('empty-cart');
const productSearch = document.getElementById('product-search');
const categoryFilter = document.getElementById('category-filter');
const customerSelect = document.getElementById('customer-select');
const discountSelect = document.getElementById('discount-select');

// Initialize POS
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateCartDisplay();
});

function setupEventListeners() {
    // Product selection
    productsGrid.addEventListener('click', function(e) {
        const productCard = e.target.closest('.product-card');
        if (productCard) {
            const product = JSON.parse(productCard.dataset.product);
            addToCart(product);
        }
    });

    // Search functionality
    productSearch.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedPaymentMethod = this.dataset.method;
            
            // Update selected payment text indicator
            const paymentText = this.querySelector('span').textContent;
            document.getElementById('selected-payment-text').textContent = paymentText;
            
            // Show/hide credit options
            const creditOptions = document.getElementById('credit-options');
            if (selectedPaymentMethod === 'credit') {
                creditOptions.classList.remove('hidden');
            } else {
                creditOptions.classList.add('hidden');
            }
        });
    });

    // Discount selection
    discountSelect.addEventListener('change', updateCartDisplay);

    // Action buttons
    document.getElementById('complete-sale').addEventListener('click', completeSale);
    document.getElementById('clear-cart').addEventListener('click', clearCart);

    // Barcode scanning (Enter key)
    productSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value.trim().toLowerCase();
            if (searchTerm) {
                const product = findProductByBarcode(searchTerm);
                if (product) {
                    addToCart(product);
                    this.value = '';
                }
            }
        }
    });
}

function addToCart(product) {
    if (product.stock_quantity <= 0) {
        alert(t('product_out_of_stock'));
        return;
    }

    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity >= product.stock_quantity) {
            alert(t('cannot_add_more'));
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    updateCartDisplay();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

function updateQuantity(productId, newQuantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else if (newQuantity <= item.stock_quantity) {
            item.quantity = newQuantity;
            updateCartDisplay();
        } else {
            alert(t('insufficient_stock'));
        }
    }
}

function updateCartDisplay() {
    if (cart.length === 0) {
        cartItems.innerHTML = `<div id="empty-cart" class="text-center text-gray-500 py-8"><i class="fas fa-shopping-cart text-4xl mb-4"></i><p>${t('cart_empty')}</p><p class="text-sm">${t('click_products_add')}</p></div>`;
        document.getElementById('complete-sale').disabled = true;
    } else {
        let html = '';
        cart.forEach(item => {
            const rtlClass = isRTL ? 'flex-row-reverse' : '';
            const spaceClass = isRTL ? 'space-x-reverse' : '';
            html += `
                <div class="flex items-center justify-between py-2 border-b ${rtlClass}">
                    <div class="flex-1">
                        <h4 class="font-medium text-sm">${item.name}</h4>
                        <p class="text-xs text-gray-500">${formatCurrency(item.selling_price)} ${t('each')}</p>
                    </div>
                    <div class="flex items-center space-x-2 ${spaceClass}">
                        <button onclick="updateQuantity(${item.id}, ${item.quantity - 1})" class="w-6 h-6 bg-gray-200 rounded text-xs">-</button>
                        <span class="w-8 text-center text-sm">${item.quantity}</span>
                        <button onclick="updateQuantity(${item.id}, ${item.quantity + 1})" class="w-6 h-6 bg-gray-200 rounded text-xs">+</button>
                        <button onclick="removeFromCart(${item.id})" class="w-6 h-6 bg-red-500 text-white rounded text-xs"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `;
        });
        cartItems.innerHTML = html;
        document.getElementById('complete-sale').disabled = false;
    }
    
    updateTotals();
}

function updateTotals() {
    let subtotal = 0;
    let taxAmount = 0;
    
    cart.forEach(item => {
        const itemTotal = item.selling_price * item.quantity;
        subtotal += itemTotal;
        taxAmount += (itemTotal * item.tax_rate / 100);
    });
    
    // Apply discount
    let discountAmount = 0;
    const discountOption = discountSelect.options[discountSelect.selectedIndex];
    
    if (discountOption.value) {
        const discountType = discountOption.dataset.type;
        const discountValue = parseFloat(discountOption.dataset.value);
        const minAmount = parseFloat(discountOption.dataset.min || 0);
        const maxDiscount = parseFloat(discountOption.dataset.max || 0);
        
        if (subtotal >= minAmount) {
            if (discountType === 'percentage') {
                discountAmount = (subtotal * discountValue) / 100;
                if (maxDiscount > 0 && discountAmount > maxDiscount) {
                    discountAmount = maxDiscount;
                }
            } else {
                discountAmount = discountValue;
            }
        }
    }
    
    const total = subtotal + taxAmount - discountAmount;
    
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('tax-amount').textContent = formatCurrency(taxAmount);
    document.getElementById('discount-amount').textContent = '-' + formatCurrency(discountAmount);
    document.getElementById('total-amount').textContent = formatCurrency(total);
    
    const discountRow = document.getElementById('discount-row');
    if (discountAmount > 0) {
        discountRow.style.display = 'flex';
    } else {
        discountRow.style.display = 'none';
    }
}

function filterProducts() {
    const searchTerm = productSearch.value.toLowerCase();
    const selectedCategory = categoryFilter.value;
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const name = card.dataset.name;
        const sku = card.dataset.sku;
        const barcode = card.dataset.barcode;
        const category = card.dataset.category;
        
        const matchesSearch = !searchTerm || 
            name.includes(searchTerm) || 
            sku.includes(searchTerm) || 
            barcode.includes(searchTerm);
            
        const matchesCategory = !selectedCategory || category === selectedCategory;
        
        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function findProductByBarcode(barcode) {
    const productCards = document.querySelectorAll('.product-card');
    for (let card of productCards) {
        const product = JSON.parse(card.dataset.product);
        if (product.barcode && product.barcode.toLowerCase() === barcode.toLowerCase()) {
            return product;
        }
        if (product.sku.toLowerCase() === barcode.toLowerCase()) {
            return product;
        }
    }
    return null;
}

function clearCart() {
    if (cart.length > 0 && confirm(t('clear_cart_confirm'))) {
        cart = [];
        updateCartDisplay();
    }
}

async function completeSale() {
    if (cart.length === 0) return;
    
    const customerId = customerSelect.value || null;
    const discountOption = discountSelect.options[discountSelect.selectedIndex];
      // Validate credit sale requirements
    if (selectedPaymentMethod === 'credit') {
        if (!customerId) {
            alert(t('select_customer_credit'));
            return;
        }
    }
    
    const saleData = {
        customer_id: customerId,
        payment_method: selectedPaymentMethod,
        items: cart,
        discount_id: discountOption.value || null
    };
    
    // Add credit-specific data if credit payment method
    if (selectedPaymentMethod === 'credit') {
        saleData.credit_due_date = document.getElementById('credit-due-date').value || null;
        saleData.credit_notes = document.getElementById('credit-notes').value || '';
    }
    
    try {
        const response = await fetch('process_sale.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(saleData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showReceipt(result.sale);
            cart = [];
            updateCartDisplay();
            customerSelect.value = '';
            discountSelect.value = '';            // Reset credit options
            document.getElementById('credit-due-date').value = '';
            document.getElementById('credit-notes').value = '';
            document.getElementById('credit-options').classList.add('hidden');            // Reset payment method to cash
            document.querySelectorAll('.payment-method').forEach(b => b.classList.remove('active'));
            document.querySelector('[data-method="cash"]').classList.add('active');
            selectedPaymentMethod = 'cash';
            document.getElementById('selected-payment-text').textContent = t('cash');
        } else {
            alert(t('error_completing_sale') + ': ' + result.message);
        }
    } catch (error) {
        alert(t('error_completing_sale') + ': ' + error.message);
    }
}

function showReceipt(sale) {
    const receiptContent = document.getElementById('receipt-content');
    const now = new Date();
    
    const systemName = systemSettings.system_name || 'POS System';
    const receiptHeader = systemSettings.receipt_header || 'Thank you for your purchase!';
    const receiptFooter = systemSettings.receipt_footer || 'Please come again!';
    
    let html = `
        <div class="text-center mb-4">`;
      // Add logo if available
    if (systemSettings.system_logo) {
        html += `<img src="uploads/system/${systemSettings.system_logo}" alt="Logo" style="max-height: 60px; max-width: 200px; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"><br>`;
    }
    
    html += `
            <h2 class="font-bold text-lg">${systemName}</h2>`;
    
    // Add store information
    if (systemSettings.store_address) {
        html += `<div class="text-xs">${systemSettings.store_address.replace(/\n/g, '<br>')}</div>`;
    }
    if (systemSettings.store_phone) {
        html += `<div class="text-xs">Phone: ${systemSettings.store_phone}</div>`;
    }
    if (systemSettings.store_email) {
        html += `<div class="text-xs">Email: ${systemSettings.store_email}</div>`;
    }
    
    html += `
            <p>Receipt #${sale.sale_number}</p>
            <p>${now.toLocaleDateString()} ${now.toLocaleTimeString()}</p>
        </div>
        
        <div class="border-t border-b py-2 mb-2">
    `;
    
    sale.items.forEach(item => {
        html += `
            <div class="flex justify-between">
                <span>${item.name}</span>
                <span>${formatCurrency(item.total_price)}</span>
            </div>
            <div class="text-xs text-gray-600 mb-1">
                ${item.quantity} x ${formatCurrency(item.unit_price)}
            </div>
        `;
    });
    
    html += `
        </div>
          <div class="space-y-1">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>${formatCurrency(sale.subtotal)}</span>
            </div>
            <div class="flex justify-between">
                <span>Tax:</span>
                <span>${formatCurrency(sale.tax_amount)}</span>
            </div>
    `;
    
    if (sale.discount_amount > 0) {
        html += `
            <div class="flex justify-between">
                <span>Discount:</span>
                <span>-${formatCurrency(sale.discount_amount)}</span>
            </div>
        `;
    }
      html += `
            <div class="flex justify-between font-bold border-t pt-1">
                <span>Total:</span>
                <span>${formatCurrency(sale.total_amount)}</span>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p>Payment Method: ${sale.payment_method.toUpperCase()}</p>
            <div class="text-xs mt-2">
                ${receiptHeader && receiptHeader !== 'Thank you for your purchase!' ? receiptHeader + '<br>' : ''}
                ${receiptFooter}
            </div>
        </div>
    `;
    
    receiptContent.innerHTML = html;
    document.getElementById('receipt-modal').classList.remove('hidden');
}

function closeReceiptModal() {
    document.getElementById('receipt-modal').classList.add('hidden');
}

function printReceipt() {
    const receiptContent = document.getElementById('receipt-content').innerHTML;
    const printWindow = window.open('', '', 'width=300,height=600');
    printWindow.document.write(`
        <html>
            <head>
                <title>Receipt</title>                <style>
                    body { font-family: monospace; font-size: 12px; margin: 10px; }
                    .text-center { text-align: center; }
                    .font-bold { font-weight: bold; }
                    .border-t { border-top: 1px solid #000; }
                    .border-b { border-bottom: 1px solid #000; }
                    .py-2 { padding: 5px 0; }
                    .mb-2 { margin-bottom: 10px; }
                    .mb-4 { margin-bottom: 20px; }
                    .mt-4 { margin-top: 20px; }
                    .space-y-1 > * + * { margin-top: 5px; }
                    .flex { display: flex; }
                    .justify-between { justify-content: space-between; }
                    .text-xs { font-size: 10px; }
                    .text-gray-600 { color: #666; }
                    img { display: block; margin: 0 auto; max-height: 60px; max-width: 200px; }
                </style>
            </head>
            <body>${receiptContent}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}

function formatCurrency(amount) {
    return 'MVR ' + parseFloat(amount).toFixed(2);
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
