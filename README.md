
# 🏪 POS System - Point of Sale Management System

A comprehensive, multilingual Point of Sale (POS) system built with PHP, MySQL, and modern web technologies. Features full support for English and Dhivehi (ދިވެހި) languages with RTL layout support.

## 📸 Screenshots

### Main POS Interface
![POS Interface](screenshots/pos-interface.png)
*Main point of sale interface with product grid and cart management*

### Dhivehi RTL Support
![Dhivehi Interface](screenshots/dhivehi-interface.png)
*Complete Dhivehi language support with right-to-left layout*

### Dashboard Overview
![Dashboard](screenshots/dashboard.png)
*Administrative dashboard with sales analytics and system overview*

### Product Management
![Product Management](screenshots/product-management.png)
*Comprehensive product inventory management system*

## ✨ Features

### 🌐 Multilingual Support
- **English** and **Dhivehi (ދިވެހި)** language support
- Complete RTL (Right-to-Left) layout for Dhivehi
- Proper Thaana font rendering with Google Fonts
- Session-based language switching
- Admin-configurable default language

### 💰 Point of Sale
- **Real-time product search** with barcode scanning
- **Multiple payment methods**: Cash, Card, Digital, Credit
- **Dynamic pricing** with tax calculations
- **Discount management** with percentage and fixed amounts
- **Receipt generation** with customizable headers/footers
- **Customer selection** for sales tracking

### 📦 Inventory Management
- **Product catalog** with categories and images
- **Stock level tracking** with low stock alerts
- **Barcode support** for quick product identification
- **Cost and selling price management**
- **Tax rate configuration** per product

### 👥 Customer Management
- **Customer database** with contact information
- **Credit sales tracking** with payment history
- **Customer-specific transactions**
- **Address and contact management**

### 💳 Credit Sales
- **Credit transaction management**
- **Payment tracking** with partial payments
- **Due date monitoring** with overdue alerts
- **Payment history** and balance tracking
- **Flexible payment terms**

### 📊 Reporting & Analytics
- **Sales reports** with date range filtering
- **Revenue analytics** and trends
- **Product performance** tracking
- **Customer transaction history**
- **Credit sales monitoring**

### 🔧 System Administration
- **User management** with role-based access (Admin, Manager, Cashier)
- **System configuration** with branding options
- **Logo upload** and receipt customization
- **Category management** for products
- **Database backup** and maintenance tools

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **UI Framework**: Tailwind CSS 3.x
- **Icons**: Font Awesome 6.x
- **Fonts**: Noto Sans Thaana (for Dhivehi)
- **Server**: Apache/Nginx + PHP-FPM

## 📋 Requirements

### System Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: 512MB RAM minimum (1GB recommended)
- **Storage**: 500MB disk space

### PHP Extensions
```
- PDO MySQL
- GD Library (for image processing)
- mbstring
- json
- session
- fileinfo
```

## 🚀 Installation

### Option 1: Manual Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/pos-system.git
   cd pos-system
   ```

2. **Set up web server**
   - Copy files to your web root directory
   - For XAMPP: `C:\xampp\htdocs\pos\`
   - For WAMP: `C:\wamp64\www\pos\`

3. **Configure database**
   ```bash
   # Import the database structure
   mysql -u root -p < database/pos_system.sql
   
   # Import system settings and language support
   mysql -u root -p pos_system < system_settings.sql
   mysql -u root -p pos_system < dhivehi_language_setup.sql
   ```

4. **Configure application**
   ```php
   // Edit config/database.php
   $host = 'localhost';
   $dbname = 'pos_system';
   $username = 'your_username';
   $password = 'your_password';
   ```

5. **Set permissions**
   ```bash
   # Linux/Mac
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/system/
   
   # Windows: Ensure IIS_IUSRS has write permissions
   ```

### Option 2: Desktop Application

Build as a standalone desktop application using Electron:

```bash
# Install dependencies
npm install

# Build for Windows
npm run build-win

# Build for all platforms
npm run build-all
```

Detailed guide: [Desktop Application Setup](DESKTOP_SETUP.md)

## 🎯 Quick Start

### Default Login Credentials
```
Username: admin
Password: admin123
```

⚠️ **Important**: Change default credentials immediately after installation!

### First Steps
1. **Login** with default credentials
2. **Change admin password** in Admin → Users
3. **Configure system settings** in Admin → System Settings
4. **Set up your store information** (name, address, logo)
5. **Add product categories** in Admin → Categories
6. **Import or add products** in Products section
7. **Add customers** (optional) in Customers section
8. **Start selling** in POS interface

## 🌍 Language Configuration

### Setting Default Language
1. Go to **Admin → System Settings**
2. Select **Default Language**: English or ދިވެހި (Dhivehi)
3. Click **Save Settings**

### Adding New Languages
The system is designed for easy language expansion:

1. **Edit** `includes/language.php`
2. **Add translations** to both language arrays
3. **Update** JavaScript translations
4. **Test** with language switcher

Example:
```php
// Add to English array
'new_term' => 'New Term',

