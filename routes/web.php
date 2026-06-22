<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// The application opens on the project list.
Route::get('/', static fn () => redirect()->route('projects.index'));

// Full CRUD for projects via a resource controller (index/create/store/show/
// edit/update/destroy) with implicit route-model binding.
Route::resource('projects', ProjectController::class);
