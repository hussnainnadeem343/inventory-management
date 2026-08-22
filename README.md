# Inventory Management

A Laravel 13, MySQL, Blade and Bootstrap 5 inventory application. It includes login-only authentication, Super Admin and User roles, inventory/brand/category/user management, search, pagination, seed data, server-side validation, authorization, and tests.

## Requirements

- PHP 8.3+ with OpenSSL, Mbstring, PDO MySQL, Fileinfo and Tokenizer
- Composer 2
- MySQL 8+
- Git

No Node.js build is required: the UI loads Bootstrap 5 from its official CDN.

## Installation

```bash
git clone <your-repository-url> inventory-management
cd inventory-management
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database, configure `.env`, then run:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Default login

- Email: `admin@example.com`
- Password: `password`

Override these before seeding with `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env`. Change the default password immediately outside local development.

## Roles and decisions

- **Super Admin:** manages users, brands, categories, and inventory.
- **User:** views brands/categories and manages inventory. Restricted URLs are blocked on the server.
- Inventory, brands, and categories use soft deletes, while referenced brands/categories remain protected.
- Users with authored business records cannot be deleted; deactivate them instead. Super Admins and the current account cannot be deleted.
- Units are centralized in `InventoryItem::UNITS` for easy extension.

## Tests

Tests use SQLite in memory and cover registration removal, inactive login, authorization, server-controlled `created_by`, referenced-brand deletion, and self-deletion protection.

```bash
php artisan test
```

For production, set `APP_ENV=production`, `APP_DEBUG=false`, use strong credentials, configure HTTPS, and run `php artisan config:cache`.
