# Product Management System

A small admin dashboard built with Laravel 13 and Angular 19. It manages products, categories, users, roles, and permissions, with English and Arabic support.

## Run locally

You will need PHP 8.4, Composer, MySQL, Node.js 22, npm, and Laravel Herd.

First, set up the API:

```powershell
Set-Location backend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database named `admin_dashboard`, then update the database credentials in `backend/.env`. Run the migrations and seed the sample data:

```powershell
php artisan migrate --seed
php artisan storage:link
herd link admin-dashboard-api
```

The API will be available at `http://admin-dashboard-api.test`.

In another terminal, start the frontend:

```powershell
Set-Location frontend
nvm use 22.22.3
npm install
npm start
```

Open `http://localhost:4200` and sign in with:

```text
Email: admin@example.com
Password: password
```

To rebuild the database from scratch, run `php artisan migrate:fresh --seed` from `backend`. This command deletes all existing database data.
