<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeachersController extends Controller
{
    /**
     * @var list<string>
     */
    private const LOG_FIELDS = [
        'type',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'tax_code',
        'description',
        'photo_path',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function index(): Response
    {
        $teachers = Teacher::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Teacher $teacher): array => $this->teacherPayload($teacher))
            ->all();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Teachers/Profile', [
            'mode' => 'create',
            'teacher' => null,
        ]);
    }

    public function show(Teacher $teacher): Response
    {
        return Inertia::render('Teachers/Profile', [
            'mode' => 'edit',
            'teacher' => $this->teacherPayload($teacher),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();
        $payload = $this->buildPayload($validated);
        $teacher = Teacher::query()->create($payload);
        $fileUpdates = $this->storeUploadedPhoto($request, $teacher);

        if ($fileUpdates !== []) {
            $teacher->forceFill($fileUpdates)->save();
            $payload = [...$payload, ...$fileUpdates];
        }

        $this->activityLogger->logForModel(
            $request,
            'created',
            $teacher,
            'teacher',
            $teacher->id,
            null,
            $payload,
        );

        return redirect()->route('teachers.show', $teacher);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();

        $before = $teacher->only(self::LOG_FIELDS);
        $payload = $this->buildPayload($validated);
        $teacher->update($payload);
        $fileUpdates = $this->storeUploadedPhoto($request, $teacher);

        if ($fileUpdates !== []) {
            $teacher->forceFill($fileUpdates)->save();
            $payload = [...$payload, ...$fileUpdates];
        }

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $teacher,
            'teacher',
            $teacher->id,
            $before,
            $payload,
        );

        return redirect()->route('teachers.show', $teacher);
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $before = $teacher->only(self::LOG_FIELDS);
        $teacherId = $teacher->id;
        $label = $teacher->name;

        if ($teacher->photo_path) {
            Storage::disk('public')->delete($teacher->photo_path);
        }

        $teacher->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'teacher',
            $teacherId,
            $label,
            null,
            $before,
        );

        return redirect()->route('teachers.index');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        foreach ([
            'type',
            'first_name',
            'last_name',
            'email',
            'phone',
            'tax_code',
            'description',
        ] as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'type' => ['required', 'in:permanent,temporary'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildPayload(array $validated): array
    {
        $firstName = $validated['first_name'];
        $lastName = $validated['last_name'];

        return [
            'type' => $validated['type'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'tax_code' => $validated['tax_code'] ?? null,
            'description' => $validated['description'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function storeUploadedPhoto(Request $request, Teacher $teacher): array
    {
        if (! $request->hasFile('photo')) {
            return [];
        }

        if ($teacher->photo_path) {
            Storage::disk('public')->delete($teacher->photo_path);
        }

        return [
            'photo_path' => $request->file('photo')->store("teachers/{$teacher->id}/photos", 'public'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function teacherPayload(Teacher $teacher): array
    {
        return [
            'id' => $teacher->id,
            'type' => $teacher->type,
            'first_name' => $teacher->first_name,
            'last_name' => $teacher->last_name,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'tax_code' => $teacher->tax_code,
            'description' => $teacher->description,
            'photo_path' => $teacher->photo_path,
        ];
    }
}
