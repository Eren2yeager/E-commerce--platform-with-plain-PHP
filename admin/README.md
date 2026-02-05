# Admin Panel - ShopHub

## Setup Instructions

### 1. Create Admin User

Run the SQL commands in `setup_admin.sql` or manually create an admin user:

```sql
-- Add role column to users table
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'customer';

-- Create admin user (username: admin, password: admin123)
INSERT INTO users (username, email, password, role, created_at) 
VALUES (
    'admin',
    'admin@shophub.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    NOW()
);
```

### 2. Login

- URL: `http://localhost/your-project/admin/login.php`
- Username: `admin`
- Password: `admin123`

### 3. Features

#### Dashboard
- View statistics (total products, users, stock levels)
- Quick actions
- Recent products list

#### Product Management
- **List Products**: View all products with search
- **Add Product**: Create new products
- **Edit Product**: Update product details
- **Delete Product**: Remove products (with confirmation)

## File Structure

```
admin/
├── auth.php          # Admin authentication functions
├── header.php        # Admin panel header/navigation
├── login.php         # Admin login page
├── logout.php        # Logout handler
├── dashboard.php     # Admin dashboard
├── products.php      # Product CRUD operations
├── setup_admin.sql   # SQL to create admin user
└── README.md         # This file
```

## Security Notes

1. **Change Default Password**: After first login, create a new admin user with a strong password
2. **Role-Based Access**: Only users with `role='admin'` can access the admin panel
3. **Session Management**: Admin sessions are separate from customer sessions
4. **SQL Injection Protection**: All queries use prepared statements

## Next Steps

### Recommended Enhancements:
1. **Image Upload**: Add file upload functionality for product images
2. **Order Management**: View and manage customer orders
3. **User Management**: Manage customer accounts
4. **Analytics**: Add charts and graphs for sales data
5. **Bulk Operations**: Import/export products via CSV

## Laravel Comparison

This admin panel teaches you concepts you'll use in Laravel:

- **Authentication**: Laravel has `Auth` middleware
- **CRUD Operations**: Laravel uses Eloquent ORM
- **Forms**: Laravel has form validation and CSRF protection
- **File Uploads**: Laravel has built-in file storage
- **Admin Panels**: Laravel has packages like Nova, Filament

The logic is the same, Laravel just provides better tools!
