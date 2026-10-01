<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    /**
     * @return Collection<int, Todo>
     */
    public function index(Request $request): Collection
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            // active = still open, inactive = done
            'active' => ['nullable', 'boolean'],
        ]);

        return $request->user()->todos()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('date', '<=', $to))
            ->when(isset($filters['active']), fn ($query) => $request->boolean('active') ? $query->open() : $query->done())
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    public function store(TodoRequest $request): JsonResponse
    {
        return response()->json($request->user()->todos()->create($request->payload())->refresh(), 201);
    }

    public function show(Request $request, Todo $todo): Todo
    {
        abort_unless($todo->user_id === $request->user()->id, 404);

        return $todo;
    }

    /**
     * Final: there is no endpoint to undo it.
     */
    public function done(Request $request, Todo $todo): Todo
    {
        abort_unless($todo->user_id === $request->user()->id, 404);

        $todo->markDone();

        return $todo;
    }
}
