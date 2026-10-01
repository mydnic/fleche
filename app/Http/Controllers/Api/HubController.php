<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Hub;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated: what self-hosted instances read the hub from.
 * Only served by the cloud edition.
 */
class HubController extends Controller
{
    public function __construct(private Hub $hub)
    {
        abort_unless(config('fleche.edition') === 'cloud', 404);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(Request $request): array
    {
        return $this->hub->search($request->string('q')->toString() ?: null);
    }

    /**
     * @return array<string, mixed>
     */
    public function import(int $pack): array
    {
        return $this->hub->take($pack);
    }
}
