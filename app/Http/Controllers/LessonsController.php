<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonsController extends Controller
{
    /**
     * @var list<string>
     */
    private const LOG_FIELDS = [
        'discipline',
        'name',
        'description',
        'sort_order',
        'teacher_ids',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $tab = $this->resolveTab($request->query('tab', 'ballet'));

        return redirect()->route('settings.index', [
            'tab' => 'academy',
            'academyTab' => 'lessons',
            'lessonTab' => $tab,
        ]);
    }

    /**
     * @return array{lessonTab: string, lessons: list<array<string, mixed>>, teachers: list<array<string, mixed>>}
     */
    public function catalogForPage(?string $tab): array
    {
        $lessonTab = $this->resolveTab($tab);

        $lessons = Lesson::query()
            ->where('discipline', $lessonTab)
            ->with(['teachers:id,name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Lesson $lesson): array => $this->lessonPayload($lesson))
            ->all();

        return [
            'lessonTab' => $lessonTab,
            'lessons' => $lessons,
            'teachers' => $this->teachersPayload(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = validator(
            $this->normalize($request->all()),
            $this->rules($this->resolveTab($request->input('discipline', 'ballet'))),
        )->validate();

        $sortOrder = (int) Lesson::query()
            ->where('discipline', $validated['discipline'])
            ->max('sort_order') + 1;

        $lesson = Lesson::query()->create([
            'discipline' => $validated['discipline'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        $this->syncRelations($lesson, $validated);
        $lesson->load(['teachers:id,name']);

        $this->activityLogger->logForModel(
            $request,
            'created',
            $lesson,
            'lesson',
            null,
            null,
            $this->logSnapshot($lesson),
        );

        return $this->redirectBack($validated['discipline']);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $validated = validator(
            $this->normalize($request->all()),
            $this->rules($lesson->discipline, updating: true),
        )->validate();

        $before = $this->logSnapshot($lesson);
        $lesson->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncRelations($lesson, $validated);
        $lesson->load(['teachers:id,name']);

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $lesson,
            'lesson',
            $lesson->id,
            $before,
            $this->logSnapshot($lesson),
        );

        return $this->redirectBack($lesson->discipline);
    }

    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        $discipline = $lesson->discipline;
        $before = $this->logSnapshot($lesson);
        $lessonId = $lesson->id;
        $label = $lesson->name;

        $lesson->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'lesson',
            $lessonId,
            $label,
            null,
            $before,
        );

        return $this->redirectBack($discipline);
    }

    private function resolveTab(?string $tab): string
    {
        return in_array($tab, ['ballet', 'tam', 'carcano'], true) ? $tab : 'ballet';
    }

    private function redirectBack(string $discipline): RedirectResponse
    {
        return redirect()->route('settings.index', [
            'tab' => 'academy',
            'academyTab' => 'lessons',
            'lessonTab' => $discipline,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $discipline, bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
        ];

        if (! $updating) {
            $rules['discipline'] = ['required', 'in:ballet,tam,carcano'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelations(Lesson $lesson, array $validated): void
    {
        $teacherIds = collect($validated['teacher_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $lesson->teachers()->sync($teacherIds);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function teachersPayload(): array
    {
        return Teacher::query()
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name'])
            ->map(static fn (Teacher $teacher): array => [
                'id' => $teacher->id,
                'name' => (string) ($teacher->name ?: trim($teacher->first_name.' '.$teacher->last_name)),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function lessonPayload(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'discipline' => $lesson->discipline,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'sort_order' => $lesson->sort_order,
            'teachers' => $lesson->relationLoaded('teachers')
                ? $lesson->teachers->map(static fn (Teacher $teacher): array => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ])->all()
                : [],
            'teacher_ids' => $lesson->relationLoaded('teachers')
                ? $lesson->teachers->pluck('id')->map(static fn ($id): int => (int) $id)->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function logSnapshot(Lesson $lesson): array
    {
        if (! $lesson->relationLoaded('teachers')) {
            $lesson->load('teachers:id');
        }

        return [
            ...$lesson->only(['discipline', 'name', 'description', 'sort_order']),
            'teacher_ids' => $lesson->teachers->pluck('id')->map(static fn ($id): int => (int) $id)->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        if (array_key_exists('description', $input) && $input['description'] === '') {
            $input['description'] = null;
        }

        foreach (['teacher_ids'] as $field) {
            if (! array_key_exists($field, $input) || ! is_array($input[$field])) {
                $input[$field] = [];
            }
        }

        return $input;
    }
}
