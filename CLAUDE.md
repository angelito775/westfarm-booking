# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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

### Payment Statuses
Unpaid, Partial, Paid — stored in `payment_statuses` table. Business rules enforce:
- Completed bookings must be Paid
- Pending/Cancelled bookings cannot be Paid
- Full payment auto-confirms a Pending booking

## Directory Structure

| Directory | Purpose |
|---|---|
| `public/` | Public marketing pages (home, about, westpool, westcrays, events, faqs, playground, booking) — no auth required |
| `pages/` | Auth pages (login, register) |
| `admin/` | Admin panel (dashboard, users, categories, payments, payment methods) |
| `owner/` | Owner portal (dashboard, bookings, facilities, gallery, ledger, reviews, crayfish orders) |
| `customer/` | Customer section (profile, payment/booking management) — requires `user_type_id == 2` |
| `logic/` | PHP backend processors (auth, registration, CRUD handlers, AJAX endpoints) |
| `config/` | Database connection |
| `assets/css/` | Stylesheets (one per section: login, register, dashboard, customer, public pages) |
| `assets/js/` | Frontend JS for public pages |
| `assets/images/` | Static images (logo, facility photos, etc.) |
| `uploads/facilities/` | Uploaded facility images |
| `camera360/` | 360° camera viewer page |

## Architecture Pattern

- **No MVC framework** — each page is a standalone PHP file that queries the database directly
- Authentication is session-based (`$_SESSION['user_id']`, `$_SESSION['user_type_id']`)
- Every protected page checks `user_type_id` at the top and redirects to login if unauthorized
- Form actions point to `logic/*_process.php` files which handle POST, then redirect back with `?success=` or `?error=` query params
- Error/success messages are displayed by checking `$_GET['error']` / `$_GET['success']` in the HTML
- The owner sidebar is shared via `owner/sidebar.php` using a `$ownerNavActive` variable set before inclusion
- Admin pages use an inline sidebar (not a shared include) with the same visual design as the owner sidebar

### Owner Sidebar Navigation Pattern
Owner pages set `$ownerNavActive` to one of these string keys before including `sidebar.php`:
- `business-overview` → `index.php`
- `bookings-reservations` → `bookings.php`
- `facilities-rooms` → `facilities.php`
- `gallery-management` → `gallery.php`
- `income-ledger` → `ledger.php`
- `crayfish-orders` → `crayfish_orders.php`
- `guest-reviews` → `reviews.php`

### Logout Pattern
- Admin/Owner logout: `logic/logout.php` → redirects to `pages/login.php`
- Customer logout: `logic/logout_customer.php` → redirects to `public/index.php`
- All logout flows use `session_unset()` + `session_destroy()`

## Key Conventions

- All DB queries use prepared statements (PDO)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Registration inserts into both `users` and `user_profiles` within a transaction
- Facility CRUD in `logic/facility_process.php` dynamically inspects table columns via `SHOW COLUMNS` to handle schema variations (e.g., `price` vs `base_price`, `status` vs `is_available` vs `is_active`)
- Gallery/image and review tables are detected dynamically by trying candidate table names (e.g., `facility_images`, `gallery_images`, `gallery`)
- File uploads go to `uploads/facilities/` with `uniqid('facility_')` filenames, 5MB limit, JPEG/PNG/GIF only
- Manual bookings created by the owner auto-create customer accounts with placeholder emails (`guest_<uniqid>@westfarm.local`)
- Booking status → payment status linkage is enforced both server-side (in process files) and client-side (JS disables invalid combinations)
- The `get_available_units.php` AJAX endpoint returns JSON for the owner's manual booking form, filtering by category, date availability, and same-day booking restrictions (Cottage/Pool/Event Hall only)

## Page-Specific Notes

