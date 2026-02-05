# 📦 Inventory Management System - Backend

RESTful API for inventory management built with Laravel 11 using modular architecture and event-driven design.

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)

---

## ✨ Features

### 🏗️ Architecture
- **Modular Design** - Separated modules for scalability
- **Event-Driven** - Laravel Events and Listeners for loose coupling
- **RESTful API** - Clean and consistent API endpoints

### 🔐 Authentication
- Laravel Sanctum for API tokens
- Role-based access (Admin, Manager, Staff)
- Password reset functionality

### 📦 Modules
| Module | Description |
|--------|-------------|
| Auth | User authentication and management |
| Product | Product CRUD operations |
| Category | Category management |
| Supplier | Supplier management |
| Order | Order processing with events |
| Stock | Stock movements and tracking |
| Report | Analytics and reporting |

---

## 🛠️ Tech Stack

| Technology | Purpose |
|------------|---------|
| Laravel 11 | PHP Framework |
| PHP 8.2+ | Programming Language |
| MySQL 8.0 | Database |
| Laravel Sanctum | API Authentication |

---

## 📁 Project Structure

```
app/
├── Models/
│   └── User.php
│
├── Modules/
│   ├── Auth/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Routes/
│   │
│   ├── Product/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Events/
│   │   └── Routes/
│   │
│   ├── Category/
│   ├── Supplier/
│   ├── Order/
│   ├── Stock/
│   └── Report/
│
└── Providers/
    └── ModuleServiceProvider.php
```

---

## 🔌 API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login user |
| POST | `/api/logout` | Logout user |
| GET | `/api/user` | Get current user |

### Products

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List all products |
| POST | `/api/products` | Create product |
| GET | `/api/products/{id}` | Get single product |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | List all categories |
| POST | `/api/categories` | Create category |
| GET | `/api/categories/{id}` | Get single category |
| PUT | `/api/categories/{id}` | Update category |
| DELETE | `/api/categories/{id}` | Delete category |

### Suppliers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/suppliers` | List all suppliers |
| POST | `/api/suppliers` | Create supplier |
| GET | `/api/suppliers/{id}` | Get single supplier |
| PUT | `/api/suppliers/{id}` | Update supplier |
| DELETE | `/api/suppliers/{id}` | Delete supplier |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List all orders |
| POST | `/api/orders` | Create order |
| GET | `/api/orders/{id}` | Get single order |
| PUT | `/api/orders/{id}` | Update order |
| PATCH | `/api/orders/{id}/status` | Update status |

### Stock Movements

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/stock-movements` | List movements |
| POST | `/api/stock-movements` | Create movement |

### Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reports/dashboard` | Dashboard stats |
| GET | `/api/reports/inventory` | Inventory report |
| GET | `/api/reports/sales` | Sales report |

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0
- XAMPP / Laragon

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/inventory-management-backend.git

# Navigate to project directory
cd inventory-management-backend

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000`

---

## 🔧 Environment Variables

Update your `.env` file:

```env
APP_NAME="Inventory Management"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_db
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

---

## 🔄 Event-Driven Architecture

When an order is created, events automatically update stock:

```
Order Created
     │
     ▼
OrderCreated Event
     │
     ▼
UpdateStockListener
     │
     ▼
Stock Updated Automatically
```

---

## 🧪 API Testing

### Using Postman

1. Set base URL: `http://localhost:8000/api`
2. For authenticated routes, add header:
   ```
   Authorization: Bearer {your-token}
   ```

### Example: Login Request

```bash
POST /api/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password123"
}
```

---

## 🔗 Frontend Repository

This API is designed to work with the React frontend.

**Frontend Repository:** [inventory-management-frontend](https://github.com/dawood125/inventory-management-frontend)

---

## 👨‍💻 Author

**Dawood Ahmed**

- GitHub: [@dawood125](https://github.com/dawood125)  
- LinkedIn: [Dawood Ahmed](linkedin.com/in/dawood-ahmed-8953b63a2)
- Email: dawood.bhatti8812@gmail.com
