<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\IssueCommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueTagController;
use App\Http\Controllers\IssueUserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// The application opens on the project list (which requires authentication, so
// a guest is forwarded from here to the login page).
Route::get('/', static fn () => redirect()->route('projects.index'));

// Authentication (hand-rolled session auth — see CHECKPOINT.md for why Breeze
// is not used). The login form is server-rendered; throttling and
// enumeration-resistant errors live in App\Http\Requests\Auth\LoginRequest.
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

// Everything below requires an authenticated user. The auth middleware redirects
// guests to the login page for web requests and returns 401 for requests that
// expect JSON (the AJAX endpoints).
Route::middleware('auth')->group(function (): void {
    // Logout is a POST so it carries a CSRF token and cannot be triggered
    // cross-site.
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Full CRUD for projects. Owner-only edit/delete is enforced by
    // ProjectPolicy via per-action $this->authorize() in the controller.
    Route::resource('projects', ProjectController::class);

    // Full CRUD for issues. Filtering (status/priority/tag) and text search are
    // handled on index via query parameters and the Issue model scopes.
    Route::resource('issues', IssueController::class);

    // Tags: list and create only. Attaching/detaching to issues happens through
    // the AJAX endpoints below, not through tag-level routes.
    Route::resource('tags', TagController::class)->only(['index', 'create', 'store']);

    // AJAX endpoints used by the issue detail page. These return JSON and are
    // consumed by fetch() with no full-page reload.
    //
    // Tag attach/detach (idempotent via syncWithoutDetaching / detach).
    Route::post('issues/{issue}/tags/{tag}', [IssueTagController::class, 'store'])
        ->name('issues.tags.attach');
    Route::delete('issues/{issue}/tags/{tag}', [IssueTagController::class, 'destroy'])
        ->name('issues.tags.detach');

    // User assignment attach/detach (idempotent). Only the owner of the issue's
    // project may manage assignments (IssuePolicy::assign).
    Route::post('issues/{issue}/users/{user}', [IssueUserController::class, 'store'])
        ->name('issues.users.attach');
    Route::delete('issues/{issue}/users/{user}', [IssueUserController::class, 'destroy'])
        ->name('issues.users.detach');

    // Paginated comments list + comment creation.
    Route::get('issues/{issue}/comments', [IssueCommentController::class, 'index'])
        ->name('issues.comments.index');
    Route::post('issues/{issue}/comments', [IssueCommentController::class, 'store'])
        ->name('issues.comments.store');
});
