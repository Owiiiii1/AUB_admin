<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CustomersController extends Controller
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
        'student_email',
        'student_phone',
        'parent_phone',
        'parent_email',
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
        'email',
        'phone',
        'address',
        'notes',
        'status',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}
    public function index(): Response
    {
        $customers = Customer::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Customer $customer): array => $this->customerPayload($customer))
            ->all();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Profile', [
            'mode' => 'create',
            'customer' => null,
        ]);
    }

    public function show(Request $request, Customer $customer): Response
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
            'customer' => $this->customerPayload($customer),
            'returnTo' => $returnTo,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();
        $payload = $this->buildPayload($validated);
        $customer = Customer::query()->create($payload);
        $fileUpdates = $this->storeUploadedFiles($request, $customer);
        if ($fileUpdates !== []) {
            $customer->forceFill($fileUpdates)->save();
            $payload = [...$payload, ...$fileUpdates];
        }

        $this->activityLogger->logForModel(
            $request,
            'created',
            $customer,
            'customer',
            $customer->id,
            null,
            $payload,
        );

        return redirect()->route('customers.show', $customer);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = validator($this->normalize($request->all()), $this->rules())->validate();

        $before = $customer->only(self::LOG_FIELDS);
        $payload = $this->buildPayload($validated);
        $customer->update($payload);
        $fileUpdates = $this->storeUploadedFiles($request, $customer);
        if ($fileUpdates !== []) {
            $customer->forceFill($fileUpdates)->save();
            $payload = [...$payload, ...$fileUpdates];
        }

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $customer,
            'customer',
            $customer->id,
            $before,
            $payload,
        );

        return redirect()->route('customers.show', $customer);
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $before = $customer->only(self::LOG_FIELDS);
        $customerId = $customer->id;
        $label = $customer->name;

        $customer->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'customer',
            $customerId,
            $label,
            $customerId,
            $before,
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
            'email',
            'phone',
            'address',
            'notes',
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
     * @param array<string, mixed> $validated
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
            'student_email' => $validated['student_email'] ?? null,
            'student_phone' => $validated['student_phone'] ?? null,
            'parent_phone' => $validated['parent_phone'] ?? null,
            'parent_email' => $validated['parent_email'] ?? null,
            'course_aa_2026_27' => $validated['course_aa_2026_27'] ?? null,
            'other_courses' => $validated['other_courses'] ?? null,
            'is_existing_student' => (bool) ($validated['is_existing_student'] ?? false),
            'form_filled_at' => $validated['form_filled_at'] ?? null,
            'medical_certificate_expiry' => $validated['medical_certificate_expiry'] ?? null,
            'father_first_name' => $validated['father_first_name'] ?? null,
            'father_last_name' => $validated['father_last_name'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'father_email' => $validated['father_email'] ?? null,
            'father_notes' => $validated['father_notes'] ?? null,
            'mother_first_name' => $validated['mother_first_name'] ?? null,
            'mother_last_name' => $validated['mother_last_name'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_email' => $validated['mother_email'] ?? null,
            'mother_notes' => $validated['mother_notes'] ?? null,
            'student_notes' => $validated['student_notes'] ?? null,
            // Backward compatibility fields
            'email' => $validated['student_email'] ?? null,
            'phone' => $validated['student_phone'] ?? null,
            'address' => $validated['residence_address'] ?? null,
            'notes' => $validated['student_notes'] ?? null,
            'status' => 'active',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function storeUploadedFiles(Request $request, Customer $customer): array
    {
        $fileMap = [
            'student_photo' => 'student_photo_path',
            'parent_id_document' => 'parent_id_document_path',
            'general_regulation_form' => 'general_regulation_form_path',
            'minor_entry_exit_form' => 'minor_entry_exit_form_path',
            'rights_release_form' => 'rights_release_form_path',
        ];

        $updates = [];
        foreach ($fileMap as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $existing = $customer->{$column};
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }

            $updates[$column] = $request->file($input)->store("students/{$customer->id}/documents", 'public');
        }

        return $updates;
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'gender' => $customer->gender,
            'tax_code' => $customer->tax_code,
            'birth_date' => optional($customer->birth_date)->toDateString(),
            'birth_place' => $customer->birth_place,
            'residence_address' => $customer->residence_address,
            'residence_city_province' => $customer->residence_city_province,
            'residence_postal_code' => $customer->residence_postal_code,
            'student_email' => $customer->student_email,
            'student_phone' => $customer->student_phone,
            'parent_phone' => $customer->parent_phone,
            'parent_email' => $customer->parent_email,
            'course_aa_2026_27' => $customer->course_aa_2026_27,
            'other_courses' => $customer->other_courses,
            'is_existing_student' => (bool) $customer->is_existing_student,
            'form_filled_at' => optional($customer->form_filled_at)->toDateString(),
            'medical_certificate_expiry' => optional($customer->medical_certificate_expiry)->toDateString(),
            'student_photo_path' => $customer->student_photo_path,
            'parent_id_document_path' => $customer->parent_id_document_path,
            'general_regulation_form_path' => $customer->general_regulation_form_path,
            'minor_entry_exit_form_path' => $customer->minor_entry_exit_form_path,
            'rights_release_form_path' => $customer->rights_release_form_path,
            'father_first_name' => $customer->father_first_name,
            'father_last_name' => $customer->father_last_name,
            'father_phone' => $customer->father_phone,
            'father_email' => $customer->father_email,
            'father_notes' => $customer->father_notes,
            'mother_first_name' => $customer->mother_first_name,
            'mother_last_name' => $customer->mother_last_name,
            'mother_phone' => $customer->mother_phone,
            'mother_email' => $customer->mother_email,
            'mother_notes' => $customer->mother_notes,
            'student_notes' => $customer->student_notes,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'status' => $customer->status,
            'notes' => $customer->notes,
            'created_at' => optional($customer->created_at)->toIso8601String(),
        ];
    }
}