// Add to Dhivehi array  
'new_term' => 'އައު ޝަब्द',
```

## 📱 Usage Guide

### POS Operations
![POS Workflow](screenshots/pos-workflow.png)

1. **Select products** from the grid or search by name/barcode
2. **Adjust quantities** using +/- buttons in cart
3. **Choose customer** (optional, required for credit sales)
4. **Apply discounts** if applicable
5. **Select payment method**: Cash, Card, Digital, or Credit
6. **Complete sale** and print receipt

### Credit Sales Management
![Credit Management](screenshots/credit-management.png)

1. **Create credit sale** by selecting Credit payment method
2. **Set due date** and payment terms
3. **Track payments** in Credit section
4. **Record partial payments** as received
5. **Monitor overdue accounts**

### Inventory Management
![Inventory](screenshots/inventory-management.png)

1. **Add products** with images, pricing, and stock levels
2. **Set minimum stock levels** for low stock alerts
3. **Update stock** as inventory changes
4. **Monitor product performance** in reports

## 🔒 Security Features

- **Password hashing** with PHP `password_hash()`
- **Role-based access control** (Admin, Manager, Cashier)
- **Session management** with automatic timeouts
- **SQL injection prevention** with prepared statements
- **Input validation** and sanitization
- **File upload security** with type restrictions

## 🎨 Customization

### Theming
- **Colors**: Modify Tailwind config in `includes/layout.php`
- **Logo**: Upload via Admin → System Settings
- **Receipt format**: Customize in `print_receipt.php`

### Adding Features
- **New user roles**: Extend in `includes/functions.php`
- **Product attributes**: Modify products table schema
- **Custom reports**: Add to `reports.php`
- **Payment methods**: Extend payment processing

## 📊 Screenshots Gallery

<table>
  <tr>
    <td><img src="screenshots/login.png" alt="Login Screen" width="300"/></td>
    <td><img src="screenshots/dashboard.png" alt="Dashboard" width="300"/></td>
    <td><img src="screenshots/pos-main.png" alt="POS Interface" width="300"/></td>
  </tr>
  <tr>
    <td align="center"><b>Login Screen</b></td>
    <td align="center"><b>Dashboard</b></td>
    <td align="center"><b>POS Interface</b></td>
  </tr>
  <tr>
    <td><img src="screenshots/products.png" alt="Product Management" width="300"/></td>
    <td><img src="screenshots/customers.png" alt="Customer Management" width="300"/></td>
    <td><img src="screenshots/reports.png" alt="Reports" width="300"/></td>
  </tr>
  <tr>
    <td align="center"><b>Product Management</b></td>
    <td align="center"><b>Customer Management</b></td>
    <td align="center"><b>Reports</b></td>
  </tr>
  <tr>
    <td><img src="screenshots/dhivehi-pos.png" alt="Dhivehi POS" width="300"/></td>
    <td><img src="screenshots/credit-sales.png" alt="Credit Sales" width="300"/></td>
    <td><img src="screenshots/receipt.png" alt="Receipt" width="300"/></td>
  </tr>
  <tr>
    <td align="center"><b>Dhivehi Interface</b></td>
    <td align="center"><b>Credit Sales</b></td>
    <td align="center"><b>Receipt Generation</b></td>
  </tr>
</table>

## 🚨 Troubleshooting

### Common Issues

**Database Connection Error**
```
Solution: Check config/database.php credentials and ensure MySQL is running
```

**Permission Denied Errors**
```
Solution: Set proper write permissions on uploads/ directory
```

**Language Not Switching**
```
Solution: Clear browser cache and ensure session support is enabled
```

**Images Not Uploading**
```
Solution: Check file permissions and PHP upload_max_filesize setting
```

**RTL Layout Issues**
```
Solution: Clear browser cache and verify Thaana font is loading
```

## 🛣️ Roadmap

### Upcoming Features
- [ ] **Mobile app** with React Native
- [ ] **Cloud synchronization** with API
- [ ] **Advanced analytics** with charts
- [ ] **Multi-location support** 
- [ ] **Inventory alerts** via email/SMS
- [ ] **Barcode label printing**
- [ ] **Supplier management**
- [ ] **Purchase orders**
- [ ] **Staff time tracking**
- [ ] **Customer loyalty programs**

### Language Expansion
- [ ] **Arabic** (العربية) support
- [ ] **Hindi** (हिन्दी) support  
- [ ] **Tamil** (தமிழ்) support
- [ ] **Sinhala** (සිංහල) support

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Code Standards
- **PSR-12** PHP coding standards
- **ESLint** for JavaScript
- **Meaningful** commit messages
- **Comprehensive** documentation

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Authors

- **Your Name** - *Initial work* - [YourGitHub](https://github.com/yourusername)

See also the list of [contributors](https://github.com/yourusername/pos-system/contributors) who participated in this project.

## 🙏 Acknowledgments

- **Tailwind CSS** for the beautiful UI framework
- **Font Awesome** for the comprehensive icon set
- **Google Fonts** for Noto Sans Thaana typography
- **PHP Community** for excellent documentation
- **Open Source Community** for inspiration and tools

## 📞 Support

### Getting Help
- **📖 Documentation**: Check the [Implementation Guide](IMPLEMENTATION_GUIDE.md)
- **🐛 Bug Reports**: Open an [issue](https://github.com/yourusername/pos-system/issues)
- **💡 Feature Requests**: Start a [discussion](https://github.com/yourusername/pos-system/discussions)
- **💬 Community**: Join our [Discord](https://discord.gg/yourinvite)

### Professional Support
For commercial support, custom development, or enterprise solutions:
- **Email**: support@yourcompany.com
- **Website**: https://yourcompany.com

---

<div align="center">

**⭐ Star this repo if you find it helpful!**

Made with ❤️ for the retail community

[🌟 Give it a star](https://github.com/yourusername/pos-system) • [🐛 Report Bug](https://github.com/yourusername/pos-system/issues) • [💡 Request Feature](https://github.com/yourusername/pos-system/issues)

</div>

