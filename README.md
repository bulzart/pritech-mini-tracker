# Mini Issue Tracker

A small Laravel app for managing **projects**, **issues**, **tags**, and
**comments** — with authentication, per-project ownership, user assignment, and
AJAX search and filtering.

## Run it

You need **PHP 8.3+**, **Composer**, and the **pdo_sqlite** PHP extension. No
database server and no Node.js required.

```bash
composer install
composer start
```

`composer start` sets everything up (creates `.env` and the app key, builds and
seeds the SQLite database, enables demo mode) and starts the server. When it
prints the URL, open it:

**http://127.0.0.1:8000**

The login form is prefilled in demo mode — just click **Sign in**. Every demo
account uses the password `password`:

- `owner@example.com` — owns the demo projects (can edit and delete them)
- `member@example.com`, `qa@example.com`, `frontend@example.com`,
  `backend@example.com` — members

## Test

```bash
php artisan test
```

127 tests run on an in-memory SQLite database — nothing to configure.

## What's inside

- **Projects & issues** — full CRUD, server-rendered Blade, Form Request validation.
- **Filtering & search** — combinable status / priority / tag filters and a
  debounced AJAX search across title and description, preserved across pagination.
- **Tags & comments** — tag attach/detach and paginated comments, both over AJAX
  with no page reload.
- **Authentication** — session login/logout with seeded demo users.
- **Ownership** — projects belong to a user; only the owner can edit or delete,
  enforced by a policy.
- **Assignment** — assign multiple members to an issue over AJAX.

The front end is progressive-enhancement JavaScript (no SPA framework) over
server-rendered Blade. See [`CHECKPOINT.md`](CHECKPOINT.md) for the detailed
status, route map, and JSON response shapes, and [`SECURITY.md`](SECURITY.md) for
the security baseline and disclosure policy.

## Code style

```bash
vendor/bin/pint --test
```

## License

Built on the [Laravel framework](https://laravel.com), open-source software
licensed under the [MIT license](https://opensource.org/licenses/MIT).
