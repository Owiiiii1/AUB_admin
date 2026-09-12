<?php

namespace App\Http\Controllers;

use App\Exceptions\SecureFileException;
use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountIdentityService;
use App\Services\ActivityLogger;
use App\Services\SecureFiles\SecureFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentsController extends Controller
{
    /**
     * @var list<string>
     */
    private const LOG_FIELDS = [
        'name',
        'first_name',
        'last_name',
        'gender',
        'tax_code',
        'birth_date',
        'birth_place',
        'residence_address',
        'residence_city_province',
        'residence_postal_code',
        'email',
        'phone',
        'course_aa_2026_27',
        'other_courses',
        'is_existing_student',
        'form_filled_at',
        'medical_certificate_expiry',
        'student_photo_path',
        'parent_id_document_path',
        'general_regulation_form_path',
        'minor_entry_exit_form_path',
        'rights_release_form_path',
        'notes',
        'status',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly AccountIdentityService $identity,
        private readonly SecureFileService $secureFiles,
    ) {}

    public function index(): Response
    {
        $students = Student::query()
            ->with('secureFiles')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Student $student): array => $this->studentPayload($student))
            ->all();

        return Inertia::render('Customers/Index', [
            'customers' => $students,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Profile', [
            'mode' => 'create',
            'customer' => null,
            'linkableStudentUsers' => [],
            'linkableParentUsers' => [],
        ]);
    }

    public function show(Request $request, Student $student): Response
    {
        $returnTo = null;

        if ($request->query('from') === 'courses-groups') {
            $tab = in_array($request->query('tab'), ['ballet', 'tam'], true)
                ? $request->query('tab')
                : 'ballet';

            $returnTo = [
                'route_name' => 'courses-groups.index',
                'route_params' => [
                    'tab' => $tab,
                    'view' => in_array($request->query('view'), ['students', 'lessons'], true)
                        ? $request->query('view')
                        : 'students',
                ],
            ];
        }

        return Inertia::render('Customers/Profile', [
            'mode' => 'edit',
            'customer' => $this->studentPayload($student),
            'returnTo' => $returnTo,
            'linkableStudentUsers' => $this->identity->linkableUsers(User::TYPE_STUDENT),
            'linkableParentUsers' => $this->identity->linkableUsers(User::TYPE_PARENT),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();
        $payload = $this->buildPayload($validated);
        $student = Student::query()->create($payload);
        $this->storeUploadedFiles($request, $student);

        $this->syncParents($student, $validated);

        $this->activityLogger->logForModel(
            $request,
            'created',
            $student,
            'student',
            $student->id,
            null,
            $payload,
        );

        return redirect()->route('customers.show', $student);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();

        $before = $student->only(self::LOG_FIELDS);
        $payload = $this->buildPayload($validated);
        $student->update($payload);
        $this->storeUploadedFiles($request, $student);

        $this->syncParents($student, $validated);

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $student,
            'student',
            $student->id,
            $before,
            $payload,
        );

        return redirect()->route('customers.show', $student);
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $before = $student->only(self::LOG_FIELDS);
        $studentId = $student->id;
        $label = $student->displayName();

        $this->secureFiles->deleteAllFor($student, $request->user(), $request);
        $student->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'student',
            $studentId,
            $label,
            null,
            $before,
            null,
            $studentId,
        );

        return redirect()->route('customers.index');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        foreach ([
            'first_name',
            'last_name',
            'gender',
            'tax_code',
            'birth_place',
            'residence_address',
            'residence_city_province',
            'residence_postal_code',
            'student_email',
            'student_phone',
            'parent_phone',
            'parent_email',
            'course_aa_2026_27',
            'other_courses',
            'father_first_name',
            'father_last_name',
            'father_phone',
            'father_email',
            'father_notes',
            'mother_first_name',
            'mother_last_name',
            'mother_phone',
            'mother_email',
            'mother_notes',
            'student_notes',
        ] as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }

        return $input;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'residence_address' => ['nullable', 'string', 'max:500'],
            'residence_city_province' => ['nullable', 'string', 'max:255'],
            'residence_postal_code' => ['nullable', 'string', 'max:20'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'student_phone' => ['nullable', 'string', 'max:50'],
            'parent_phone' => ['nullable', 'string', 'max:50'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'course_aa_2026_27' => ['nullable', 'string', 'max:255'],
            'other_courses' => ['nullable', 'string', 'max:2000'],
            'is_existing_student' => ['sometimes', 'boolean'],
            'form_filled_at' => ['nullable', 'date'],
            'medical_certificate_expiry' => ['nullable', 'date'],
            'student_photo' => ['nullable', 'image', 'max:5120'],
            'parent_id_document' => ['nullable', 'file', 'max:10240'],
            'general_regulation_form' => ['nullable', 'file', 'max:10240'],
            'minor_entry_exit_form' => ['nullable', 'file', 'max:10240'],
            'rights_release_form' => ['nullable', 'file', 'max:10240'],
            'father_first_name' => ['nullable', 'string', 'max:255'],
            'father_last_name' => ['nullable', 'string', 'max:255'],
            'father_phone' => ['nullable', 'string', 'max:50'],
            'father_email' => ['nullable', 'email', 'max:255'],
            'father_notes' => ['nullable', 'string', 'max:5000'],
            'mother_first_name' => ['nullable', 'string', 'max:255'],
            'mother_last_name' => ['nullable', 'string', 'max:255'],
            'mother_phone' => ['nullable', 'string', 'max:50'],
            'mother_email' => ['nullable', 'email', 'max:255'],
            'mother_notes' => ['nullable', 'string', 'max:5000'],
            'student_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildPayload(array $validated): array
    {
        $fullName = trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? ''));

        return [
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'] ?? null,
            'tax_code' => $validated['tax_code'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'birth_place' => $validated['birth_place'] ?? null,
            'residence_address' => $validated['residence_address'] ?? null,
            'residence_city_province' => $validated['residence_city_province'] ?? null,
            'residence_postal_code' => $validated['residence_postal_code'] ?? null,
            'email' => $validated['student_email'] ?? null,
            'phone' => $validated['student_phone'] ?? null,
            'course_aa_2026_27' => $validated['course_aa_2026_27'] ?? null,
            'other_courses' => $validated['other_courses'] ?? null,
            'is_existing_student' => (bool) ($validated['is_existing_student'] ?? false),
            'form_filled_at' => $validated['form_filled_at'] ?? null,
            'medical_certificate_expiry' => $validated['medical_certificate_expiry'] ?? null,
            'notes' => $validated['student_notes'] ?? null,
            'status' => 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncParents(Student $student, array $validated): void
    {
        $this->syncParentOfType($student, 'father', [
            'first_name' => $validated['father_first_name'] ?? null,
            'last_name' => $validated['father_last_name'] ?? null,
            'phone' => $validated['father_phone'] ?? $validated['parent_phone'] ?? null,
            'email' => $validated['father_email'] ?? $validated['parent_email'] ?? null,
            'notes' => $validated['father_notes'] ?? null,
        ]);

        $this->syncParentOfType($student, 'mother', [
            'first_name' => $validated['mother_first_name'] ?? null,
            'last_name' => $validated['mother_last_name'] ?? null,
            'phone' => $validated['mother_phone'] ?? null,
            'email' => $validated['mother_email'] ?? null,
            'notes' => $validated['mother_notes'] ?? null,
        ]);
    }

    /**
     * @param  array{first_name: ?string, last_name: ?string, phone: ?string, email: ?string, notes: ?string}  $fields
     */
    private function syncParentOfType(Student $student, string $type, array $fields): void
    {
        $existing = $student->parents()
            ->wherePivot('relation_type', $type)
            ->get();

        $hasData = collect($fields)->filter(static fn ($value) => $value !== null && $value !== '')->isNotEmpty();

        if (! $hasData) {
            $student->parents()->wherePivot('relation_type', $type)->detach();

            return;
        }

        $parent = $existing->first();
        if ($parent === null) {
            $parent = AcademyParent::query()->create($fields);
            $student->parents()->attach($parent->id, ['relation_type' => $type]);

            return;
        }

        $parent->update($fields);

        foreach ($existing->skip(1) as $duplicate) {
            $student->parents()->detach($duplicate->id);
        }
    }

    /**
     * @return void
     */
    private function storeUploadedFiles(Request $request, Student $student): void
    {
        $fileMap = [
            'student_photo' => 'profile_photo',
            'parent_id_document' => 'identity_document',
            'general_regulation_form' => 'consent_general_regulation',
            'minor_entry_exit_form' => 'consent_minor_entry_exit',
            'rights_release_form' => 'consent_rights_release',
        ];

        foreach ($fileMap as $input => $category) {
            if (! $request->hasFile($input)) {
                continue;
            }

            try {
                $this->secureFiles->replace($student, $category, $request->file($input), $request->user(), $request);
            } catch (SecureFileException $e) {
                throw ValidationException::withMessages([
                    $input => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function studentPayload(Student $student): array
    {
        $student->loadMissing(['user.role', 'parents.user.role', 'secureFiles']);
        $father = $student->parentOfType('father');
        $mother = $student->parentOfType('mother');

        return [
            'id' => $student->id,
            'name' => $student->displayName(),
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'gender' => $student->gender,
            'tax_code' => $student->tax_code,
            'birth_date' => optional($student->birth_date)->toDateString(),
            'birth_place' => $student->birth_place,
            'residence_address' => $student->residence_address,
            'residence_city_province' => $student->residence_city_province,
            'residence_postal_code' => $student->residence_postal_code,
            'student_email' => $student->email,
            'student_phone' => $student->phone,
            'parent_phone' => $father?->phone,
            'parent_email' => $father?->email,
            'course_aa_2026_27' => $student->course_aa_2026_27,
            'other_courses' => $student->other_courses,
            'is_existing_student' => (bool) $student->is_existing_student,
            'form_filled_at' => optional($student->form_filled_at)->toDateString(),
            'medical_certificate_expiry' => optional($student->medical_certificate_expiry)->toDateString(),
            'student_photo_path' => null,
            'student_photo_url' => $student->profilePhotoWebUrl(),
            'parent_id_document_path' => null,
            'parent_id_document_url' => $student->secureFileWebDownloadUrl('identity_document'),
            'general_regulation_form_path' => null,
            'general_regulation_form_url' => $student->secureFileWebDownloadUrl('consent_general_regulation'),
            'minor_entry_exit_form_path' => null,
            'minor_entry_exit_form_url' => $student->secureFileWebDownloadUrl('consent_minor_entry_exit'),
            'rights_release_form_path' => null,
            'rights_release_form_url' => $student->secureFileWebDownloadUrl('consent_rights_release'),
            'father_id' => $father?->id,
            'father_first_name' => $father?->first_name,
            'father_last_name' => $father?->last_name,
            'father_phone' => $father?->phone,
            'father_email' => $father?->email,
            'father_notes' => $father?->notes,
            'mother_id' => $mother?->id,
            'mother_first_name' => $mother?->first_name,
            'mother_last_name' => $mother?->last_name,
            'mother_phone' => $mother?->phone,
            'mother_email' => $mother?->email,
            'mother_notes' => $mother?->notes,
            'student_notes' => $student->notes,
            'email' => $student->email,
            'phone' => $student->phone,
            'address' => $student->residence_address,
            'status' => $student->status,
            'notes' => $student->notes,
            'account' => $student->user?->accountPayload(),
            'father_account' => $father?->user?->accountPayload(),
            'mother_account' => $mother?->user?->accountPayload(),
            'created_at' => optional($student->created_at)->toIso8601String(),
        ];
    }
}
