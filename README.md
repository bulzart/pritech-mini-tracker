# Mini Issue Tracker

A small Laravel app for managing projects, issues, tags, and comments — with
authentication, per-project ownership, user assignment, and AJAX search and
filtering.

## Run it

Requires **PHP 8.3+**, **Composer**, and **pdo_sqlite** — no database server,
no Node.

```bash
composer install   # install PHP dependencies
composer start     # one command does the rest: creates .env + app key, builds
                   # & seeds the SQLite database, enables demo mode, and starts
                   # the server (php artisan serve) — nothing else to run
```

That's everything — no separate migrate, seed, or serve step. When `composer
start` prints the URL, open **http://127.0.0.1:8000** and click **Sign in**; the
demo login is prefilled. Every demo account uses the password `password`:

- `owner@example.com` — owns the demo projects
- `member@example.com`
- `qa@example.com`
- `frontend@example.com`
- `backend@example.com`

## Test

```bash
php artisan test
```

127 tests on in-memory SQLite — nothing to configure.

---

See [`CHECKPOINT.md`](CHECKPOINT.md) for the route map and JSON response shapes,
and [`SECURITY.md`](SECURITY.md) for the security baseline.