### Admin Pages
- **Dashboard** (`admin/index.php`): Shows KPI cards, revenue chart (Chart.js line), booking status doughnut chart, recent bookings table. Uses Chart.js 4.4.0 CDN.
- **Users** (`admin/users.php`): Full CRUD with add/edit/delete modals. Prevents self-deletion and self-demotion. Live search filtering.
- **Categories** (`admin/categories.php`): Simple CRUD for facility categories. Prevents deletion if facilities are linked (FK constraint).
- **Payments** (`admin/payments.php`): Read-only ledger of all payment transactions. Allows editing transaction IDs and deleting records.
- **Payment Methods** (`admin/payment_methods.php`): CRUD for payment methods (GCash, Maya, Cash, etc.) with active/disabled toggle.

### Owner Pages
- **Dashboard** (`owner/index.php`): Business overview with KPIs, revenue chart, bookings-by-facility chart, recent + upcoming bookings.
- **Bookings** (`owner/bookings.php`): Lists all bookings for owner's facilities. Edit modal updates guest name, phone, booking status, payment status. Delete removes booking + payment records.
- **Facilities** (`owner/facilities.php`): Card-grid display of facilities with add/edit/delete. Includes a full manual booking modal with category → unit → date → price cascade via AJAX.
- **Gallery** (`owner/gallery.php`): Dynamically detects gallery table. Upload/edit/delete photos with facility linking. Image preview modal.
- **Ledger** (`owner/income-ledger.php`): Financial summary with payment records, method/status breakdowns, monthly revenue.
- **Reviews** (`owner/reviews.php`): Dynamically detects review table. Displays guest reviews.
- **Crayfish Orders** (`owner/crayfish_orders.php`): Manages crayfish order statuses (pending → confirmed → delivered/cancelled).

### Customer Pages
- **Profile** (`customer/profile.php`): View/edit personal info, change password, booking summary stats.
- **Payments** (`customer/payment_booking.php`): Lists all customer bookings with payment status. Payment form with method selection and amount input. Processes payments via `logic/payment_process.php`.

### Public Pages
- **Booking** (`public/booking.php`): Public booking page (requires customer login to actually book).
- All public pages use `public_nav.css` and `public_nav.js` for the navigation bar with dropdown menus.

## Logic/Processor Files

| File | Purpose |
|---|---|
| `auth_process.php` | Login — validates email/password, sets session, redirects by role |
| `register_process.php` | Registration — validates, checks email/phone uniqueness, inserts user + profile in transaction |
| `logout.php` | Admin/Owner logout → login page |
| `logout_customer.php` | Customer logout → public home |
| `user_process.php` | Admin user CRUD (add/edit/delete) |
| `category_process.php` | Admin category CRUD |
| `payment_method_process.php` | Admin payment method CRUD |
| `payment_process.php` | Customer payment processing + admin payment record edits/deletes |
| `facility_process.php` | Owner facility CRUD with dynamic column handling and image gallery management |
| `booking_process.php` | Owner manual booking CRUD (add/update/delete) with customer auto-creation |
| `gallery_process.php` | Owner gallery photo upload/edit/delete |
| `get_available_units.php` | AJAX endpoint — returns available facilities for given category + dates (JSON) |
| `get_booked_dates.php` | AJAX endpoint — returns booked dates for a facility (JSON) |
| `get_public_facilities.php` | AJAX endpoint — returns facilities for public booking page (JSON) |
| `westcrays_process.php` | Processor for crayfish ordering |
| `ajax_auth_process.php` | AJAX login endpoint |
| `ajax_register_process.php` | AJAX registration endpoint |
| `customer_booking_process.php` | Customer booking processor |

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

- **New admin page**: Create file in `admin/`, add session guard checking `user_type_id == 1`, query DB directly, use `header("Location: ...")` for redirects. Copy the sidebar markup from an existing admin page.
- **New owner page**: Create file in `owner/`, add session guard checking `user_type_id == 3`, set `$ownerNavActive` to a unique key, include `sidebar.php`. Add the nav item to `owner/sidebar.php`.
- **New form processor**: Create file in `logic/`, check session + POST method, validate input, use prepared statements, redirect with status query param.
- **New public page**: Create file in `public/`, no auth needed, use shared nav pattern from `public/index.php` (include `public_nav.css` and `public_nav.js`).
- **New customer page**: Create file in `customer/`, add session guard checking `user_type_id == 2`, use the customer nav pattern (nav + footer inline, not shared include).
