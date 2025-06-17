<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>" <?php echo isRTL() ? 'dir="rtl"' : 'dir="ltr"'; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getSystemSetting('system_name', 'POS System'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if (isRTL()): ?>
    <!-- Dhivehi/Thaana Font -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thaana:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#64748B',
                    },
                    fontFamily: {
                        'thaana': ['Noto Sans Thaana', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        <?php if (isRTL()): ?>
        body {
            font-family: 'Noto Sans Thaana', sans-serif;
            direction: rtl;
        }
        .rtl-flip {
            transform: scaleX(-1);
        }
        /* RTL adjustments */
        .text-left { text-align: right !important; }
        .text-right { text-align: left !important; }
        .mr-2 { margin-left: 0.5rem !important; margin-right: 0 !important; }
        .mr-3 { margin-left: 0.75rem !important; margin-right: 0 !important; }
        .ml-2 { margin-right: 0.5rem !important; margin-left: 0 !important; }
        .ml-3 { margin-right: 0.75rem !important; margin-left: 0 !important; }
        .ml-6 { margin-right: 1.5rem !important; margin-left: 0 !important; }
        .pr-4 { padding-left: 1rem !important; padding-right: 0 !important; }
        .pl-4 { padding-right: 1rem !important; padding-left: 0 !important; }
        .border-r { border-left: 1px solid !important; border-right: none !important; }
        .border-l { border-right: 1px solid !important; border-left: none !important; }
        .rounded-l { border-top-right-radius: 0.375rem !important; border-bottom-right-radius: 0.375rem !important; border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; }
        .rounded-r { border-top-left-radius: 0.375rem !important; border-bottom-left-radius: 0.375rem !important; border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important; }
        <?php endif; ?>
    </style>
    <script>
        // Language support
        const currentLanguage = '<?php echo getCurrentLanguage(); ?>';
        const isRTL = <?php echo isRTL() ? 'true' : 'false'; ?>;
        const translations = <?php echo getJSTranslations(); ?>;
        
        function t(key, defaultText = null) {
            return translations[key] || defaultText || key;
        }
    </script>
</head>
<body class="bg-gray-50 <?php echo isRTL() ? 'font-thaana' : ''; ?>">
    <?php if (isLoggedIn()): ?>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <?php 
                        $systemName = getSystemSetting('system_name', 'POS System');
                        $systemLogo = getSystemSetting('system_logo', '');
                        ?>
                        <?php if (!empty($systemLogo) && file_exists('uploads/system/' . $systemLogo)): ?>
                            <img src="uploads/system/<?php echo htmlspecialchars($systemLogo); ?>" 
                                 alt="Logo" class="h-8 w-auto mr-3">
                        <?php endif; ?>
                        <h1 class="text-xl font-bold text-primary"><?php echo htmlspecialchars($systemName); ?></h1>
                    </div>                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8 <?php echo isRTL() ? 'sm:mr-6 sm:space-x-reverse' : ''; ?>">
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-tachometer-alt <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('dashboard'); ?>
                        </a>
                        <a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'pos.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-cash-register <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('pos'); ?>
                        </a>
                        <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-box <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('products'); ?>
                        </a>
                        <a href="customers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-users <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('customers'); ?>
                        </a>
                        <a href="sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-line <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('sales'); ?>
                        </a>
                        <a href="credit.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'credit.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-calendar-alt <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('credit'); ?>
                        </a>                        <?php if (hasRole('admin') || hasRole('manager')): ?>
                        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-chart-bar <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('reports'); ?>
                        </a>
                        <?php endif; ?>                        <?php if (hasRole('admin')): ?>
                        <a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-cog <?php echo isRTL() ? 'ml-2' : 'mr-2'; ?>"></i><?php echo t('admin'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>                <div class="hidden sm:ml-6 sm:flex sm:items-center <?php echo isRTL() ? 'sm:mr-6' : ''; ?>">
                    <!-- Language Switcher -->
                    <div class="<?php echo isRTL() ? 'ml-4' : 'mr-4'; ?>">
                        <div class="relative">
                            <select id="language-select" onchange="changeLanguage(this.value)" 
                                    class="appearance-none bg-white border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="en" <?php echo getCurrentLanguage() === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                                <option value="dv" <?php echo getCurrentLanguage() === 'dv' ? 'selected' : ''; ?>>🇲🇻 ދިވެހި</option>
                            </select>
                            <i class="fas fa-chevron-down absolute <?php echo isRTL() ? 'left-2' : 'right-2'; ?> top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="<?php echo isRTL() ? 'mr-3' : 'ml-3'; ?> relative">
                        <div class="flex items-center space-x-4 <?php echo isRTL() ? 'space-x-reverse' : ''; ?>">
                            <span class="text-gray-700"><?php echo getCurrentLanguage() === 'dv' ? 'މަރުހަބާ' : 'Welcome'; ?>, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                            <a href="logout.php" class="bg-primary text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150">
                                <i class="fas fa-sign-out-alt <?php echo isRTL() ? 'ml-1' : 'mr-1'; ?>"></i><?php echo t('logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="sm:hidden flex items-center">
                    <button type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>        <!-- Mobile menu -->
        <div id="mobile-menu" class="sm:hidden hidden">
            <div class="pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="bg-primary text-white block pl-3 pr-4 py-2 border-l-4 border-primary text-base font-medium"><?php echo t('dashboard'); ?></a>
                <a href="pos.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('pos'); ?></a>
                <a href="products.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('products'); ?></a>
                <a href="customers.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('customers'); ?></a>
                <a href="sales.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('sales'); ?></a>
                <a href="credit.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('credit'); ?></a>
                <?php if (hasRole('admin') || hasRole('manager')): ?>
                <a href="reports.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('reports'); ?></a>
                <?php endif; ?>
                <?php if (hasRole('admin')): ?>
                <a href="admin.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('admin'); ?></a>
                <?php endif; ?>
                <a href="logout.php" class="text-gray-600 hover:text-gray-800 hover:bg-gray-50 block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"><?php echo t('logout'); ?></a>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?php echo isLoggedIn() ? 'max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8' : ''; ?>">
        <?php displayAlert(); ?>
        <?php echo $content ?? ''; ?>
    </main>    <!-- Scripts -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
        
        // Language switching function
        function changeLanguage(language) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'set_language.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'language';
            input.value = language;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
