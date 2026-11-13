# Laravel API Backend - Expense Tracker

A Laravel-based REST API for the Bede Bestoon expense tracker app, built with Laravel 11 and Laravel Sanctum.

## Features

- **User Management** - Registration, login, logout (with Sanctum)
- **Categories** - Income and expense categories with Persian names
- **Transactions** - Full CRUD for financial transactions
- **Financial Analytics** - Summary, monthly reports, category breakdowns
- **CORS Enabled** - Cross-origin requests supported
- **RESTful Design** - Standard REST API patterns

## API Endpoints

### Authentication (Protected Routes)
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout (requires auth)
- `GET /api/user` - Get current user info (requires auth)

### User Management (Protected Routes)
- `GET /api/users` - List all users (paginated)
- `POST /api/users` - Create a new user
- `GET /api/users/{id}` - Get user by ID
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

### Categories (Public - Uses Default User)
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create a new category
- `GET /api/categories/{id}` - Get category by ID
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category
- `GET /api/categories/type/{type}` - Filter categories by type (income/expense)

### Transactions (Public - Uses Default User)
- `GET /api/transactions` - List all transactions (paginated)
- `POST /api/transactions` - Create a new transaction
- `GET /api/transactions/{id}` - Get transaction by ID
- `PUT /api/transactions/{id}` - Update transaction
- `DELETE /api/transactions/{id}` - Delete transaction
- `GET /api/transactions/type/{type}` - Filter transactions by type

### Financial Analytics (Public - Uses Default User)
- `GET /api/transactions/summary` - Get total income, expenses, and balance
- `GET /api/transactions/monthly-summary` - Monthly financial summary
- `GET /api/transactions/expenses-by-category` - Monthly expenses grouped by category
- `GET /api/transactions/recent` - Get recent transactions (last 10)

### System
- `GET /api/health` - Health check endpoint

## Default Data

The API comes pre-populated with:

### Default User
- **Email**: `user@example.com`
- **Password**: `password123`
- **ID**: `1`

### Default Categories
**Income Categories:**
- حقوق (Salary) - Green
- فروش (Sales) - Blue  
- سود سرمایه‌گذاری (Investment Profit) - Yellow
- هدیه (Gift) - Purple
- سایر درآمدها (Other Income) - Gray

**Expense Categories:**
- خوراک (Food) - Red
- حمل و نقل (Transportation) - Blue
- مسکن (Housing) - Purple
- پوشاک (Clothing) - Pink
- سرگرمی (Entertainment) - Purple
- سلامت (Health) - Green
- آموزش (Education) - Indigo
- سایر هزینه‌ها (Other Expenses) - Gray

### Sample Transactions
- Monthly salary: 5,000,000 (income)
- Grocery shopping: 500,000 (expense)

## Request/Response Format

### Standard Response Structure
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... }
}
```

### Error Response Structure
```json
{
  "message": "Error description",
  "errors": { ... }
}
```

## Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Environment setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=DefaultDataSeeder
   ```

4. **Start server:**
   ```bash
   php artisan serve
   ```

## Testing the API

### Test Categories
```bash
curl http://localhost:8000/api/categories
```

### Test Transactions
```bash
curl http://localhost:8000/api/transactions
```

### Test Financial Summary
```bash
curl http://localhost:8000/api/transactions/summary
```

### Create a New Transaction
```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Shopping",
    "description": "Weekly groceries",
    "amount": 300000,
    "category_id": 6,
    "type": "expense",
    "date": "2025-08-21"
  }'
```

## Database Schema

### Categories Table
- `id` - Primary key
- `name` - Category name (Persian)
- `type` - 'income' or 'expense'
- `color` - ARGB color value
- `icon` - Material icon codepoint
- `user_id` - Foreign key to users
- `created_at`, `updated_at` - Timestamps

### Transactions Table
- `id` - Primary key
- `title` - Transaction title
- `description` - Transaction description
- `amount` - Amount (decimal)
- `category_id` - Foreign key to categories
- `type` - 'income' or 'expense'
- `date` - Transaction date
- `user_id` - Foreign key to users
- `created_at`, `updated_at` - Timestamps

## Notes

- **Authentication**: Currently disabled for categories and transactions (uses default user ID 1)
- **Localization**: Categories use Persian names as in the Flutter app
- **Currency**: Amounts are stored as decimals (supports Iranian Rial)
- **Pagination**: Transaction lists are paginated (20 per page)
- **Relationships**: Transactions include category data when fetched

## Next Steps

To enable full authentication:
1. Uncomment the auth middleware in routes
2. Update controllers to use `$request->user()->id` instead of hardcoded user ID
3. Test with proper Bearer tokens
# Expense-Tracker
