# Nebula Institute of Technology

Staff management system for student records, academic operations, payments, and campus clearances across Welisara, Moratuwa, and Peradeniya.

The application is a Laravel 12 / PHP 8.2 web app. Staff sign in with a username and password. Access is role-based, so each person only sees the menus and pages they are allowed to use.

Detailed notes live in [`docs/`](docs/).

## Stack

- PHP 8.2 and Laravel 12
- MySQL
- Blade, Bootstrap 5, Vite
- Excel import/export, PDF generation, QR codes for badges
- Intervention Image for profile photos

## What it covers

**Students.** Register students, keep extra details, search and export lists, open a full student profile (personal data, results, attendance, payments, certificates), track terminations and reinstatements.

**Academic.** Courses, intakes, modules, semesters, specializations, eligibility checks, course registration, course changes, UH index numbers, exam results, repeat students, attendance, and timetables.

**Payments.** Payment plans (including installment totals that must match course fees), discounts and SLT loan, student payments and slips, miscellaneous payments, late fees and approvals, payment summaries and analytics, payment clearance.

**Clearances.** Library, hostel, project, and payment clearance, plus an all-clearance view for administrators.

**Approvals and badges.** Special approval for students who do not meet standard entry rules. Course completion badges with a public verification link (`/verify-badge/{code}`).

**Staff and system.** User management, profile and settings, role-based dashboards, audit log (Developer), scheduled pruning of old audit rows.

A public spreadsheet attendance page is also available at `/spreadsheet` (no login).

## Roles

Users can hold more than one role. Menus and routes are gated through `RoleHelper` and the `role` middleware.

| Role | Typical work |
| --- | --- |
| Developer | Full access, including audit log and diagnostics |
| DGM | Dashboards, special approvals, student overview, termination tracking |
| Program Administrator (level 01) | User management, academic setup, students, clearances, reporting |
| Program Administrator (level 02) | Academic operations, registrations, results, attendance, timetable |
| Program Administrator (level 02) Trainee | Limited L2 dashboard and academic views |
| Student Counselor | Registration, eligibility, payments |
| Student Counselor Trainee | Limited counselor dashboard and related views |
| Marketing Manager | Marketing dashboard, payment plans, badges |
| Bursar | Payments, discounts, late fees, bursar dashboard |
| Project Tutor | Project clearance and related attendance |
| Librarian | Library clearance |
| Hostel Manager | Hostel clearance |

See [`docs/ROLE_BASED_ACCESS_CONTROL.md`](docs/ROLE_BASED_ACCESS_CONTROL.md) for older permission notes. Route-level access in `routes/web.php` is the source of truth.

## Setup

Requirements: PHP 8.2+, Composer, Node.js, and MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set these in `.env`:

```
APP_NAME="Nebula Institute of Technology"
APP_URL=http://localhost:8189
DB_DATABASE=nebula
DB_USERNAME=root
DB_PASSWORD=

AUDIT_LOGGING=true
AUDIT_LOG_RETENTION_DAYS=7
```

Then:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install
```

Local development (Vite on port 9283, Laravel on 8189):

```bash
npm run dev
```

Production front-end build:

```bash
npm run build
```

Tests:

```bash
php artisan test
```

## Audit log retention

Developers can browse `/audit-log`. New rows can be turned off with `AUDIT_LOGGING=false`.

Old rows are deleted by `php artisan audit:prune`, scheduled daily at 01:15. Retention is `AUDIT_LOG_RETENTION_DAYS` in `.env` (default 7). After changing `.env`, run `php artisan config:clear`.

One-off run, for example keep only 3 days:

```bash
php artisan audit:prune --days=3
```

On the server, run the Laravel scheduler every minute:

```
* * * * * cd /path/to/Nebula && php artisan schedule:run >> /dev/null 2>&1
```

## Campuses

Staff locations are stored as:

- Nebula Institute of Technology – Welisara
- Nebula Institute of Technology – Moratuwa
- Nebula Institute of Technology – Peradeniya
