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

(`composer start` does the rest — it creates `.env` + the app key, builds and
seeds the SQLite database, enables demo mode, and runs `php artisan serve`.
Nothing else to run: no separate migrate, seed, or serve step.)

When `composer start` prints the URL, open **http://127.0.0.1:8000** and click
**Sign in**; the demo login is prefilled. Every demo account uses the password
`password`:

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
