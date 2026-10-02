<?php

namespace App\Support;

use App\Enums\HubPackStatus;
use App\Models\HubPack;
use App\Models\TodoSetting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The community hub lives on the cloud instance. There it is read straight
 * from the database; a self-hosted instance reads the same data over HTTP
 * from the cloud's public `/api/hub` endpoints.
 */
class Hub
{
    private function local(): bool
    {
        return config('fleche.edition') === 'cloud';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $query = null): array
    {
        if (! $this->local()) {
            return Http::timeout(10)->get(config('fleche.hub_url').'/api/hub/packs', ['q' => $query])->throw()->json();
        }

        return HubPack::query()
            ->where('status', HubPackStatus::Approved)
            ->when($query, fn ($builder) => $builder->whereAny(['name', 'description'], 'like', '%'.$query.'%'))
            ->with('user:id,name')
            ->orderByDesc('imports_count')
            ->limit(100)
            ->get()
            ->map(fn (HubPack $pack): array => $this->present($pack))
            ->all();
    }

    /**
     * Fetches an approved pack and counts the import, crediting its author.
     * The same importer (a user, or an IP for self-hosted instances) only
     * counts once per pack, ever: the public endpoint can't be spammed to
     * inflate a pack's ranking or its author's points.
     *
     * @return array<string, mixed>
     */
    public function take(int $id, string $importer): array
    {
        if (! $this->local()) {
            return Http::timeout(10)->post(config('fleche.hub_url')."/api/hub/packs/{$id}/imports")->throw()->json();
        }

        $pack = HubPack::query()->where('status', HubPackStatus::Approved)->findOrFail($id);

        DB::transaction(function () use ($pack, $importer): void {
            // The primary key makes this a no-op for a repeat importer.
            $isNew = DB::table('hub_imports')->insertOrIgnore([
                'hub_pack_id' => $pack->id,
                'importer' => hash_hmac('sha256', $importer, (string) config('app.key')),
                'created_at' => now(),
            ]);

            if ($isNew) {
                $pack->increment('imports_count');
                User::query()->whereKey($pack->user_id)->increment('points', config('fleche.hub_import_points'));
            }
        });

        return $this->present($pack->load('user:id,name'));
    }

    /**
     * Copies a pack's rules into the user's own instance.
     *
     * @param  array<string, mixed>  $pack
     */
    public function importInto(User $user, array $pack): int
    {
        $images = app(Images::class);

        foreach ($pack['rules'] as $rule) {
            // The picture lives on the author's instance: copy it onto ours.
            $user->todoSettings()->create([
                ...Arr::only($rule, TodoSetting::SHAREABLE),
                'image' => $images->fetch($rule['image_url'] ?? null),
            ]);
        }

        return count($pack['rules']);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(HubPack $pack): array
    {
        return [
            'id' => $pack->id,
            'name' => $pack->name,
            'description' => $pack->description,
            'author' => $pack->user->name ?? 'A former archer',
            'rules' => $pack->rules,
            'imports_count' => $pack->imports_count,
        ];
    }
}
