# Mini Issue Tracker

A small Laravel app for managing projects, issues, tags, and comments — with
authentication, per-project ownership, user assignment, and AJAX search and
filtering.

## Run it

Requires **PHP 8.3+**, **Composer**, and **pdo_sqlite** — no database server,
no Node.

```bash
composer install
composer start
```

Open **http://127.0.0.1:8000** and click **Sign in** — the demo login is
prefilled. Every demo account uses the password `password`; `owner@example.com`
owns the projects, and `member@`, `qa@`, `frontend@`, `backend@example.com` are
members.

## Test

```bash
php artisan test
```

127 tests on in-memory SQLite — nothing to configure.

---

See [`CHECKPOINT.md`](CHECKPOINT.md) for the route map and JSON response shapes,
and [`SECURITY.md`](SECURITY.md) for the security baseline.
