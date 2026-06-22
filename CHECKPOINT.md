# Checkpoint 1 — Foundation, Data Layer & Project CRUD

Status: **complete and working**. The application boots, seeds demo data, and
serves full Project CRUD in the browser with no console errors.

## Environment

| Item             | Value                                                              |
| ---------------- | ----------------------------------------------------------------- |
| Laravel          | **13.16.1** (Laravel 13, as requested — no downgrade needed)      |
| PHP              | 8.4.7                                                             |
| Composer         | 2.8.8                                                             |
| Database (dev)   | SQLite — `database/database.sqlite`                               |
| Database (tests) | SQLite `:memory:` (configured in `phpunit.xml`)                  |
| Test framework   | PHPUnit 12 (the project's configured framework; Pest not installed) |
| Front-end build  | None required — static `public/css/app.css` + `public/js/app.js` (Vite/Tailwind ship with Laravel but are unused; Node 18 here cannot run the bundled Vite 8) |

Laravel 13 was available in the environment, so it is used directly.

## Migrations created (`database/migrations/`)

1. `2026_06_22_100001_create_projects_table` — `name`, `description` (nullable), timestamps.
2. `2026_06_22_100002_add_dates_to_projects_table` — adds `start_date` and `deadline` (nullable dates) **in a separate, additive migration**, as required.
3. `2026_06_22_100003_create_issues_table` — `project_id` (FK, cascade), `title`, `description`, `status` (default `open`), `priority` (default `medium`), `due_date` (nullable). Indexes on `project_id`, `status`, `priority`, `due_date`.
4. `2026_06_22_100004_create_tags_table` — `name` (unique), `color` (nullable). The unique index serves name lookups.
5. `2026_06_22_100005_create_comments_table` — `issue_id` (FK, cascade), `author_name`, `body`. Index on `issue_id`.
6. `2026_06_22_100006_create_issue_tag_table` — pivot with `issue_id` + `tag_id` (both FK, cascade), `unique(issue_id, tag_id)`, index on `tag_id`. No surrogate id, no timestamps (no pivot state to store).

**Cascade behaviour (DB-enforced via foreign keys):** deleting a project deletes
its issues; deleting an issue deletes its comments and pivot rows; deleting a tag
deletes its pivot rows. Verified by tests.

**Indexes:** `issues.project_id`, `issues.status`, `issues.priority`,
`issues.due_date`, `tags.name` (unique), `issue_tag(issue_id, tag_id)` (unique,
also serves `issue_id` via its left-most prefix), `issue_tag.tag_id`.

## Models created (`app/Models/`)

- `Project` — fillable `name, description, start_date, deadline`; `start_date`/`deadline` cast to `date`; `issues()` hasMany.
- `Issue` — fillable `project_id, title, description, status, priority, due_date`; `due_date` cast to `date`; `STATUSES = [open, in_progress, closed]`, `PRIORITIES = [low, medium, high]`; relationships `project()`, `comments()`, `tags()`; scopes `status()`, `priority()`, `tag()` (each ignores null/empty input).
- `Tag` — fillable `name, color`; `issues()` belongsToMany.
- `Comment` — fillable `issue_id, author_name, body`; `issue()` belongsTo.

Models follow the project's own idiom (PHP `#[Fillable]` attribute + `casts()` method, matching `App\Models\User`).

## Relationships implemented

- Project → has many Issues
- Issue → belongs to Project; has many Comments; belongs to many Tags (`issue_tag`)
- Tag → belongs to many Issues (`issue_tag`)
- Comment → belongs to Issue

## Factories & seeders

- Factories: `ProjectFactory`, `IssueFactory`, `TagFactory`, `CommentFactory` — realistic, non-filler demo data; nullable fields are sometimes null; issue status/priority only ever use the allowed values; deadlines are always on/after the start date.
- `DatabaseSeeder` seeds (idempotent under `migrate:fresh --seed`):
  - 6 named projects (a couple with null start/deadline to exercise nullables)
  - ~50 issues (7–10 per project) with mixed statuses and priorities
  - 8 shared tags (backend, frontend, bug, feature, urgent, design, QA, documentation)
  - ~200+ comments (3–6 per issue)
  - random tag attachments per issue
- Verified counts (latest seed): 6 projects, 51 issues, 8 tags, 231 comments — all above the required minimums (≥5 / ≥40 / ≥8 / ≥100).

## Project routes

```
GET    /                       → redirect to projects.index
GET    /projects               projects.index
GET    /projects/create        projects.create
POST   /projects               projects.store
GET    /projects/{project}     projects.show
GET    /projects/{project}/edit projects.edit
PUT/PATCH /projects/{project}  projects.update
DELETE /projects/{project}     projects.destroy
```

Defined with `Route::resource('projects', ProjectController::class)` and implicit route-model binding.

## Project CRUD status

| Capability | Status |
| ---------- | ------ |
| List (with issue counts, paginated, `withCount` to avoid N+1) | ✅ |
| Create (Form Request validated) | ✅ |
| Show (issues eager-loaded; issue titles plain text — no link to a not-yet-existing route) | ✅ |
| Edit / Update (Form Request validated) | ✅ |
| Delete (with JS confirm; cascade) | ✅ |
| Flash messages on create/update/delete | ✅ |
| Inline validation errors + old input | ✅ |
| Accessible labels, landmarks, skip link, focus styles, empty states | ✅ |

Validation lives in `StoreProjectRequest` / `UpdateProjectRequest`
(`name` required/string/max:255; `description` nullable; `start_date` nullable date;
`deadline` nullable date, `after_or_equal:start_date` only when a start date is present).

## Tests run

`php artisan test` → **33 passed (71 assertions)**, ~0.7s.

- Data layer (18): has-many/belongs-to/belongs-to-many in both directions; tag name uniqueness; pivot duplicate-pair rejection; four cascade deletes; status/priority/tag scopes (including null/empty no-ops); `start_date`/`deadline`/`due_date` date casts.
- Project feature (15): `/` redirect; index 200 + lists projects; create page 200; valid create; missing-name error; deadline-before-start fails; deadline-equal-to-start passes; nullable dates accepted; show 200 + lists related issues; edit 200; update; delete; deleted row absent.

`vendor/bin/pint --test` → **passed** (app, database, routes, tests, config).

## Playwright MCP verification (real browser, dev server)

All 14 steps passed against `php artisan serve`:

1–4. Loaded `/projects`; all 6 seeded projects render (names, descriptions, dates, issue counts, actions); **0 console errors / 0 warnings** across the whole session.
5–6. Created a valid project via the form → redirected to its show page.
7–8. Show page displays description, start date, deadline, issue count, and the empty-issues state.
9–10. Edited the project (name + deadline) → updated values shown, "Project updated." flash.
11–12. Submitted invalid data (empty name + deadline before start) → both inline field errors shown (`aria-invalid`, `role="alert"`), old input preserved.
13–14. Deleted the project (JS confirm dialog with the project name) → removed from the list, "Project deleted." flash.

## API / HTTP verification (curl against the dev server)

| Request | Result |
| ------- | ------ |
| `GET /` | 302 → `/projects` |
| `GET /projects` | 200 |
| `GET /projects/create` | 200 |
| `GET /projects/{id}` | 200 (404 for a missing id) |
| `GET /projects/{id}/edit` | 200 |
| `POST /projects` (valid, with CSRF) | 302 → show; row created |
| `POST /projects` (invalid) | 302 back; no row created |
| `PATCH /projects/{id}` | 302 |
| `DELETE /projects/{id}` | 302; row gone (404 afterwards) |
| `POST /projects` (no CSRF token) | 419 (CSRF protection confirmed) |

Security response headers confirmed present on web responses: `Content-Security-Policy`
(strict `'self'`), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy`, `Permissions-Policy`. `X-Powered-By` and `Server` are suppressed.

## Demo readiness

- `.env.example` is SQLite-first and comment-documented; no real secrets committed.
- `php artisan migrate:fresh --seed` produces an immediately understandable data set.
- No destructive logic runs on normal web requests or app boot; the database is
  only reset by the explicit `migrate:fresh` command a developer runs.
- README documents prerequisites (with versions), setup, run, and test commands.

## Known limitations (by design for this checkpoint)

- No Issue CRUD UI, no Tags UI, no AJAX tag attach/detach, no AJAX comments, no
  AJAX search — deferred to later checkpoints. (The CSRF meta tag is already in
  the layout for that future AJAX.)
- No authentication or authorization. Form Requests `authorize()` returns `true`;
  access control is a documented follow-up. A `Test User` is seeded for when auth
  is added.
- Pagination is a simple prev/next control (10 projects per page).
- HSTS is not emitted by the app (only meaningful over HTTPS); add it at the
  TLS-terminating proxy in production.
- The bundled Vite/Tailwind pipeline is unused; styling is a static stylesheet so
  the app runs with no Node build step.

## Next checkpoint notes

- Issue CRUD (controller, Form Requests, views) and link issue titles on the
  project show page to the new issue show route.
- Tags management UI and AJAX attach/detach against the existing pivot.
- AJAX comments on the issue show page.
- Optional: issue list filtering using the existing `status`/`priority`/`tag`
  scopes; optional user assignment; optional authorization with policies.
