<?php
require_once 'includes/functions.php';
requireLogin();

$pageTitle = t('language') . ' ' . t('test');
ob_start();
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="<?php echo isRTL() ? 'text-right' : 'text-left'; ?>">
        <h1 class="text-2xl font-bold mb-4"><?php echo t('language'); ?> <?php echo getCurrentLanguage() === 'dv' ? 'ޓެސްޓް' : 'Test'; ?></h1>
        
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-2"><?php echo getCurrentLanguage() === 'dv' ? 'މިހާރުގެ ބަސް' : 'Current Language'; ?>: 
                <span class="text-primary"><?php echo getCurrentLanguage() === 'dv' ? 'ދިވެހި' : 'English'; ?></span>
            </h2>
            <p class="text-gray-600"><?php echo getCurrentLanguage() === 'dv' ? 'ޑައިރެކްޝަން' : 'Direction'; ?>: 
                <span class="font-medium"><?php echo isRTL() ? 'RTL (Right-to-Left)' : 'LTR (Left-to-Right)'; ?></span>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- POS Interface Translations -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3"><?php echo getCurrentLanguage() === 'dv' ? 'ޕީއޯއެސް އިންޓަރފޭސް' : 'POS Interface'; ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><strong><?php echo t('point_of_sale'); ?></strong></li>
                    <li><?php echo t('current_sale'); ?></li>
                    <li><?php echo t('search_products'); ?></li>
                    <li><?php echo t('all_categories'); ?></li>
                    <li><?php echo t('customer_optional'); ?></li>
                    <li><?php echo t('payment_method'); ?></li>
                    <li><?php echo t('complete_sale'); ?></li>
                    <li><?php echo t('clear_cart'); ?></li>
                </ul>
            </div>

            <!-- Payment Methods -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3"><?php echo getCurrentLanguage() === 'dv' ? 'ފައިސާ ދެއްކުމުގެ ގޮތްތައް' : 'Payment Methods'; ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><?php echo t('cash'); ?></li>
                    <li><?php echo t('card'); ?></li>
                    <li><?php echo t('digital'); ?></li>
                    <li><?php echo t('credit'); ?></li>
                </ul>
            </div>

            <!-- Product Status -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3"><?php echo getCurrentLanguage() === 'dv' ? 'ތަކެތީގެ ހާލަތު' : 'Product Status'; ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full"><?php echo t('out_of_stock'); ?></span></li>
                    <li><span class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full"><?php echo t('low_stock'); ?></span></li>
                    <li><span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full"><?php echo t('in_stock'); ?></span></li>
                </ul>
            </div>

            <!-- Totals -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold mb-3"><?php echo getCurrentLanguage() === 'dv' ? 'ޖުމްލަ ހިސާބު' : 'Totals'; ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><?php echo t('subtotal'); ?>: MVR 100.00</li>
                    <li><?php echo t('tax'); ?>: MVR 6.00</li>
                    <li><?php echo t('discount'); ?>: -MVR 5.00</li>
                    <li class="font-bold border-t pt-2"><?php echo t('total'); ?>: MVR 101.00</li>
                </ul>
            </div>
        </div>

        <div class="mt-8">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">
                    <?php echo getCurrentLanguage() === 'dv' ? 'ބަސް ބަދަލުކުރުން' : 'Language Switching'; ?>
                </h4>
                <p class="text-yellow-700 text-sm mb-3">
                    <?php echo getCurrentLanguage() === 'dv' ? 
                        'ބަސް ބަދަލުކުރުމަށް މަތީގައިވާ ނެވިގޭޝަން ބޭރުގައި ބަސް ސެލެކްޓަރ ބޭނުންކުރައްވާ' : 
                        'Use the language selector in the navigation bar above to switch languages'; ?>
                </p>
                <div class="flex items-center space-x-2 <?php echo isRTL() ? 'space-x-reverse' : ''; ?>">
                    <span class="text-sm">🇺🇸 English</span>
                    <span class="text-gray-400">|</span>
                    <span class="text-sm">🇲🇻 ދިވެހި</span>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="pos.php" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-cash-register <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i>
                <?php echo getCurrentLanguage() === 'dv' ? 'ޕީއޯއެސް އަށް ދާން' : 'Go to POS'; ?>
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
