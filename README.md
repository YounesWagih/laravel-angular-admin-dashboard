# Product Management System

A Laravel API and Angular single-page application for managing products, categories, users, roles, and permissions in English and Arabic.

## Workspace

- `backend/` — Laravel 13 API served locally by Laravel Herd
- `frontend/` — Angular 19 standalone application

## Requirements

- Laravel Herd 1.29 or newer
- PHP 8.4 through Herd
- Composer 2
- MySQL 8
- Node.js 22.22.3 (managed through Herd's bundled NVM)
- npm 11 or newer

## Backend setup

```powershell
Set-Location backend
composer install
Copy-Item .env.example .env
php artisan key:generate
herd link admin-dashboard-api
```

Configure the MySQL credentials in `backend/.env`. Herd serves the API at:

```text
http://admin-dashboard-api.test
```

## Frontend setup

```powershell
nvm use 22.22.3
Set-Location frontend
npm install
npm start
```

The Angular application runs at `http://localhost:4200`. Its development proxy forwards `/api` and `/sanctum` requests to the Herd site.

## Quality checks

```powershell
Set-Location backend
vendor/bin/pint --test

Set-Location ../frontend
npm run lint
npm run build
```
