<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TodoSettingRequest;
use App\Models\TodoSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TodoSettingController extends Controller
{
    /**
     * @return Collection<int, TodoSetting>
     */
    public function index(Request $request): Collection
    {
        return $request->user()->todoSettings()->orderBy('id')->get();
    }

    public function store(TodoSettingRequest $request): JsonResponse
    {
        return response()->json($request->user()->todoSettings()->create($request->payload())->refresh(), 201);
    }

    public function show(Request $request, TodoSetting $todoSetting): TodoSetting
    {
        abort_unless($todoSetting->user_id === $request->user()->id, 404);

        return $todoSetting;
    }

    public function update(TodoSettingRequest $request, TodoSetting $todoSetting): TodoSetting
    {
        abort_unless($todoSetting->user_id === $request->user()->id, 404);

        $todoSetting->update($request->payload());

        return $todoSetting;
    }

    public function destroy(Request $request, TodoSetting $todoSetting): Response
    {
        abort_unless($todoSetting->user_id === $request->user()->id, 404);

        $todoSetting->delete();

        return response()->noContent();
    }
}
