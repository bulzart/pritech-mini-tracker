# Mini Issue Tracker

A small Laravel application for a team to manage **projects**, **issues**, **tags**, and **comments**.

This repository is built in checkpoints. It currently delivers the database
schema, Eloquent models and relationships, demo data, and:

- **Project CRUD** — server-rendered Blade views with Form Request validation.
- **Issue CRUD** — with status / priority / tag filtering (combinable, preserved
  across pagination) and a detail page.
- **Tags** — listing with per-tag issue counts, and creation with unique-name
  validation.
- **AJAX tag attach/detach** on the issue detail page — `fetch()`, JSON, no
  full-page reload.
- **AJAX comments** — paginated loading and creation on the issue detail page,
  with inline validation; new comments are prepended without a reload.

The front end is progressive-enhancement JavaScript (no SPA framework) over
server-rendered Blade. See [`CHECKPOINT.md`](CHECKPOINT.md) for the detailed
status, route map, JSON response shapes, and what is intentionally not built yet.

## Domain model

| Entity   | Key fields                                             | Relationships                                   |
| -------- | ------------------------------------------------------ | ----------------------------------------------- |
| Project  | name, description, start_date, deadline                | has many Issues                                 |
| Issue    | project_id, title, description, status, priority, due_date | belongs to Project; has many Comments; belongs to many Tags |
| Tag      | name (unique), color                                   | belongs to many Issues                          |
| Comment  | issue_id, author_name, body                            | belongs to Issue                                |

`Issue` ↔ `Tag` is a many-to-many through the `issue_tag` pivot table.
Deleting a project cascades to its issues; deleting an issue cascades to its
comments and pivot rows; deleting a tag removes its pivot rows.

## Prerequisites

| Tool     | Version used | Notes                                          |
| -------- | ------------ | ---------------------------------------------- |
| PHP      | 8.4.7        | `^8.3` required by `composer.json`             |
| Composer | 2.8.8        |                                                |
| SQLite   | 3.x          | via the `pdo_sqlite` PHP extension             |
| Node.js  | optional     | only for the Vite asset pipeline (not required — see below) |

> **Front-end assets:** the UI is styled by a self-contained static stylesheet
> at `public/css/app.css` and a small script at `public/js/app.js`. No `npm`
> build step is required to run or demo the app. (The default Vite/Tailwind
> pipeline ships with Laravel but is not used by these views.)

## Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your environment file
cp .env.example .env

# 3. Generate the application key
php artisan key:generate

# 4. Create the SQLite database file (skip if it already exists)
touch database/database.sqlite

# 5. Run migrations and seed demo data
php artisan migrate:fresh --seed
```

## Run

```bash
php artisan serve
# then open http://127.0.0.1:8000  (redirects to /projects)
```

## Test

```bash
php artisan test
```

The suite uses an in-memory SQLite database (configured in `phpunit.xml`) and
covers the data layer (relationships, cascades, uniqueness, scopes, casts) and
the Project HTTP layer (routing, validation, CRUD).

## Code style

```bash
vendor/bin/pint           # format
vendor/bin/pint --test    # check formatting in CI
```

## Security

Baseline HTTP security headers (CSP, `X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy`, `Permissions-Policy`) are applied to every web response, and
`X-Powered-By`/`Server` are suppressed. See [`SECURITY.md`](SECURITY.md) for the
responsible-disclosure policy.

## License

Built on the [Laravel framework](https://laravel.com), which is open-sourced
software licensed under the [MIT license](https://opensource.org/licenses/MIT).
