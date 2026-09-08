<?php

namespace App\Http\Controllers;

use App\Exceptions\AccountIdentityException;
use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ActorAccountController extends Controller
{
    public function __construct(
        private readonly AccountIdentityService $identity
    ) {}

    public function storeStudent(Request $request, Student $student): RedirectResponse
    {
        return $this->storeForProfile($request, $student);
    }

    public function linkStudent(Request $request, Student $student): RedirectResponse
    {
        return $this->linkForProfile($request, $student);
    }

    public function unlinkStudent(Student $student): RedirectResponse
    {
        $this->identity->unlink($student);

        return back();
    }

    public function disableStudent(Student $student): RedirectResponse
    {
        return $this->disableForProfile($student);
    }

    public function storeParent(Request $request, Student $student, AcademyParent $parent): RedirectResponse
    {
        $this->assertParentBelongsToStudent($student, $parent);

        return $this->storeForProfile($request, $parent);
    }

    public function linkParent(Request $request, Student $student, AcademyParent $parent): RedirectResponse
    {
        $this->assertParentBelongsToStudent($student, $parent);

        return $this->linkForProfile($request, $parent);
    }

    public function unlinkParent(Student $student, AcademyParent $parent): RedirectResponse
    {
        $this->assertParentBelongsToStudent($student, $parent);
        $this->identity->unlink($parent);

        return back();
    }

    public function disableParent(Student $student, AcademyParent $parent): RedirectResponse
    {
        $this->assertParentBelongsToStudent($student, $parent);

        return $this->disableForProfile($parent);
    }

    public function storeTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        return $this->storeForProfile($request, $teacher);
    }

    public function linkTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        return $this->linkForProfile($request, $teacher);
    }

    public function unlinkTeacher(Teacher $teacher): RedirectResponse
    {
        $this->identity->unlink($teacher);

        return back();
    }

    public function disableTeacher(Teacher $teacher): RedirectResponse
    {
        return $this->disableForProfile($teacher);
    }

    private function storeForProfile(Request $request, Student|AcademyParent|Teacher $profile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->identity->createAndLink($profile, $validated);
        } catch (AccountIdentityException $exception) {
            throw $this->identity->toValidationException($exception);
        }

        return back();
    }

    private function linkForProfile(Request $request, Student|AcademyParent|Teacher $profile): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        try {
            $this->identity->link($user, $profile);
        } catch (AccountIdentityException $exception) {
            throw $this->identity->toValidationException($exception, 'user_id');
        }

        return back();
    }

    private function disableForProfile(Student|AcademyParent|Teacher $profile): RedirectResponse
    {
        $profile->loadMissing('user');

        if ($profile->user === null) {
            throw ValidationException::withMessages([
                'account' => 'This profile has no linked account.',
            ]);
        }

        $this->identity->setActive($profile->user, false);

        return back();
    }

    private function assertParentBelongsToStudent(Student $student, AcademyParent $parent): void
    {
        if (! $student->parents()->where('parents.id', $parent->id)->exists()) {
            abort(404);
        }
    }
}
