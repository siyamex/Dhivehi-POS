<?php
// Language system for POS - Supports English and Dhivehi

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'en';
}

// Function to set language
function setLanguage($lang) {
    $_SESSION['language'] = $lang;
}

// Function to get current language
function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'en';
}

// Function to check if current language is RTL
function isRTL() {
    return getCurrentLanguage() === 'dv';
}

// Translation arrays
$translations = [
    'en' => [
        // Navigation
        'dashboard' => 'Dashboard',
        'pos' => 'POS',
        'products' => 'Products',
        'customers' => 'Customers',
        'sales' => 'Sales',
        'credit' => 'Credit',
        'reports' => 'Reports',
        'admin' => 'Admin',
        'logout' => 'Logout',
        'system_settings' => 'System Settings',
        
        // POS Interface
        'point_of_sale' => 'Point of Sale',
        'current_sale' => 'Current Sale',
        'search_products' => 'Search products by name, SKU, or barcode...',
        'all_categories' => 'All Categories',
        'customer_optional' => 'Customer (Optional)',
        'walk_in_customer' => 'Walk-in Customer',
        'cart_empty' => 'Cart is empty',
        'click_products_add' => 'Click on products to add them',
        'apply_discount' => 'Apply Discount',
        'no_discount' => 'No Discount',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'discount' => 'Discount',
        'total' => 'Total',
        'payment_method' => 'Payment Method',
        'selected' => 'Selected',
        'cash' => 'Cash',
        'card' => 'Card',
        'digital' => 'Digital',
        'credit' => 'Credit',
        'complete_sale' => 'Complete Sale',
        'clear_cart' => 'Clear Cart',
        
        // Credit Options
        'credit_sale_options' => 'Credit Sale Options',
        'due_date_optional' => 'Due Date (Optional)',
        'notes' => 'Notes',
        'payment_terms_placeholder' => 'Payment terms, customer agreement, etc.',
        'customer_required_credit' => 'Customer must be selected for credit sales',
        
        // Product Grid
        'sku' => 'SKU',
        'out_of_stock' => 'Out of Stock',
        'low_stock' => 'Low Stock',
        'in_stock' => 'In Stock',
        
        // Receipt
        'sale_complete' => 'Sale Complete!',
        'receipt_number' => 'Receipt #',
        'print' => 'Print',
        'close' => 'Close',
        'each' => 'each',
        
        // Messages
        'product_out_of_stock' => 'This product is out of stock!',
        'cannot_add_more' => 'Cannot add more items. Insufficient stock!',
        'insufficient_stock' => 'Insufficient stock!',
        'clear_cart_confirm' => 'Are you sure you want to clear the cart?',
        'select_customer_credit' => 'Please select a customer for credit sales',
        'error_completing_sale' => 'Error completing sale',
          // System Settings
        'language' => 'Language',
        'english' => 'English',
        'dhivehi' => 'Dhivehi',
        'save_settings' => 'Save Settings',
        'settings_saved' => 'Settings saved successfully!',
        
        // Forms and Buttons
        'add' => 'Add',
        'edit' => 'Edit',
        'update' => 'Update',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'submit' => 'Submit',
        'search' => 'Search',
        'filter' => 'Filter',
        'clear' => 'Clear',
        'reset' => 'Reset',
        'view' => 'View',
        'back' => 'Back',
        'next' => 'Next',
        'previous' => 'Previous',
        'first' => 'First',
        'last' => 'Last',
        'actions' => 'Actions',
        'select' => 'Select',
        'choose' => 'Choose',
        'upload' => 'Upload',
        'download' => 'Download',
        'export' => 'Export',
        'import' => 'Import',
        
        // Product Management
        'add_product' => 'Add Product',
        'edit_product' => 'Edit Product',
        'update_product' => 'Update Product',
        'add_new_product' => 'Add New Product',
        'product_name' => 'Product Name',
        'product_description' => 'Description',
        'product_category' => 'Category',
        'cost_price' => 'Cost Price',
        'selling_price' => 'Selling Price',
        'stock_quantity' => 'Stock Quantity',
        'min_stock_level' => 'Minimum Stock Level',
        'barcode' => 'Barcode',
        'tax_rate' => 'Tax Rate',
        'product_image' => 'Product Image',
        'basic_information' => 'Basic Information',
        'pricing_inventory' => 'Pricing & Inventory',
        'product_added' => 'Product added successfully!',
        'product_updated' => 'Product updated successfully!',
        'product_deleted' => 'Product deleted successfully!',
        
        // Customer Management
        'add_customer' => 'Add Customer',
        'edit_customer' => 'Edit Customer',
        'update_customer' => 'Update Customer',
        'add_new_customer' => 'Add New Customer',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'city' => 'City',
        'postal_code' => 'Postal Code',
        'customer_added' => 'Customer added successfully!',
        'customer_updated' => 'Customer updated successfully!',
        'customer_deleted' => 'Customer deleted successfully!',
        
        // User Management
        'add_user' => 'Add User',
        'edit_user' => 'Edit User',
        'update_user' => 'Update User',
        'add_new_user' => 'Add New User',
        'username' => 'Username',
        'password' => 'Password',
        'role' => 'Role',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'cashier' => 'Cashier',
        'manager' => 'Manager',
        'user_added' => 'User added successfully!',
        'user_updated' => 'User updated successfully!',
        'user_deleted' => 'User deleted successfully!',
        
        // Categories
        'add_category' => 'Add Category',
        'edit_category' => 'Edit Category',
        'update_category' => 'Update Category',
        'add_new_category' => 'Add New Category',
        'category_name' => 'Category Name',
        'category_description' => 'Category Description',
        'category_added' => 'Category added successfully!',
        'category_updated' => 'Category updated successfully!',
        'category_deleted' => 'Category deleted successfully!',
        
        // Sales and Payments
        'sale_number' => 'Sale #',
        'sale_date' => 'Sale Date',
        'sale_time' => 'Sale Time',
        'original_amount' => 'Original Amount',
        'paid_amount' => 'Paid Amount',
        'remaining_amount' => 'Remaining Amount',
        'remaining_balance' => 'Remaining Balance',
        'payment_amount' => 'Payment Amount',
        'payment_date' => 'Payment Date',
        'payment_notes' => 'Payment Notes',
        'record_payment' => 'Record Payment',
        'payment_history' => 'Payment History',
        'payment_recorded' => 'Payment recorded successfully!',
        'all_status' => 'All Status',
        'pending' => 'Pending',
        'partial' => 'Partial',
        'partial_payment' => 'Partial Payment',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'due_date' => 'Due Date',
        'no_due_date' => 'No due date',
        'pay' => 'Pay',
        'view_sale' => 'View Sale',
        
        // Receipt and Print
        'print_receipt' => 'Print Receipt',
        'receipt_preview' => 'Receipt Preview',
        'receipt_header' => 'Receipt Header',
        'receipt_footer' => 'Receipt Footer',
        'store_information' => 'Store Information',
        'store_address' => 'Store Address',
        'store_phone' => 'Phone Number',
        'store_email' => 'Email Address',
        
        // System Configuration
        'system_name' => 'System Name',
        'system_logo' => 'System Logo',
        'current_logo' => 'Current Logo',
        'update_logo' => 'Update Logo',
        'upload_logo' => 'Upload Logo',
        'currency_symbol' => 'Currency Symbol',
        'default_language' => 'Default Language',
        'receipt_settings' => 'Receipt Settings',
        'logo_branding' => 'Logo & Branding',
        
        // General Terms
        'date' => 'Date',
        'time' => 'Time',
        'amount' => 'Amount',
        'quantity' => 'Quantity',
        'price' => 'Price',
        'name' => 'Name',
        'description' => 'Description',
        'welcome' => 'Welcome',
        'sign_in' => 'Sign in',
        'sign_out' => 'Sign out',
        'login' => 'Login',
        'demo_credentials' => 'Demo Credentials',
        'invalid_credentials' => 'Invalid username or password',
        
        // Status Messages
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Information',
        'loading' => 'Loading',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        
        // Common Phrases
        'are_you_sure' => 'Are you sure?',
        'confirm_delete' => 'Are you sure you want to delete this item?',
        'required_field' => 'This field is required',
        'optional' => 'Optional',
        'select_option' => 'Select an option',
        'no_data_found' => 'No data found',
        'no_records' => 'No records found',
        'total_records' => 'Total Records',
        'showing_results' => 'Showing results',
        'page' => 'Page',
        'of' => 'of',
        'go_to_page' => 'Go to page',
        
        // Placeholders
        'search_placeholder' => 'Search...',
        'select_customer' => 'Select Customer',
        'select_payment_method' => 'Select Payment Method',
        'enter_amount' => 'Enter amount',
        'enter_notes' => 'Enter notes',
        'optional_notes' => 'Optional notes',
    ],
    
    'dv' => [
        // Navigation (Dhivehi)
        'dashboard' => 'ޑެޝްބޯޑް',
        'pos' => 'ޕީއޯއެސް',
        'products' => 'ތަކެތި',
        'customers' => 'ކަސްޓަމަރުން',
        'sales' => 'ވިއްކުން',
        'credit' => 'ކްރެޑިޓް',
        'reports' => 'ރިޕޯޓްތައް',
        'admin' => 'އެޑްމިން',
        'logout' => 'ލޮގް އައުޓް',
        'system_settings' => 'ސިސްޓަމް ސެޓިންގްސް',
        
        // POS Interface (Dhivehi)
        'point_of_sale' => 'ވިޔަފާރީ ނިންމުން',
        'current_sale' => 'މިހާރުގެ ވިއްކުން',
        'search_products' => 'ތަކެތި ހޯދުން - ނަން، އެސްކޭޔޫ، ނުވަތަ ބާކޯޑް...',
        'all_categories' => 'ހުރިހާ ކެޓަގަރީތައް',
        'customer_optional' => 'ކަސްޓަމަރ (އިޚްތިޔާރީ)',
        'walk_in_customer' => 'ވޯކް އިން ކަސްޓަމަރ',
        'cart_empty' => 'ކާޓް ހާލީ',
        'click_products_add' => 'ތަކެތި އިތުރުކުރުމަށް ކްލިކްކުރައްވާ',
        'apply_discount' => 'ޑިސްކައުންޓް ލާދެއްވުން',
        'no_discount' => 'ޑިސްކައުންޓް ނެތް',
        'subtotal' => 'ސަބްޓޯޓަލް',
        'tax' => 'ޓެކްސް',
        'discount' => 'ޑިސްކައުންޓް',
        'total' => 'ޖުމްލަ',
        'payment_method' => 'ފައިސާ ދެއްކުމުގެ ގޮތް',
        'selected' => 'އިޚްތިޔާރުކުރެވިފައި',
        'cash' => 'ކެޝް',
        'card' => 'ކާޑް',
        'digital' => 'ޑިޖިޓަލް',
        'credit' => 'ކްރެޑިޓް',
        'complete_sale' => 'ވިއްކުން ނިންމުން',
        'clear_cart' => 'ކާޓް ސާފުކުރުން',
        
        // Credit Options (Dhivehi)
        'credit_sale_options' => 'ކްރެޑިޓް ވިއްކުމުގެ އިޚްތިޔާރުތައް',
        'due_date_optional' => 'ދޭން ޖެހޭ ތާރީޚް (އިޚްތިޔާރީ)',
        'notes' => 'ނޯޓްތައް',
        'payment_terms_placeholder' => 'ފައިސާ ދެއްކުމުގެ ޝަރުތުތައް، ކަސްޓަމަރ އެއްބަސްވުން، ވޮ.',
        'customer_required_credit' => 'ކްރެޑިޓް ވިއްކުމަށް ކަސްޓަމަރ އިޚްތިޔާރުކުރަން ޖެހޭ',
        
        // Product Grid (Dhivehi)
        'sku' => 'އެސްކޭޔޫ',
        'out_of_stock' => 'ސްޓޮކް ނެތް',
        'low_stock' => 'ސްޓޮކް ކުޑަ',
        'in_stock' => 'ސްޓޮކް އެބަ',
        
        // Receipt (Dhivehi)
        'sale_complete' => 'ވިއްކުން ނިމުނީ!',
        'receipt_number' => 'ރަސީދު ނަންބަރު',
        'print' => 'ޕްރިންޓް',
        'close' => 'ބަނދުކުރުން',
        'each' => 'އެކެއް',
        
        // Messages (Dhivehi)
        'product_out_of_stock' => 'މި ތަކެތި ސްޓޮކް ނެތް!',
        'cannot_add_more' => 'ތިޔަގޮތަށް އިތުރު ނުކުރެވޭ. ސްޓޮކް ނުލިބޭ!',
        'insufficient_stock' => 'ސްޓޮކް ނުލިބޭ!',
        'clear_cart_confirm' => 'ޔަގީންކޮށް ކާޓް ސާފުކުރަން?',
        'select_customer_credit' => 'ކްރެޑިޓް ވިއްކުމަށް ކަސްޓަމަރ އިޚްތިޔާރުކުރައްވާ',
        'error_completing_sale' => 'ވިއްކުން ނިންމުމުގައި ގޮޅި',
          // System Settings (Dhivehi)
        'language' => 'ބަސް',
        'english' => 'އިނގިރޭސި',
        'dhivehi' => 'ދިވެހި',
        'save_settings' => 'ސެޓިންގްސް ސޭވްކުރުން',
        'settings_saved' => 'ސެޓިންގްސް ކާމިޔާބުން ސޭވްކުރެވުނީ!',
        
        // Forms and Buttons (Dhivehi)
        'add' => 'އިތުރުކުރުން',
        'edit' => 'އިސްލާހުކުރުން',
        'update' => 'އަޕްޑޭޓްކުރުން',
        'delete' => 'ފުހެލުން',
        'cancel' => 'ކެންސަލްކުރުން',
        'save' => 'ސޭވްކުރުން',
        'submit' => 'ހުށަހެޅުން',
        'search' => 'ހޯދުން',
        'filter' => 'ފިލްޓަރ',
        'clear' => 'ސާފުކުރުން',
        'reset' => 'ރީސެޓް',
        'view' => 'ބަލައިދިނުން',
        'back' => 'ފަހަތަށް',
        'next' => 'ކުރިއަށް',
        'previous' => 'ކުރީގެ',
        'first' => 'ފުރަތަމަ',
        'last' => 'ފަހު',
        'actions' => 'ހަރަކާތްތައް',
        'select' => 'އިޚްތިޔާރުކުރުން',
        'choose' => 'ހޮވުން',
        'upload' => 'އަޕްލޯޑް',
        'download' => 'ޑައުންލޯޑް',
        'export' => 'އެކްސްޕޯޓް',
        'import' => 'އިމްޕޯޓް',
        
        // Product Management (Dhivehi)
        'add_product' => 'ތަކެތި އިތުރުކުރުން',
        'edit_product' => 'ތަކެތި އިސްލާހުކުރުން',
        'update_product' => 'ތަކެތި އަޕްޑޭޓްކުރުން',
        'add_new_product' => 'އައު ތަކެތި އިތުރުކުރުން',
        'product_name' => 'ތަކެތީގެ ނަން',
        'product_description' => 'ތަފްސީލު',
        'product_category' => 'ކެޓަގަރީ',
        'cost_price' => 'ކޮސްޓް ޕްރައިސް',
        'selling_price' => 'ވިއްކާ ޕްރައިސް',
        'stock_quantity' => 'ސްޓޮކް ޢަދަދު',
        'min_stock_level' => 'އިން ކުޑަ ސްޓޮކް ލެވެލް',
        'barcode' => 'ބާކޯޑް',
        'tax_rate' => 'ޓެކްސް ރޭޓް',
        'product_image' => 'ތަކެތީގެ ތަސްވީރު',
        'basic_information' => 'ބުނިޔާދީ މަޢުލޫމާތު',
        'pricing_inventory' => 'ޕްރައިސް އަދި އިންވެންޓަރީ',
        'product_added' => 'ތަކެތި ކާމިޔާބުން އިތުރުކުރެވުނީ!',
        'product_updated' => 'ތަކެތި ކާމިޔާބުން އަޕްޑޭޓްކުރެވުނީ!',
        'product_deleted' => 'ތަކެތި ކާމިޔާބުން ފުހެލެވުނީ!',
        
        // Customer Management (Dhivehi)
        'add_customer' => 'ކަސްޓަމަރ އިތުރުކުރުން',
        'edit_customer' => 'ކަސްޓަމަރ އިސްލާހުކުރުން',
        'update_customer' => 'ކަސްޓަމަރ އަޕްޑޭޓްކުރުން',
        'add_new_customer' => 'އައު ކަސްޓަމަރ އިތުރުކުރުން',
        'first_name' => 'ފުރަތަމަ ނަން',
        'last_name' => 'ފަހު ނަން',
        'email' => 'އީމެއިލް',
        'phone' => 'ފޯނު',
        'address' => 'އެޑްރެސް',
        'city' => 'ރަށް/ސަރަހައްދު',
        'postal_code' => 'ޕޯސްޓަލް ކޯޑް',
        'customer_added' => 'ކަސްޓަމަރ ކާމިޔާބުން އިތުރުކުރެވުނީ!',
        'customer_updated' => 'ކަސްޓަމަރ ކާމިޔާބުން އަޕްޑޭޓްކުރެވުނީ!',
        'customer_deleted' => 'ކަސްޓަމަރ ކާމިޔާބުން ފުހެލެވުނީ!',
        
        // User Management (Dhivehi)
        'add_user' => 'ޔޫޒަރ އިތުރުކުރުން',
        'edit_user' => 'ޔޫޒަރ އިސްލާހުކުރުން',
        'update_user' => 'ޔޫޒަރ އަޕްޑޭޓްކުރުން',
        'add_new_user' => 'އައު ޔޫޒަރ އިތުރުކުރުން',
        'username' => 'ޔޫޒަރނޭމް',
        'password' => 'ޕާސްވޯޑް',
        'role' => 'ރޯލް',
        'status' => 'ހާލަތު',
        'active' => 'ކަމުގައި',
        'inactive' => 'ނުކަމުގައި',
        'cashier' => 'ކެޝިއަރ',
        'manager' => 'މެނޭޖަރ',
        'user_added' => 'ޔޫޒަރ ކާމިޔާބުން އިތުރުކުރެވުނީ!',
        'user_updated' => 'ޔޫޒަރ ކާމިޔާބުން އަޕްޑޭޓްކުރެވުނީ!',
        'user_deleted' => 'ޔޫޒަރ ކާމިޔާބުން ފުހެލެވުނީ!',
        
        // Categories (Dhivehi)
        'add_category' => 'ކެޓަގަރީ އިތުރުކުރުން',
        'edit_category' => 'ކެޓަގަރީ އިސްލާހުކުރުން',
        'update_category' => 'ކެޓަގަރީ އަޕްޑޭޓްކުރުން',
        'add_new_category' => 'އައު ކެޓަގަރީ އިތުރުކުރުން',
        'category_name' => 'ކެޓަގަރީގެ ނަން',
        'category_description' => 'ކެޓަގަރީގެ ތަފްސީލު',
        'category_added' => 'ކެޓަގަރީ ކާމިޔާބުން އިތުރުކުރެވުނީ!',
        'category_updated' => 'ކެޓަގަރީ ކާމިޔާބުން އަޕްޑޭޓްކުރެވުނީ!',
        'category_deleted' => 'ކެޓަގަރީ ކާމިޔާބުން ފުހެލެވުނީ!',
        
        // Sales and Payments (Dhivehi)
        'sale_number' => 'ވިއްކުމުގެ ނަންބަރު',
        'sale_date' => 'ވިއްކި ތާރީޚް',
        'sale_time' => 'ވިއްކި ވަގުތު',
        'original_amount' => 'އަސްލު ޢަދަދު',
        'paid_amount' => 'ދެއްކި ޢަދަދު',
        'remaining_amount' => 'ބާކީ ޢަދަދު',
        'remaining_balance' => 'ބާކީ ބެލެންސް',
        'payment_amount' => 'ދެއްކުމުގެ ޢަދަދު',
        'payment_date' => 'ދެއްކި ތާރީޚް',
        'payment_notes' => 'ދެއްކުމުގެ ނޯޓްތައް',
        'record_payment' => 'ދެއްކުން ރެކޯޑްކުރުން',
        'payment_history' => 'ދެއްކުމުގެ ތާރީޚް',
        'payment_recorded' => 'ދެއްކުން ކާމިޔާބުން ރެކޯޑްކުރެވުނީ!',
        'all_status' => 'ހުރިހާ ހާލަތް',
        'pending' => 'ބާކީ',
        'partial' => 'ބައިކޮޅެއް',
        'partial_payment' => 'ބައިކޮޅެއް ދެއްކުން',
        'paid' => 'ކުރިން ދެއްކިފައި',
        'overdue' => 'ވަގުތު ހަމަވެފައި',
        'due_date' => 'ދޭން ޖެހޭ ތާރީޚް',
        'no_due_date' => 'ދޭން ޖެހޭ ތާރީޚް ނެތް',
        'pay' => 'ދެއްކުން',
        'view_sale' => 'ވިއްކުން ބަލައިދިނުން',
        
        // Receipt and Print (Dhivehi)
        'print_receipt' => 'ރަސީދު ޕްރިންޓްކުރުން',
        'receipt_preview' => 'ރަސީދުގެ ޕްރިވިއު',
        'receipt_header' => 'ރަސީދުގެ ހެޑަރ',
        'receipt_footer' => 'ރަސީދުގެ ފޫޓަރ',
        'store_information' => 'ފަންނުގެ މަޢުލޫމާތު',
        'store_address' => 'ފަންނުގެ އެޑްރެސް',
        'store_phone' => 'ފޯނު ނަންބަރު',
        'store_email' => 'އީމެއިލް އެޑްރެސް',
        
        // System Configuration (Dhivehi)
        'system_name' => 'ސިސްޓަމްގެ ނަން',
        'system_logo' => 'ސިސްޓަމްގެ ލޯގޯ',
        'current_logo' => 'މިހާރުގެ ލޯގޯ',
        'update_logo' => 'ލޯގޯ އަޕްޑޭޓްކުރުން',
        'upload_logo' => 'ލޯގޯ އަޕްލޯޑްކުރުން',
        'currency_symbol' => 'ކަރަންސީ ސިމްބޯލް',
        'default_language' => 'ޑިފޯލްޓް ބަސް',
        'receipt_settings' => 'ރަސީދު ސެޓިންގްސް',
        'logo_branding' => 'ލޯގޯ އަދި ބްރެންޑިން',
        
        // General Terms (Dhivehi)
        'date' => 'ތާރީޚް',
        'time' => 'ވަގުތު',
        'amount' => 'ޢަދަދު',
        'quantity' => 'ޢަދަދު',
        'price' => 'ޕްރައިސް',
        'name' => 'ނަން',
        'description' => 'ތަފްސީލު',
        'welcome' => 'މަރުހަބާ',
        'sign_in' => 'ސައިން އިން',
        'sign_out' => 'ސައިން އައުޓް',
        'login' => 'ލޮގިން',
        'demo_credentials' => 'ޑެމޯ ކްރެޑެންޝަލްސް',
        'invalid_credentials' => 'ޔޫޒަރނޭމް ނުވަތަ ޕާސްވޯޑް ރަނގަޅެއް ނޫން',
        
        // Status Messages (Dhivehi)
        'success' => 'ކާމިޔާބު',
        'error' => 'ގޮޅި',
        'warning' => 'އާންމު',
        'info' => 'މަޢުލޫމާތު',
        'loading' => 'ލޯޑުވަނީ',
        'processing' => 'ޕްރޮސެސްވަނީ',
        'completed' => 'ނިމުނީ',
        'failed' => 'ފެއިލްވެއްޖެ',
        
        // Common Phrases (Dhivehi)
        'are_you_sure' => 'ޔަގީންކޮށް؟',
        'confirm_delete' => 'ޔަގީންކޮށް މި ތަކެއް ފުހެލަން؟',
        'required_field' => 'މި ފީލްޑް ފުރިހަމަކުރަން ޖެހޭ',
        'optional' => 'އިޚްތިޔާރީ',
        'select_option' => 'އޮޕްޝަން އިޚްތިޔާރުކުރައްވާ',
        'no_data_found' => 'ޑޭޓާ ނުފެނުނު',
        'no_records' => 'ރެކޯޑްތައް ނުފެނުނު',
        'total_records' => 'ޖުމްލަ ރެކޯޑްތައް',
        'showing_results' => 'ނަތީޖާތައް ދައްކަވަނީ',
        'page' => 'ޞަފްޙާ',
        'of' => 'ގެ',
        'go_to_page' => 'ޞަފްޙާއަށް ދާ',
        
        // Placeholders (Dhivehi)
        'search_placeholder' => 'ހޯދުން...',
        'select_customer' => 'ކަސްޓަމަރ އިޚްތިޔާރުކުރުން',
        'select_payment_method' => 'ފައިސާ ދެއްކުމުގެ ގޮތް އިޚްތިޔާރުކުރުން',
        'enter_amount' => 'ޢަދަދު ލިޔުއްވާ',
        'enter_notes' => 'ނޯޓްތައް ލިޔުއްވާ',
        'optional_notes' => 'އިޚްތިޔާރީ ނޯޓްތައް',
    ]
];

// Function to get translated text
function t($key, $default = null) {
    global $translations;
    
    $language = getCurrentLanguage();
    
    if (isset($translations[$language][$key])) {
        return $translations[$language][$key];
    }
    
    // Fallback to English if translation not found
    if ($language !== 'en' && isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }
    
    // Return default or key if no translation found
    return $default ?? $key;
}

// Function to get all translations for JavaScript
function getJSTranslations() {
    global $translations;
    $language = getCurrentLanguage();
    return json_encode($translations[$language] ?? $translations['en']);
}
?>
