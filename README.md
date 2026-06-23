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
- **Authentication** — session login/logout with seeded demo users; the login
  form is prefilled in demo mode for one-click review.
- **Project ownership** — projects belong to a user; only the owner can edit or
  delete a project, enforced by a policy (`@can`-gated buttons + a 403 on direct
  access).
- **User assignment** — assign multiple members to an issue (a second
  `issue_user` pivot), attach/detach over AJAX on the issue detail page.
- **Issue search** — debounced AJAX search across title and description that
  combines with the status / priority / tag filters and preserves pagination.

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
> at `public/css/app.css` and small scripts under `public/js/` (delete-confirm,
> the AJAX tag / comment / assignment managers, and debounced search). No `npm`
> build step is required to run or demo the app. (The default Vite/Tailwind
> pipeline ships with Laravel but is not used by these views.)

## Quick start

For a reviewer — three commands, then sign in with the prefilled demo
credentials:

```bash
composer install      # install PHP dependencies
composer setup        # = php artisan app:install: creates .env + app key, the
                      #   SQLite database, seeds demo data, and enables demo mode
php artisan serve     # open the printed URL — http://127.0.0.1:8000
```

Or, after `composer install`, do setup and serve in one command:

```bash
composer start        # runs app:install, then php artisan serve
```

On the login page the **Demo Owner** credentials are prefilled in demo mode —
just click **Sign in**. Demo accounts (every password is `password`):
`owner@example.com` (owns the demo projects), `member@example.com`,
`qa@example.com`, `frontend@example.com`, `backend@example.com`.

> In demo mode the app self-heals: the first request after `php artisan serve`
> creates and seeds the database automatically if it is missing, so there is no
> separate migrate/seed step.

### Manual setup (equivalent, for development)

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve   # then open http://127.0.0.1:8000 (redirects to /projects)
```

## Test

```bash
php artisan test
```

**127 tests** use an in-memory SQLite database (configured in `phpunit.xml`) and
cover the data layer (relationships, cascades, uniqueness, scopes, casts) and
the HTTP layer (authentication, project ownership + policy, issue / tag /
comment CRUD, filters, AJAX tag / assignment / comments, and search).

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
