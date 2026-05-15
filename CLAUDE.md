# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working on code in this repository.

## Project Overview

**West Farm Resort and Hotel** — a PHP booking management system for a resort in Basista, Pangasinan, Philippines. It has three user-facing sections: a public marketing website, an admin panel, and an owner portal.

**Tech stack:** Plain PHP (no framework), MySQL via PDO, no build step, no package manager. Runs on Laragon (Windows local dev). Frontend uses vanilla CSS and JS with Font Awesome icons and Google Fonts (Dancing Script, Josefin Sans, Lora).

## Database

- MySQL database name: `westfarm`
- Connection config: `config/db_connection.php` (PDO, `root` / no password)
- Schema file: `westfarm (3).sql` — contains full CREATE TABLE + seed data
- Key tables: `users`, `user_profiles`, `user_types`, `user_status`, `facilities`, `facility_images`, `categories`, `bookings`, `booking_items`, `booking_statuses`, `payments`, `payment_methods`, `payment_statuses`

### User Roles (user_type_id)
1. **Admin** — full system access → redirected to `admin/index.php`
2. **Customer** — can browse/make bookings → redirected to `pages/index.php`
3. **Owner** — manages facilities, bookings, gallery → redirected to `owner/index.php`

### Booking Statuses
1. Pending → 2. Confirmed → 4. Completed (or 3. Cancelled)

## Directory Structure

| Directory | Purpose |
|---|---|
| `public/` | Public marketing pages (home, about, westpool, westcrays) — no auth required |
| `pages/` | Auth pages (login, register) |
| `admin/` | Admin panel (dashboard, users, categories, payments, payment methods) |
| `owner/` | Owner portal (dashboard, bookings, facilities, gallery, ledger, reviews) |
| `logic/` | PHP backend processors (auth, registration, CRUD handlers) |
| `config/` | Database connection |
| `assets/css/` | Stylesheets (one per section: login, register, dashboard, public pages) |
| `assets/js/` | Frontend JS for public pages |
| `assets/images/` | Static images (logo, facility photos, etc.) |
| `uploads/facilities/` | Uploaded facility images |

## Architecture Pattern

- **No MVC framework** — each page is a standalone PHP file that queries the database directly
- Authentication is session-based (`$_SESSION['user_id']`, `$_SESSION['user_type_id']`)
- Every protected page checks `user_type_id` at the top and redirects to login if unauthorized
- Form actions point to `logic/*_process.php` files which handle POST, then redirect back with `?success=` or `?error=` query params
- Error/success messages are displayed by checking `$_GET['error']` / `$_GET['success']` in the HTML
- The owner sidebar is shared via `owner/sidebar.php` using a `$ownerNavActive` variable set before inclusion

## Key Conventions

- All DB queries use prepared statements (PDO)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Registration inserts into both `users` and `user_profiles` within a transaction
- Facility CRUD in `logic/facility_process.php` dynamically inspects table columns via `SHOW COLUMNS` to handle schema variations
- Gallery/image tables are detected dynamically by trying candidate table names
- File uploads go to `uploads/facilities/` with `uniqid('facility_')` filenames, 5MB limit, JPEG/PNG/GIF only

## Development

This project runs on **Laragon** on Windows. There are no build tools, no Composer, no npm, no tests, and no linter. To work on it:

1. Start Laragon (Apache + MySQL)
2. Ensure the `westfarm` database is imported from the SQL dump
3. Access the site at `http://localhost/westfarm-booking/`
4. Edit files directly — changes are reflected on page reload

Default seed accounts (all passwords are `password`):
- Admin: `admin@westfarm.com`
- Owner: `raymundvalerios@gmail.com`
- Customer: `abiandilla2015@gmail.com`

## Common Patterns When Adding Features

- **New admin page**: Create file in `admin/`, add session guard checking `user_type_id == 1`, query DB directly, use `header("Location: ...")` for redirects
- **New owner page**: Create file in `owner/`, add session guard checking `user_type_id == 3`, set `$ownerNavActive`, include `sidebar.php`
- **New form processor**: Create file in `logic/`, check session + POST method, validate input, use prepared statements, redirect with status query param
- **New public page**: Create file in `public/`, no auth needed, use shared nav pattern from `public/index.php`
