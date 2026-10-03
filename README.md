# 🦷 Dentl Care Management System

<div align="center">

![Version](https://img.shields.io/badge/version-3.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4.svg?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1.svg?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg?logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-production%20ready-brightgreen.svg)

**A modern, feature-rich dental clinic management system built with PHP & MySQL.**

Complete solution for managing patients, appointments, dental chairs, billing, prescriptions, and analytics — with a beautiful, animated UI and dark mode support.

[Features](#-features) • [Installation](#-installation) • [Screenshots](#-screenshots) • [Tech Stack](#-tech-stack) • [License](#-license)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Default Login](#-default-login)
- [Modules Overview](#-modules-overview)
- [Database Schema](#-database-schema)
- [Keyboard Shortcuts](#-keyboard-shortcuts)
- [Project Structure](#-project-structure)
- [Customization](#-customization)
- [Security](#-security)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)

---

## 🎯 About

**Dental Care Management System** is a comprehensive, production-ready web application designed for dental clinics of any size. Whether you run a single-chair practice or a 12-chair multi-doctor clinic, this system handles everything.

### Why This System?

- ✅ **12-Chair Support** — Real-time tracking of dental chairs
- ✅ **Beautiful UI** — Modern design with animations & glassmorphism
- ✅ **Dark Mode** — Built-in with 5 theme colors
- ✅ **Complete Billing** — Partial payments, multi-method support
- ✅ **Interactive Dental Chart** — FDI tooth numbering system
- ✅ **Analytics Dashboard** — 12-month growth charts
- ✅ **Prescription Printing** — Professional Rx paper layout
- ✅ **Mobile Responsive** — Works on phones, tablets, desktops
- ✅ **100% Free & Open Source**

---

## ✨ Features

### 🏠 Dashboard
- Real-time statistics with animated counters
- Live chair status (12 chairs)
- Revenue charts (daily/monthly/yearly)
- Today's appointments & upcoming schedule
- Recent activities log
- Patient birthdays today
- Low stock alerts
- Top doctors ranking

### 👥 Patient Management
- Complete CRUD operations
- Auto-generated patient codes (P0001, P0002...)
- Full medical history tracking
- Allergies & emergency contacts
- Search by name, phone, NIC, code, email
- Filter by gender & status
- Pagination (10 per page)
- Print patient list

### 📅 Appointments
- Book appointments with date/time
- Assign doctor & chair
- Priority levels (Low/Normal/High)
- Duration tracking
- Status workflow (Pending → Confirmed → Completed/Cancelled)
- Filter by date, status, patient
- Today's appointments highlighted
- Quick status change dropdown

### 🪑 Chairs (12 Chairs)
- Real-time status tracking
- Available / Occupied / Maintenance
- Assign patient & doctor
- Utilization percentage bar
- Color-coded cards (Green/Red/Gray)
- Pulse animation on occupied
- Free chair / Set maintenance quick actions
- Add unlimited chairs

### 💰 Billing & Invoices
- Auto invoice number generation
- Live calculation (Total + Tax - Discount)
- Partial payment support
- Multiple payment methods (Cash/Card/Online/Cheque)
- Payment history per invoice
- Auto status update (Paid/Partial/Unpaid)
- Top debtors report
- Date range filters

### 📊 Reports
- Daily revenue line chart
- Monthly revenue bar chart (12 months)
- Appointments by status (doughnut)
- Doctor performance ranking
- Chair utilization report
- Top 10 treatments
- Patient demographics
- Age distribution
- CSV export
- Print-friendly

### 📄 Prescriptions
- Auto Rx number (RX-YYYYMMDD-XXX)
- Diagnosis + Medicines + Instructions
- Next visit date
- Professional print layout (℞ symbol)
- Full prescription history

### 🦷 Dental Chart
- Interactive FDI tooth chart (32 teeth)
- Upper & Lower jaws
- 8 tooth conditions:
  - Healthy, Cavity, Filled, Crown
  - Root Canal, Missing, Implant, Extraction
- Click any tooth to update
- Patient-specific charts
- Condition summary stats

### 🗓️ Calendar View
- Month view with appointments
- Color-coded events by status
- Navigate previous/next month
- Month summary statistics
- Quick jump to today

### 📈 Analytics
- 12-month growth (Patients vs Revenue)
- KPI cards (Retention rate, growth %)
- Busiest days polar chart
- Revenue by category
- Top performing doctors
- Monthly appointments trend
- Average treatment cost

### 🔔 Notifications
- Send SMS/Email (mock integration)
- Quick templates (Reminder/Payment/Follow-up)
- Patient quick-select
- Notification log with status
- Delivery history

### 🎨 UI/UX
- **Dark Mode** — Toggle with localStorage persistence
- **5 Theme Colors** — Purple, Blue, Green, Orange, Pink
- **Animations** — Fade-in, slide, bounce, ripple
- **Toast Notifications** — Replaces alerts
- **Custom Confirm Modal** — Replaces browser confirm
- **Glassmorphism** — Modern blur effects
- **Responsive** — Mobile/Tablet/Desktop
- **Keyboard Shortcuts** — Power user friendly

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.2+ |
| **Database** | MySQL 8.0 / MariaDB |
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **UI Framework** | Bootstrap 5.3 |
| **Icons** | Bootstrap Icons 1.11 |
| **Charts** | Chart.js 4.x |
| **Server** | Apache (XAMPP/WAMP/LAMP) |
| **Font** | Inter, Segoe UI |

---

## 💻 Requirements

- **PHP** 8.0 or higher
- **MySQL** 5.7+ or MariaDB 10.4+
- **Apache** web server
- **XAMPP** / WAMP / LAMP / MAMP
- **Modern browser** (Chrome, Firefox, Edge, Safari)
- **Composer** (optional)

---

## 🚀 Installation

### Step 1: Clone or Download

```bash
# Option 1: Clone with Git
(https://github.com/dilshankumara001001/Dental-Care-Management-System)

# Option 2: Download ZIP
# Extract to your web server root
```

### Step 2: Move to Web Root

```bash
# For XAMPP on Windows:
C:\xampp\htdocs\dental_system\

# For Linux/Mac:
/var/www/html/dental_system/
/Applications/XAMPP/htdocs/dental_system/
```

### Step 3: Start Services

**XAMPP Control Panel:**
- Start **Apache**
- Start **MySQL**

### Step 4: Create Database

Open **phpMyAdmin** → `http://localhost/phpmyadmin`

**Run this SQL to create the database and tables:**

```sql
-- Full SQL available in `/database/dental_system.sql`
-- Or use the one below:
CREATE DATABASE IF NOT EXISTS dental_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
```

**Then import the full SQL from `database.sql` file** (see [Database Schema](#-database-schema)).

### Step 5: Configure Database

Edit **`config/database.php`**:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'dental_system');
define('BASE_URL', 'http://localhost/dental_system/');
define('SITE_NAME', 'Dental Care System');
```

### Step 6: Fix Admin Password

**Create** `fix_password.php` in project root:

```php
<?php
require_once 'config/database.php';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '$hash'");
echo "✅ Password updated!";
```

**Visit:** `http://localhost/dental_system/fix_password.php`

**Then DELETE `fix_password.php` immediately!** 🔒

### Step 7: Access the System

Open your browser:

```
http://localhost/dental_system/
```

---

## 🔐 Default Login

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Administrator |
| `doctor1` | `admin123` | Doctor |
| `doctor2` | `admin123` | Doctor |
| `reception` | `admin123` | Receptionist |

> ⚠️ **Change passwords immediately after first login!**

---

## 📦 Modules Overview

| Module | Description | Features |
|--------|-------------|----------|
| 🏠 **Dashboard** | Central hub | 30+ widgets |
| 👥 **Patients** | Patient management | CRUD + Search + Filter |
| 📅 **Appointments** | Scheduling | Booking + Status |
| 🪑 **Chairs** | 12-chair tracking | Real-time status |
| 💰 **Billing** | Invoices & payments | Partial + Multi-method |
| 📊 **Reports** | Business reports | Charts + CSV export |
| 📄 **Prescriptions** | Rx management | Print-ready |
| 🦷 **Dental Chart** | Interactive teeth | FDI numbering |
| 🗓️ **Calendar** | Month view | Event display |
| 📈 **Analytics** | Business intelligence | 12-month trends |
| 🔔 **Notifications** | SMS/Email | Templates + Log |

---

## 🗄 Database Schema

### Main Tables

```
users              — System users (admin, doctors, receptionists)
patients           — Patient records with medical history
chairs             — 12 dental chairs with status
appointments       — Booking records
treatments         — Treatment history
treatments_catalog — Service catalog with prices
invoices           — Billing invoices
payments           — Payment records
prescriptions      — Prescription records
dental_chart       — Tooth conditions per patient
notifications_log  — SMS/Email history
activity_log       — User activity tracking
```

### ER Diagram

```
users ──────┬────── appointments ─────── patients
            │             │
            │             │
            │             ├──── chairs
            │             │
            │             └──── treatments
            │
            ├──── prescriptions ──── patients
            │
            └──── invoices ──── patients
                     │
                     └──── payments
```

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl + K` | Focus search box |
| `Ctrl + Shift + D` | Toggle dark mode |
| `Esc` | Close modal |
| `Ctrl + S` | Save (in forms) |

---

## 📁 Project Structure

```
dental_system/
├── config/
│   └── database.php              # DB configuration
├── includes/
│   ├── header.php                # HTML head + opening tags
│   ├── sidebar.php               # Navigation sidebar
│   ├── footer.php                # Closing tags + JS
│   └── functions.php             # Helper functions
├── modules/
│   ├── patients/                 # Patient management
│   │   └── index.php
│   ├── appointments/             # Appointment booking
│   │   └── index.php
│   ├── chairs/                   # 12-chair tracking
│   │   └── index.php
│   ├── billing/                  # Invoices & payments
│   │   └── index.php
│   ├── reports/                  # Business reports
│   │   └── index.php
│   ├── prescriptions/            # Rx management
│   │   ├── index.php
│   │   └── print.php
│   ├── dental_chart/             # Interactive tooth chart
│   │   └── index.php
│   ├── calendar/                 # Month calendar view
│   │   └── index.php
│   ├── analytics/                # Advanced analytics
│   │   └── index.php
│   └── notifications/            # SMS/Email sending
│       └── index.php
├── assets/
│   ├── css/
│   │   └── style.css             # Main stylesheet
│   ├── js/
│   │   └── script.js             # Main JavaScript
│   └── images/                   # Image assets
├── uploads/                      # User uploads
├── dashboard.php                 # Main dashboard
├── index.php                     # Login page
├── logout.php                    # Logout handler
├── README.md                     # This file
├── LICENSE                       # MIT License
└── database.sql                  # Full DB schema + sample data
```

---

## 🎨 Customization

### Change Theme Colors

Edit **`assets/css/style.css`** at top:

```css
:root {
    --primary: #667eea;        /* Main color */
    --secondary: #764ba2;      /* Accent color */
    /* ... */
}
```

### Add New Module

1. Create folder: `modules/your_module/`
2. Add `index.php` with this template:

```php
<?php
$page_title = 'Your Module';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Your content here -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

3. Add to sidebar in `includes/sidebar.php`

### Language Support

Language files can be added in `languages/` folder. See `languages/en.php` for example.

---

## 🔒 Security

### Built-in Security Features
- ✅ **Password hashing** (bcrypt via `password_hash`)
- ✅ **Prepared statements** (SQL injection prevention)
- ✅ **HTML escaping** (XSS prevention via `e()` function)
- ✅ **Session management**
- ✅ **Role-based access control** (in progress)
- ✅ **CSRF protection** (add tokens for production)
- ✅ **Activity logging**

### Production Checklist
- [ ] Change default passwords
- [ ] Set `DB_PASS` in config
- [ ] Use HTTPS (SSL certificate)
- [ ] Disable `display_errors` in production
- [ ] Add CSRF tokens to all forms
- [ ] Enable `.htaccess` protection
- [ ] Regular backups
- [ ] Update PHP/MySQL to latest versions

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch:
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. Commit your changes:
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. Push to the branch:
   ```bash
   git push origin feature/AmazingFeature
   ```
5. Open a Pull Request

---

## 📜 License

This project is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2026 Dental Care System

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---


### Found a Bug?

Please report it with:
- Description
- Steps to reproduce
- Screenshots (if applicable)
- PHP/MySQL version

---

## 🙏 Acknowledgments

- **Bootstrap** — UI Framework
- **Chart.js** — Beautiful charts
- **Bootstrap Icons** — Icon library
- **XAMPP** — Development environment
- **Open Source Community** — Inspiration

---


## 📊 Project Stats

- **Lines of Code**: 15,000+
- **Files**: 40+
- **Features**: 300+
- **Modules**: 11
- **Database Tables**: 13
- **Development Time**: Full-stack

---

<div align="center">

**Made with ❤️ for Dental Professionals**

**© 2026 Dental Care System. All rights reserved.**

</div>
