<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AcademyBuilding;
use App\Models\AcademyRoom;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AcademySettingsController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function storeBuilding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $building = AcademyBuilding::query()->create([
            'name' => $validated['name'],
            'slug' => $this->nextBuildingSlug($validated['name']),
            'address' => $validated['address'] ?? null,
            'sort_order' => ((int) AcademyBuilding::query()->max('sort_order')) + 1,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->activityLogger->logForModel($request, 'created', $building, 'academy_building');

        return $this->backToAcademyTab()->with('success', 'Hall group created.');
    }

    public function updateBuilding(Request $request, AcademyBuilding $academyBuilding): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $before = $academyBuilding->only(['name', 'address', 'is_active', 'sort_order']);

        $newName = trim($validated['name']);
        $academyBuilding->fill([
            'name' => $newName,
            'address' => $validated['address'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? $academyBuilding->sort_order),
        ]);

        if ($newName !== $academyBuilding->getOriginal('name')) {
            $academyBuilding->slug = $this->nextBuildingSlug($newName, $academyBuilding->id);
        }

        $academyBuilding->save();

        $this->activityLogger->logModelChange(
            $request,
            'updated',
            'academy_building',
            $academyBuilding->id,
            $academyBuilding->name,
            null,
            $before,
            $academyBuilding->only(['name', 'address', 'is_active', 'sort_order']),
        );

        return $this->backToAcademyTab()->with('success', 'Hall group updated.');
    }

    public function destroyBuilding(Request $request, AcademyBuilding $academyBuilding): RedirectResponse
    {
        $label = $academyBuilding->name;
        $academyBuilding->delete();

        $this->activityLogger->log(
            $request,
            'deleted',
            'academy_building',
            $academyBuilding->id,
            $label,
        );

        return $this->backToAcademyTab()->with('success', 'Hall group deleted.');
    }

    public function storeRoom(Request $request, AcademyBuilding $academyBuilding): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $room = AcademyRoom::query()->create([
            'academy_building_id' => $academyBuilding->id,
            'name' => $validated['name'],
            'slug' => $this->nextRoomSlug($academyBuilding->id, $validated['name']),
            'capacity' => $validated['capacity'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'sort_order' => ((int) $academyBuilding->rooms()->max('sort_order')) + 1,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->activityLogger->logForModel($request, 'created', $room, 'academy_room');

        return $this->backToAcademyTab()->with('success', 'Hall created.');
    }

    public function updateRoom(Request $request, AcademyBuilding $academyBuilding, AcademyRoom $academyRoom): RedirectResponse
    {
        abort_unless($academyRoom->academy_building_id === $academyBuilding->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'room_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'academy_building_id' => ['nullable', Rule::exists('academy_buildings', 'id')],
        ]);

        $targetBuildingId = (int) ($validated['academy_building_id'] ?? $academyBuilding->id);
        $before = $academyRoom->only(['academy_building_id', 'name', 'capacity', 'room_type', 'is_active', 'sort_order']);
        $newName = trim($validated['name']);

        $academyRoom->fill([
            'academy_building_id' => $targetBuildingId,
            'name' => $newName,
            'capacity' => $validated['capacity'] ?? null,
            'room_type' => $validated['room_type'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? $academyRoom->sort_order),
        ]);

        if (
            $newName !== $academyRoom->getOriginal('name')
            || $targetBuildingId !== (int) $academyRoom->getOriginal('academy_building_id')
        ) {
            $academyRoom->slug = $this->nextRoomSlug($targetBuildingId, $newName, $academyRoom->id);
        }

        $academyRoom->save();

        $this->activityLogger->logModelChange(
            $request,
            'updated',
            'academy_room',
            $academyRoom->id,
            $academyRoom->name,
            null,
            $before,
            $academyRoom->only(['academy_building_id', 'name', 'capacity', 'room_type', 'is_active', 'sort_order']),
        );

        return $this->backToAcademyTab()->with('success', 'Hall updated.');
    }

    public function destroyRoom(Request $request, AcademyBuilding $academyBuilding, AcademyRoom $academyRoom): RedirectResponse
    {
        abort_unless($academyRoom->academy_building_id === $academyBuilding->id, 404);

        $label = $academyRoom->name;
        $academyRoom->delete();

        $this->activityLogger->log(
            $request,
            'deleted',
            'academy_room',
            $academyRoom->id,
            $label,
        );

        return $this->backToAcademyTab()->with('success', 'Hall deleted.');
    }

    private function nextBuildingSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'building';
        }

        $slug = $base;
        $idx = 2;

        while ($this->buildingSlugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$idx;
            $idx++;
        }

        return $slug;
    }

    private function nextRoomSlug(int $buildingId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'room';
        }

        $slug = $base;
        $idx = 2;

        while ($this->roomSlugExists($buildingId, $slug, $ignoreId)) {
            $slug = $base.'-'.$idx;
            $idx++;
        }

        return $slug;
    }

    private function buildingSlugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = AcademyBuilding::query()->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function roomSlugExists(int $buildingId, string $slug, ?int $ignoreId = null): bool
    {
        $query = AcademyRoom::query()
            ->where('academy_building_id', $buildingId)
            ->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function backToAcademyTab(): RedirectResponse
    {
        return redirect()->route('settings.index', ['tab' => 'academy']);
    }
}

