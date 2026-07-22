# 🚛 Packers & Movers Enterprise CRM 📊

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%207.4-8892BF.svg?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL Database](https://img.shields.io/badge/MySQL-Database-4479A1.svg?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![Bootstrap Framework](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com/)
[![Chart JS](https://img.shields.io/badge/Chart.js-Analytics-FF6384.svg?style=for-the-badge)](https://www.chartjs.org/)

An advanced, responsive, and compliance-ready **Packers & Movers Customer Relationship Management (CRM)** software. Built to automate shifting calculations, generate professional quotations, output GST tax invoices, manage Lorry Receipts (Bilty documents), track cash collections, and deliver visual business intelligence reports.

---

## 📸 Dashboard Preview & Visual Analytics

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 🚛 COMPANY DASHBOARD                                                        │
├─────────────────────────────────────────────────────────────────────────────┤
│  [ Today's Sales ]     [ Today's Cash ]    [ Outstanding ]   [ Customers ]  │
│     ₹95,200.00            ₹50,000.00         ₹45,200.00          150        │
├───────────────────────────────┬─────────────────────────────────────────────┤
│  MONTHLY SHIFTING BUSINESS    │  INVOICE PAYMENT STATUS                     │
│  (Last 6 Months Trend)        │  (Status Distribution)                      │
│                               │                                             │
│  Revenue  📈 ──────────────── │         .------.                            │
│  Cash     🟢 ──────────────── │        /  Paid  \   🟢 72%                  │
│                               │       |  Partial |  🟡 18%                  │
│   Jan  Feb  Mar  Apr  May  Jun│        \ Unpaid /   🔴 10%                  │
│                               │         '------'                            │
└───────────────────────────────┴─────────────────────────────────────────────┘
```

*Note: You can add actual screenshots in your repository under `assets/img/screenshots/` and update standard image links here.*

---

## 🌟 Premium Features

### 1. 📊 Power BI-style Business Dashboard
- Visualizes panned trends of **Gross Revenue** vs. **Cash Collections** over the last 6 months via Chart.js lines.
- Live distribution cutout doughnut of invoice status ratios (Paid, Partially Paid, Unpaid).
- Symmetric side-by-side grids tracking recent invoices and audit log activities.

### 2. 📋 Auto-Populating Lorry Receipt (Bilty) Generator
- One-click redirection from Invoices to LR creation.
- Parses quotation inventories JSON to:
  - Calculate total **Articles Count (boxes/pkgs)**.
  - Compile descriptive text dynamically (e.g. `2 Sofa, 1 Double Bed, 5 Cartons`).
- Prefills consignee name, mobile, GSTIN, origin/destination cities, truck number, and driver specifications.

### 3. 💸 Direct Payment ledger & Receipt System
- Record collections directly with remaining balances locked.
- Auto-generates professional Money Receipts with clean sequence numbering (e.g. `REC-B8E97F`).
- Automatically translates currency totals to words in the Indian Numbering format (Lakhs/Crores).

### 4. 🧾 GST Tax Invoices
- Formatted specifically for Goods Transport Agency (GTA) SAC 9965 standards.
- Automatic State classification check (Intrastate splits to CGST + SGST; Interstate defaults to IGST).
- Custom GST badges (0%, 5%, 12%, 18%) for quick estimations.

### 5. 💬 Emoji-rich WhatsApp Templates
- Format quotes, bills, and bilty copies into visual, copy-pasteable WhatsApp proposal layouts to share with clients.

---

## 🛠️ Technology Stack
- **Backend:** PHP 7.4+ (PDO MySQL Driver)
- **Frontend:** Bootstrap 5, Tabler Icons, Simplebar Scroll, jQuery 3.7
- **Analytics:** Chart.js CDN
- **Database:** Relational MySQL InnoDB engine

---

## 💾 Database Schema Overview

The CRM utilizes a structured MySQL relational schema:

| Table | Key Column | Description |
|---|---|---|
| `users` | `id (VARCHAR)` | Administrative credentials & login roles |
| `settings` | (Single row) | Company metadata (Name, Bank Details, Terms) |
| `customers` | `id (VARCHAR)` | Consignor / Consignee details ledger |
| `products` | `id (VARCHAR)` | Checklist items catalog with default rates |
| `quotations` | `id (VARCHAR)` | Inventory quotes & estimated charge details |
| `invoices` | `id (VARCHAR)` | GST Billing, Tax details, outstanding balances |
| `lorry_receipts`| `id (VARCHAR)` | Transit transport bilty documents |
| `payments` | `id (VARCHAR)` | Transaction collections records |
| `audit_log` | `id (INT)` | Activity tracking logs |

---

## 🚀 Installation & Local Setup

### 1. Prerequisite
- PHP 7.4 or above installed.
- MySQL Server running.

### 2. Database Initialization
Create a MySQL database named `movers_packers` (or custom name) and import your tables. The database connector file `db.php` automatically performs structural schema checks on page load:
```sql
CREATE DATABASE IF NOT EXISTS packers_movers;
```
Configure your connection string parameters inside [db.php](file:///c:/Users/ideep/OneDrive/Desktop/cromhasInida/db.php):
```php
$host = 'localhost';
$db   = 'packers_movers';
$user = 'root';
$pass = '';
```

### 3. Run Locally
Serve the application locally using PHP's built-in development server:
```bash
php -S localhost:8000
```
Navigate to `http://localhost:8000` in your web browser. Default credentials are created on first load (check your login screen instructions).

---

## 📈 Compliance & Calculations Standards
- **GTA SAC Code:** 9965 (Goods Transport Agency)
- **Tax Rules:** CGST (Central GST) and SGST (State GST) split 50/50 for local intra-state shifting (Odisha). IGST (Integrated GST) applied for interstate long-distance haulage.
- **Sequential Indexing:** Built-in maximum index calculators prevent collision crashes even if invoices are deleted.
