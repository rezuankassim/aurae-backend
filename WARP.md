# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is the **Aurae Backend** - a Laravel 12 + React (Inertia.js) application for managing an IoT health device ecosystem. The system includes an admin panel for device management, user management, e-commerce (using Lunar PHP), music therapy modes, health reports, and knowledge center content.

## Development Commands

### Initial Setup
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file and configure
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (if applicable)
php artisan db:seed
```

### Development Server
```bash
# Start all development services (recommended)
composer dev
# This runs: Laravel server, queue listener, Pail logs, and Vite dev server concurrently

# Or run services individually:
php artisan serve              # Laravel server (port 8000)
php artisan queue:listen       # Queue worker
php artisan pail               # View logs in real-time
npm run dev                    # Vite dev server
```

### Building Assets
```bash
# Build frontend assets for production
npm run build

# Build with SSR support (if needed)
npm run build:ssr
composer dev:ssr  # Then run SSR server
```

### Code Quality

#### Frontend (TypeScript/React)
```bash
# Run ESLint with auto-fix
npm run lint

# Check TypeScript types (no emit)
npm run types

# Format code with Prettier
npm run format

# Check formatting without modifying files
npm run format:check
```

#### Backend (PHP)
```bash
# Format PHP code with Laravel Pint
./vendor/bin/pint

# Run specific file/directory
./vendor/bin/pint app/Http/Controllers
```

### Testing
```bash
# Run all tests (uses Pest PHP)
composer test
# Or: php artisan test

# Run specific test file
php artisan test tests/Feature/AuthTest.php

# Run specific test by name
php artisan test --filter=login_test
```

## Architecture Overview

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: React 19 + TypeScript + Inertia.js
- **Styling**: Tailwind CSS 4 + Radix UI + shadcn/ui components
- **Database**: SQLite (development), supports MySQL/PostgreSQL
- **E-commerce**: Lunar PHP package
- **Rich Text Editor**: Lexical
- **Type-safe Routing**: Laravel Wayfinder (generates TypeScript route definitions)
- **Testing**: Pest PHP

### Directory Structure

#### Backend (`app/`)
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Admin panel controllers (News, Products, Users, Therapies, etc.)
│   │   ├── Api/            # Mobile API controllers (Authentication, Device, FAQ)
│   │   ├── Auth/           # Authentication controllers
│   │   └── Settings/       # Settings controllers
│   ├── Middleware/         # Custom middleware (EnsureIsAdmin, EnsureDevice)
│   ├── Requests/           # Form request validation
│   └── Resources/          # API resources
├── Models/                 # Eloquent models (User, Device, News, Therapy, etc.)
├── Listeners/              # Event listeners
└── Providers/              # Service providers
```

#### Frontend (`resources/js/`)
```
resources/js/
├── pages/                  # Inertia page components (route views)
├── components/             # React components
│   ├── ui/                 # shadcn/ui components (Button, Dialog, Table, etc.)
│   ├── editor/             # Lexical rich text editor components
│   └── ...                 # App-specific components (AppSidebar, AppHeader, etc.)
├── layouts/                # Layout components (AppLayout, AuthLayout, etc.)
├── routes/                 # Wayfinder-generated TypeScript routes
├── actions/                # Frontend actions/utilities
├── hooks/                  # React custom hooks
├── types/                  # TypeScript type definitions
└── lib/                    # Utility libraries
```

#### Routes (`routes/`)
```
routes/
├── admin.php               # Admin panel routes (protected by auth + EnsureIsAdmin)
├── api.php                 # Mobile API routes (protected by EnsureDevice middleware)
├── auth.php                # Authentication routes
├── settings.php            # Settings routes
└── web.php                 # Public web routes
```

### Key Architectural Patterns

#### Route Organization
- **Admin routes** (`/admin/*`): Require authentication + `EnsureIsAdmin` middleware
- **API routes** (`/api/*`): Require `EnsureDevice` middleware for device authentication
- **Type-safe routing**: Use Wayfinder generated routes from `resources/js/routes/`
  - Example: `import { admin } from '@/routes/admin'` then `admin.news.index()`

#### Frontend Data Flow
- **Inertia.js**: Server-side rendered React pages with automatic data sharing
- **Page components**: Located in `resources/js/pages/`, automatically resolved by Inertia
- **Type-safe forms**: Wayfinder provides type-safe form definitions
- **Shared data**: Accessed via `usePage().props` hook

#### Component Structure
- **UI Components**: shadcn/ui components in `resources/js/components/ui/`
- **App Components**: Application-specific reusable components in `resources/js/components/`
- **Layouts**: Page layouts in `resources/js/layouts/`
- **Styling**: Use `cn()` utility for merging Tailwind classes (from `class-variance-authority`)

#### Backend Patterns
- **Resource Controllers**: RESTful controllers follow Laravel conventions
- **Form Requests**: Validation in `app/Http/Requests/`
- **API Resources**: JSON transformations in `app/Http/Resources/`
- **Eloquent Models**: Relationships and business logic in `app/Models/`

### Module Features (from README)

