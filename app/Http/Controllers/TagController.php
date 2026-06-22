<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class TagController extends Controller
{
    /**
     * Tag list with a per-tag issue count. withCount avoids an N+1 query when
     * rendering the count column.
     */
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount('issues')
            ->orderBy('name')
            ->paginate(20);

        return view('tags.index', ['tags' => $tags]);
    }

    public function create(): View
    {
        return view('tags.create', ['tag' => new Tag]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', 'Tag created.');
    }
}
