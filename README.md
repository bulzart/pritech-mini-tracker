## Run it

Requires **PHP 8.3+**, **Composer**, and **pdo_sqlite** — no database server,
no Node.
```bash
1. `composer install`
2. `composer start` (creates the SQLite database, runs `php artisan db:seed`, and finally runs `php artisan serve` in the background)
```

When the server prints the URL, open **http://127.0.0.1:8000** and click
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