Implemented modules:
- Authentication & User Management (with login activity tracking)
- Health Report Management (upload/manage)
- E-commerce (Product CRUD, categories, inventory via Lunar PHP)
- News & Advertisement Management
- Device & Maintenance Tracking
- Music Therapy Mode Management
- Knowledge Center Management
- Social Media Link Configuration
- FAQ Management

Partially implemented:
- Device Management (maintenance tracking done, binding/GPS pending)
- Essence Inventory & Validation (in progress)

### Database

- **ORM**: Eloquent
- **Migrations**: `database/migrations/`
- **Seeders**: `database/seeders/`
- **Factories**: `database/factories/`
- **Default Connection**: SQLite (see `.env.example`)
- Run migrations: `php artisan migrate`
- Rollback: `php artisan migrate:rollback`
- Fresh migration: `php artisan migrate:fresh`

### Queue System

- Default queue connection: `database`
- Queue worker: `php artisan queue:listen --tries=1`
- Jobs are automatically processed in development via `composer dev`

### Authentication

- Uses Laravel Sanctum for API token authentication
- Session-based auth for admin panel
- Custom middleware:
  - `EnsureIsAdmin`: Protects admin routes
  - `EnsureDevice`: Validates device authentication for API

### TypeScript Integration

- Path alias: `@/` maps to `resources/js/`
- Strict mode enabled
- Use `npm run types` to check for type errors
- Wayfinder auto-generates route types from Laravel routes

### Code Style

#### Frontend
- **Prettier**: 4 spaces, single quotes, 150 char line width
- **Import Organization**: Auto-organized via prettier-plugin-organize-imports
- **Tailwind**: Use `cn()` helper for conditional classes
- **React**: React 19 with automatic JSX runtime (no need to import React)

#### Backend
- **PHP**: Follow PSR-12 via Laravel Pint
- **Laravel**: Follow Laravel conventions and best practices
- **Naming**: Use Laravel naming conventions (e.g., `UserController`, `create_users_table`)

## Working with Features

### Adding a New Admin Feature
1. Create migration: `php artisan make:migration create_xyz_table`
2. Create model: `php artisan make:model Xyz`
3. Create controller: `php artisan make:controller Admin/XyzController`
4. Add routes in `routes/admin.php`
5. Run `npm run dev` to regenerate Wayfinder routes
6. Create page component in `resources/js/pages/Admin/Xyz/`
7. Update sidebar navigation in `resources/js/components/app-sidebar.tsx`

### Creating an API Endpoint
1. Create controller: `php artisan make:controller Api/XyzController`
2. Add routes in `routes/api.php` within `EnsureDevice` middleware group
3. Create API resource: `php artisan make:resource XyzResource`
4. Regenerate routes: `npm run dev`

### Working with the Rich Text Editor
- Uses Lexical editor in `resources/js/components/editor/`
- Outputs HTML content (stored in database)
- Used in News, Knowledge, and FAQ management

### E-commerce (Lunar)
- Lunar PHP provides product, variant, collection, and cart functionality
- Admin controllers: `ProductController`, `ProductVariantController`, etc.
- Collections organized via `CollectionGroup` model
- Pricing, inventory, and identifiers managed separately per product

## Debugging

- **Laravel Logs**: Use `php artisan pail` for real-time log viewing
- **Frontend Errors**: Check browser console and React Error Boundary
- **Queue Failures**: Check `failed_jobs` table or run `php artisan queue:failed`
- **Database Queries**: Enable query logging or use Laravel Debugbar (if installed)

## Environment Configuration

Key `.env` variables:
- `APP_ENV`: Set to `local` for development
- `APP_DEBUG`: Set to `true` for development
- `DB_CONNECTION`: Default is `sqlite`
- `QUEUE_CONNECTION`: Default is `database`
- `SESSION_DRIVER`: Default is `database`

### File Upload Limits

The application supports large file uploads (APK files up to 500MB, music files up to 1GB). To enable these uploads, you need to configure PHP settings:

#### Development (php.ini or .user.ini)
```ini
; Maximum upload file size
upload_max_filesize = 600M

; Maximum POST data size (should be larger than upload_max_filesize)
post_max_size = 650M

; Maximum execution time (increase for large uploads)
max_execution_time = 300

; Maximum input time
max_input_time = 300

; Memory limit
memory_limit = 512M
```

#### Production (Web Server Configuration)

**For Nginx:**
Add to your server block:
```nginx
client_max_body_size 650M;
```

**For Apache:**
Add to your `.htaccess` or VirtualHost configuration:
```apache
LimitRequestBody 681574400
```

**Laravel Validation:**
- APK files: Maximum 500MB (configured in `GeneralSettingUpdateRequest.php`)
- Music files: Maximum 1GB (configured in `MusicController.php`)
- Ensure server PHP limits are higher than Laravel validation limits to provide clear error messages

## Additional Notes

- **Server-Side Rendering (SSR)**: Supported via Inertia SSR - use `composer dev:ssr`
- **Database**: Uses SQLite by default; database file at `database/database.sqlite`
- **File Uploads**: Default filesystem disk is `local` (change in `config/filesystems.php`)
- **Scout Search**: Algolia integration available (configure in `.env`)
- **Deployment**: Uses Laravel Forge (see deployment badge in README)
