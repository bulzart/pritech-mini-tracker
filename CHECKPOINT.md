# Checkpoint 1 — Foundation, Data Layer & Project CRUD

Status: **complete and working**. The application boots, seeds demo data, and
serves full Project CRUD in the browser with no console errors.

> **Checkpoint 3 is now complete** — authentication, project ownership with
> policies, many-to-many user assignment on issues (AJAX), debounced AJAX
> search, demo-mode login prefill, and a self-provisioning setup so the app
> runs from a fresh checkout with no manual database steps. See
> **[Checkpoint 3](#checkpoint-3--authentication-ownership-assignment--search)**
> at the end of this document. The earlier "known limitations / next checkpoint"
> notes are retained as a historical record; the latest checkpoint supersedes
> them.

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

---

# Checkpoint 2 — Issues, Filters, Tags, AJAX Tags & Comments

Status: **complete and working**. All required core assignment features are now
implemented: Issue CRUD, filtering, Tag listing/creation, AJAX tag
attach/detach, and AJAX paginated comments. `php artisan test` is green,
Playwright MCP and HTTP verification pass, and the browser console is clean.

## Routes added

### Issues — full resource (`Route::resource('issues', IssueController::class)`)

```
GET    /issues                 issues.index    (filterable + paginated)
GET    /issues/create          issues.create   (?project_id preselects a project)
POST   /issues                 issues.store
GET    /issues/{issue}         issues.show
GET    /issues/{issue}/edit    issues.edit
PUT/PATCH /issues/{issue}      issues.update
DELETE /issues/{issue}         issues.destroy
```

### Tags — list & create only (`->only(['index', 'create', 'store'])`)

```
GET    /tags                   tags.index      (withCount('issues'), paginated)
GET    /tags/create            tags.create
POST   /tags                   tags.store
```

### AJAX endpoints (JSON, consumed by fetch() with no full-page reload)

```
POST   /issues/{issue}/tags/{tag}     issues.tags.attach     (idempotent)
DELETE /issues/{issue}/tags/{tag}     issues.tags.detach     (idempotent)
GET    /issues/{issue}/comments       issues.comments.index  (paginated, newest first)
POST   /issues/{issue}/comments       issues.comments.store  (201 on success)
```

All use implicit route-model binding, so an unknown `{issue}` or `{tag}`
returns a 404 automatically.

## JSON response shapes

**Tag attach** — `POST /issues/{issue}/tags/{tag}` → `200`

```json
{ "attached": true, "tag": { "id": 1, "name": "backend", "color": "#2563eb" }, "message": "Tag attached." }
```

**Tag detach** — `DELETE /issues/{issue}/tags/{tag}` → `200`

```json
{ "attached": false, "tag": { "id": 1, "name": "backend", "color": "#2563eb" }, "message": "Tag detached." }
```

Both are idempotent: re-attaching never creates a duplicate pivot row
(`syncWithoutDetaching` + the unique `issue_tag(issue_id, tag_id)` index), and
detaching a tag that is not attached is a clean no-op.

**Comments list** — `GET /issues/{issue}/comments?page=N` → `200` (Laravel
resource collection of `CommentResource` over a paginator):

```json
{
  "data": [
    { "id": 1, "author_name": "Grace Hopper", "body": "…", "created_at": "2026-06-22T18:00:00+00:00", "created_at_for_humans": "5 minutes ago" }
  ],
  "links": { "first": "…", "last": "…", "prev": null, "next": "…" },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 5, "total": 12, "from": 1, "to": 5, "path": "…" }
}
```

**Comment create** — `POST /issues/{issue}/comments` → `201`

```json
{ "data": { "id": 248, "author_name": "Grace Hopper", "body": "…", "created_at": "…", "created_at_for_humans": "0 seconds ago" } }
```

**Comment validation error** → `422` (Laravel standard JSON):

```json
{ "message": "The name field is required. (and 1 more error)", "errors": { "author_name": ["The name field is required."], "body": ["The body field is required."] } }
```

## Filters implemented

- `?status=open|in_progress|closed`, `?priority=low|medium|high`, `?tag={name}`,
  driven by the existing `Issue::scopeStatus/scopePriority/scopeTag`.
- Filters work independently and in any combination.
- Empty filters (`value=""`) show all issues; an invalid value (e.g.
  `?status=banana`) returns an empty result set without crashing.
- The tag filter matches by tag **name** via `whereHas`.
- Selected filters stay selected in the filter bar (a "Clear" link appears when
  any filter is active).

## Pagination behaviour

- Issues index: **15 per page**, query string preserved across pages
  (`->withQueryString()`), so the Next/Previous links keep the active filters.
- Tags index: **20 per page**.
- Comments: **5 per page**, paginated through the AJAX endpoint (never all at
  once). The Previous/Next controls fetch the adjacent page in place. The first
  seeded issue is given 12 comments so 3 pages are demonstrable immediately.

## Eager loading / N+1 avoidance

- Issue index: `with(['project', 'tags'])`.
- Issue show: `load(['project', 'tags'])`; comments load through the paginated
  endpoint, not eagerly.
- Tag index: `withCount('issues')`.

## JavaScript files & Blade partials added

JavaScript (static, same-origin, CSP-safe — `script-src 'self'`, no inline
handlers, no `console.log`):

- `public/js/app.js` — extended: delete-confirm (existing) + tag colour dots
  painted via the **CSSOM** (`element.style`, which is not subject to the
  `style-src` CSP directive, so the strict policy holds with no console errors).
- `public/js/issue-show.js` — new: AJAX tag attach/detach and the paginated
  comments thread + comment creation. Comment author/body are rendered with
  `textContent` (never `innerHTML`) so stored values cannot inject markup
  (OWASP A03/XSS). Disables buttons while a request is in flight and guards
  against duplicate submits. A client-side required-field check shows inline
  errors for empty input without a needless 422 round-trip; the server remains
  the source of truth.

Blade partials/views added:

- `issues/{index,create,edit,show,_form,_filters,_status_badge,_priority_badge,_tag_item}.blade.php`
- `tags/{index,create,_form}.blade.php`
- `partials/tag-chip.blade.php`
- Layout nav extended to **Projects · Issues · Tags**; a `@stack('scripts')`
  hook loads the page-specific `issue-show.js`.
- `projects/show.blade.php` updated: issue titles now link to the issue detail
  page, and a "New issue" link carries `?project_id={id}` to preselect the
  project.

## Forms & validation (Form Request classes)

- `StoreIssueRequest` / `UpdateIssueRequest`: `project_id` required + `exists`,
  `title` required/string/max:255, `description` nullable, `status`/`priority`
  constrained to `Issue::STATUSES` / `Issue::PRIORITIES`, `due_date` nullable date.
- `StoreTagRequest`: `name` required/string/max:255/unique, `color` nullable/max:50.
- `StoreCommentRequest`: `author_name` required/max:255, `body` required/max:5000.

## Schema change

- Added migration `2026_06_22_100007_make_issues_description_nullable` — the
  Checkpoint 1 schema declared `issues.description` NOT NULL, which conflicted
  with the new "description nullable" requirement (creating an issue without a
  description failed at the DB layer). The column is now nullable, matching the
  validation contract. Done as a separate additive migration, consistent with
  how the project dates were added.

## Tests run

`php artisan test` → **82 passed (211 assertions)**, ~1s. (33 from Checkpoint 1
plus 49 new methods covering the 50 enumerated cases — three POST-comment
assertions are grouped into one method — and one regression guard for the
nullable-description fix.)

New suites:
- `Tests\Feature\Issues\IssueCrudTest` — index/create/show/edit/update/delete,
  project-dropdown, query-string preselect, validation failures (project,
  status, priority, title), nullable description, project→issue links.
- `Tests\Feature\Issues\IssueFilterTest` — status, priority, tag, combined
  filters, and filter-preserving pagination.
- `Tests\Feature\Tags\TagTest` — index, create, duplicate-name failure, nullable
  colour, issue counts.
- `Tests\Feature\Issues\IssueTagApiTest` — attach/detach JSON, idempotent
  attach (no duplicate pivot), safe missing-detach, 404 for unknown issue/tag.
- `Tests\Feature\Issues\IssueCommentApiTest` — JSON list, pagination, newest
  first, 201 create, correct issue association, 422 validation JSON, page 2.

`vendor/bin/pint --test` → **passed**.

## Playwright MCP verification (real browser, dev server)

All flows passed against `php artisan serve`, **0 console errors** in the final
state:

- **Issue list** loads; rows show title, project, status, priority, due date,
  tags, and View/Edit/Delete actions; pagination "Page 1 of 4".
- **Filters**: selected status=open + priority=high → every visible row matched;
  both selects stayed selected; a "Clear" link appeared.
- **Project integration**: issue titles on a project page link to the issue
  detail; "New issue" links to `/issues/create?project_id=2`, which preselects
  "Mobile App v2".
- **Create**: empty title → inline error + preserved input; a valid issue (no
  description) created and redirected to its detail page.
- **Edit**: form pre-filled; changing status→closed and priority→high persisted.
- **Delete**: JS confirm dialog (with the issue title) → redirect to the list,
  issue gone, "Issue deleted." flash.
- **AJAX tags**: attaching "backend" moved it to *Attached* with a Detach button
  and no reload; detaching moved it back to *Available* — URL never changed.
- **AJAX comments**: loaded automatically (5/page, "Page 1 of 3"); Next advanced
  to page 2 in place; a valid comment prepended to the list, cleared the form,
  and showed "Comment added."; an empty submission showed inline field errors
  with no failed request.
- **Tags**: index shows chips, colours, and per-tag issue counts; a new tag was
  created ("Tag created." flash); a duplicate name showed the inline error
  "The name has already been taken." with input preserved.

## API / HTTP verification (curl against the dev server)

| Request | Result |
| ------- | ------ |
| `GET /issues` (+ `?status` / `?priority` / `?tag` / combined / invalid) | 200 |
| `GET /issues/create` (+ `?project_id`) | 200 |
| `POST /issues` (valid, no description, CSRF) | 302 → show; row created |
| `GET /issues/{id}` · `GET /issues/{id}/edit` | 200 |
| `PATCH /issues/{id}` | 302 |
| `POST /issues` (invalid) | 302 back; no row created |
| `DELETE /issues/{id}` | 302; row gone |
| `GET /tags` · `GET /tags/create` | 200 |
| `POST /tags` (valid) | 302; row created |
| `POST /tags` (duplicate) | 302 back; validation error |
| `POST /issues/{issue}/tags/{tag}` (attach, ×2) | 200 JSON; one pivot row |
| `DELETE /issues/{issue}/tags/{tag}` (detach) | 200 JSON |
| `POST /issues/999999/tags/{tag}` (unknown issue) | 404 |
| `GET /issues/{issue}/comments` · `?page=2` | 200 JSON (`data`+`links`+`meta`) |
| `POST /issues/{issue}/comments` (valid) | 201 JSON (`data`) |
| `POST /issues/{issue}/comments` (invalid) | 422 JSON (`errors`) |
| `POST /issues` (no CSRF token) | 419 |

The error log was empty across the run.

## Known limitations (by design for this checkpoint)

- **No authentication or authorization.** Form Request `authorize()` returns
  `true`; the AJAX tag and comment endpoints accept any caller. Adding ownership
  checks / policies is the documented next step. Comment `author_name` is a free
  field (no identity binding yet).
- The browser logs `Failed to load resource: …422` for any AJAX request that
  legitimately returns 422. The comment form's client-side required-field check
  avoids this for the common empty-form case; a 422 from a server-only rule
  (e.g. an over-long body) would still produce that benign network log. It is
  not a JavaScript error and the response is handled inline.
- Comment colours/threading, editing/deleting comments, and editing tags are out
  of scope.
- Pagination controls remain simple prev/next (no numbered pages).
- The AJAX endpoints live on `web` routes (session + CSRF) rather than a
  versioned `/api` surface; there is no rate limiting yet.

## Next checkpoint notes

- Authentication + authorization (policies) for issues, tags, comments, and the
  AJAX endpoints; bind `author_name` to the signed-in user.
- Optional user assignment on issues; comment edit/delete; tag editing.
- Consider a versioned `/api/v1` surface with rate limiting for the JSON
  endpoints if they are to be consumed beyond the issue detail page.

---

# Checkpoint 3 — Authentication, Ownership, Assignment & Search

Status: **complete and working**. All of the assignment's bonus features are
implemented: authentication with seeded demo users, project ownership enforced
by a policy, many-to-many user assignment on issues over AJAX, debounced AJAX
search, and a demo-mode login prefill. `php artisan test` is green (127), Pint
passes, and both Playwright (real browser) and live HTTP verification pass with
a clean console. The app also self-provisions, so a non-developer can run it
without manual database steps.

## Authentication approach

Hand-rolled session auth (Laravel's `Auth` facade + the `web` session guard),
**not** Breeze. Breeze would pull in a Vite/Tailwind/Alpine build pipeline,
which breaks this project's deliberate no-build, static-asset design (the app
runs with `php artisan serve` and no `npm`). The hand-rolled controller keeps
that design and applies the auth controls directly:

- `AuthenticatedSessionController` — `create` (login form), `store` (attempt +
  `session()->regenerate()`), `destroy` (logout + `invalidate()` +
  `regenerateToken()`). Session-id rotation on login and full invalidation on
  logout defend against session fixation (OWASP Session Management).
- `App\Http\Requests\Auth\LoginRequest` — validates, throttles brute force per
  email + IP (5 attempts, OWASP "Blocking Brute Force Attacks"), and returns one
  generic "These credentials do not match our records." message for both a wrong
  password and an unknown account (enumeration prevention, NIST SP 800-63B-4).
- Registration is disabled — the seeded demo users are the way in.
- Routes: `GET /login` (`login`), `POST /login` (`login.store`), `POST /logout`
  (`logout`). A successful login redirects to `/projects`; the `auth` middleware
  redirects guests to `/login` and returns 401 to the JSON endpoints.

## Demo users seeded

Five accounts, all with the password `password` (hashed by the `User` model's
`password` cast — never stored in plain text):

| Name | Email | Role in the demo |
| ---- | ----- | ---------------- |
| Demo Owner | owner@example.com | Owns every seeded project |
| Demo Member | member@example.com | A non-owner — cannot edit others' projects |
| QA Reviewer | qa@example.com | Assignable to issues |
| Frontend Developer | frontend@example.com | Assignable to issues |
| Backend Developer | backend@example.com | Assignable to issues |

## Project ownership behaviour

- Migration `2026_06_22_100009_add_user_id_to_projects_table` adds `user_id` as
  a foreign key with **cascade on delete** — a user's projects are removed with
  the user. That is the least-surprising rule for a demo where a user owns their
  projects outright; a production system would reassign or soft-delete instead.
  The column is NOT NULL, added while the table is empty under `migrate:fresh`.
- `Project::owner()` ↔ `User::projects()`; `user_id` is cast to `int` so the
  policy's strict comparison holds across database drivers.
- New projects belong to their creator — `store()` creates through
  `$request->user()->projects()->create(...)`, so `user_id` is never taken from
  request input (CWE-915).
- **Consistent rule (documented):** any authenticated user may list, view, and
  create projects; only the **owner** may update or delete one. Viewing stays
  open to authenticated users; editing/deleting is owner-only.
- The seeder gives all six demo projects to Demo Owner, so the rule is visible
  immediately.

## ProjectPolicy behaviour

`App\Policies\ProjectPolicy` (registered explicitly in `AppServiceProvider` and
also discoverable by convention):

| Ability | Rule |
| ------- | ---- |
| `viewAny` | any authenticated user |
| `view` | any authenticated user |
| `create` | any authenticated user |
| `update` | owner only (`user.id === project.user_id`) |
| `delete` | owner only |

Enforced with a per-action `$this->authorize(...)` call in `ProjectController`.
`authorizeResource()` is intentionally avoided: on Laravel 11+ it relies on a
controller `middleware()` instance method that no longer exists. Blade gates the
Edit/Delete buttons with `@can`, so a non-owner never sees a broken button;
direct URL access by a non-owner returns a styled **403** page.

## issue_user migration & relationships

- `2026_06_22_100008_create_issue_user_table` — pivot with `issue_id` +
  `user_id` (both cascade on delete), `unique(issue_id, user_id)`, an index on
  `user_id`, and no surrogate id/timestamps (mirrors `issue_tag`).
- `Issue::assignees()` (belongsToMany User) ↔ `User::assignedIssues()`.

## Issue assignment AJAX routes

- `IssueUserController` — JSON `store`/`destroy`, idempotent
  (`syncWithoutDetaching` / `detach`), owner-gated by `IssuePolicy::assign` (the
  authenticated user must own the issue's project). Behind the `auth` group, so
  guests get 401 and non-owners 403 — never publicly accessible.
- Routes: `POST /issues/{issue}/users/{user}` (`issues.users.attach`),
  `DELETE /issues/{issue}/users/{user}` (`issues.users.detach`).
- JSON: `{ "assigned": true|false, "user": { "id", "name", "email" }, "message": … }`.
- The issue detail page shows the assigned users (name + email) to everyone; the
  project owner additionally gets an assign/unassign manager. `issue-show.js`
  drives it over `fetch()` with the CSRF header, renders with `textContent`
  (never `innerHTML`), disables buttons in flight, and shows inline errors — no
  full-page reload, no `console.log`.
- The seeder assigns ~60% of issues one to three random members (some have
  several, some none).

## Search implementation

- `Issue::scopeSearch($term)` — `title`/`description` `LIKE` with the term bound,
  not concatenated (CWE-89); null/empty is a no-op. The OR is grouped so it
  composes as a single AND term with the status/priority/tag scopes.
- `IssueController@index` threads the term through the scope chain, preserves all
  filters across pagination (`withQueryString`), and on an XHR returns just the
  `issues._results` partial.
- `issues-index.js` — 300 ms debounce, `fetch()` the partial, swap via
  `DOMParser` + `replaceChildren` (never `innerHTML`), loading + error states, a
  clear button, AJAX pagination via event delegation, and `history.replaceState`
  to keep the URL shareable. Progressive enhancement — the GET form still works
  with JavaScript off.

## DEMO_MODE login prefill

`config('app.demo_mode')` reads `(bool) env('DEMO_MODE', false)`. When on, the
login form prefills the Demo Owner email + password and shows the hint "Demo
credentials are prefilled in demo mode." When off, the fields are empty and no
credentials are exposed. It **never auto-logs-in** — the reviewer still clicks
Sign in. `.env.example` ships `DEMO_MODE=false`; `app:install` enables it for
the local demo.

## Self-provisioning setup (for a non-developer reviewer)

- `php artisan app:install` (also `composer setup`) creates `.env`, generates
  the app key, creates the SQLite file, migrates + seeds, and enables demo mode.
  Idempotent; `--fresh` drops and reseeds.
- `AppServiceProvider` auto-provisions the database (migrate + seed) on the first
  web request **in demo mode**, so even a bare `php artisan serve` self-heals a
  missing/empty database. Guarded to web requests + demo mode + file SQLite — it
  never runs in an artisan command, a test, or a real deployment.
- `composer start` runs install + serve in one command.

Reviewer flow: `composer install` → `composer setup` → `php artisan serve` (or
just `composer start`), then sign in with the prefilled credentials.

## Tests run

`php artisan test` → **127 passed (340 assertions)**, ~2s. (104 from the prior
checkpoints plus the bonus suites.) `vendor/bin/pint --test` → **passed**.

New / updated suites:
- `Tests\Feature\Auth\AuthenticationTest` — login page, owner/member login,
  logout, invalid login stays a guest, enumeration-safe message, prefill on/off.
- `Tests\Feature\Projects\ProjectCrudTest` — rewritten for auth; 13 ownership
  cases (guest gating, owner can edit/update/delete, non-owner 403, hidden
  buttons, owner shown on the list).
- `Tests\Feature\Issues\IssueUserApiTest` — pivot exists, both relations,
  detail-page render, assign JSON, idempotent duplicate, unassign,
  missing-unassign no-crash, guest 401, non-owner 403, seeded assignees.
- `Tests\Feature\Issues\IssueSearchTest` — title, description, exclusion, combine
  with each filter and all together, empty-returns-all, XHR-returns-partial,
  pagination preserves the query.
- The existing Issue/Tag/Comment HTTP suites now authenticate (every application
  route sits behind `auth`).

## Playwright MCP verification (real browser, dev server)

All flows passed against `php artisan serve`, **0 console errors** on every
normal page:

- Login form is prefilled in demo mode; Sign in → redirect to `/projects`; all
  six demo projects render with the **Owner** column.
- As Demo Owner the project rows show **View / Edit / Delete**.
- Issue detail: the assignment manager assigns "Backend Developer" via AJAX (it
  moves to *Assigned*, the button becomes *Unassign*, no reload), then unassigns
  it back — `POST` and `DELETE …/users/…` both return 200, console clean.
- Issues index: typing "Throttle" debounces and swaps the results in place (URL
  → `?search=Throttle`, no reload); adding `status=closed` narrows to the single
  match; the **Clear** button empties the search and the full list returns.
- As Demo Member (non-owner): the project rows show **only View** (no Edit /
  Delete); visiting `/projects/1/edit` directly returns the styled **403** page.

## API / HTTP verification (curl against the dev server)

| Request | Result |
| ------- | ------ |
| `GET /login` | 200; demo email + hint prefilled; CSP present; `X-Powered-By` suppressed |
| `GET /` · `GET /projects` · `GET /issues` (guest) | 302 → `/login` |
| `POST /login` (CSRF + session) | 302 → `/projects` |
| `GET /projects` (auth) | 200; owner column shown |
| `GET /issues?search=login` (XHR) | 200; results partial only (no layout) |
| `POST /issues/{issue}/users/{user}` (owner) | 200 `{ "assigned": true, "user": {…} }` |
| `DELETE /issues/{issue}/users/{user}` (owner) | 200 `{ "assigned": false, … }` |
| `DELETE /projects/{id}` (non-owner) | 403 |
| `POST /issues/{issue}/users/{user}` (non-owner) | 403 |
| `DELETE /projects/{id}` (owner) | 302 |

## Known limitations (by design for this checkpoint)

- **Authorization scope.** Only project edit/delete and issue assignment are
  owner-gated. Any authenticated user can still create/edit/delete issues, tags,
  and comments — issue/tag ownership was out of scope. Comment `author_name` is
  still a free field, not bound to the signed-in user.
- A directly-requested 403 URL logs one benign "Failed to load resource: 403" in
  the browser console — the browser reporting the document's own 403 status
  (inherent to any 403 navigation), not a CSP or script error. The UI never
  links a non-owner there; the buttons are hidden.
- `DEMO_MODE` prefills the password into the HTML for one-click review. This is
  intentional for a demo only and must stay `false` in production.
- `app:install` enables demo mode for whoever installs (this is an
  assignment/demo app); a real deployment sets `DEMO_MODE=false`.
- The AJAX endpoints remain on `web` routes (session + CSRF), not a versioned
  `/api`, and have no rate limiting beyond the login throttle.
- Sessions, cache, and the queue use the SQLite database; no Redis or queue
  worker is run.

## Final checkpoint notes

The application now satisfies the assignment's required core **and** all of its
bonus features — multi-user assignment, authorization with policies, and search
— plus a non-developer-friendly setup. Possible follow-ups: bind comment authors
to the signed-in user, add issue/tag ownership, numbered pagination, a versioned
`/api/v1` with rate limiting, and password-reset / MFA flows.
