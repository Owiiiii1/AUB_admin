<?php

namespace App\Services\SecureFiles;

use App\Models\AcademyParent;
use App\Models\SecureFile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\DB;

class FileAccessService
{
    public const ACTION_VIEW = 'view';

    public const ACTION_DOWNLOAD = 'download';

    public const ACTION_UPLOAD = 'upload';

    public const ACTION_REPLACE = 'replace';

    public const ACTION_DELETE = 'delete';

    public function allows(?User $actor, SecureFile $file, string $action): bool
    {
        if ($actor === null || ! $actor->is_active) {
            return false;
        }

        if (! is_array(config('aub-files.categories.'.$file->category))) {
            return false;
        }

        $attachable = $file->attachable;
        if ($attachable === null) {
            return false;
        }

        if ($actor->canAccessWebAdmin()) {
            return $this->staffAllows($actor, $file, $attachable, $action);
        }

        return match ($actor->account_type) {
            User::TYPE_TEACHER => $this->teacherAllows($actor, $file, $attachable, $action),
            User::TYPE_PARENT => $this->parentAllows($actor, $file, $attachable, $action),
            User::TYPE_STUDENT => $this->studentAllows($actor, $file, $attachable, $action),
            default => false,
        };
    }

    public function canUpload(?User $actor, string $category, mixed $attachable): bool
    {
        if ($actor === null || ! $actor->is_active || $attachable === null) {
            return false;
        }

        $dummy = new SecureFile([
            'category' => $category,
            'variant' => SecureFile::VARIANT_ORIGINAL,
        ]);
        $dummy->setRelation('attachable', $attachable);

        return $this->allows($actor, $dummy, self::ACTION_UPLOAD);
    }

    private function staffAllows(User $actor, SecureFile $file, mixed $attachable, string $action): bool
    {
        if (! $this->staffCanMutate($actor, $action)) {
            return false;
        }

        if ($actor->isAdministrator()) {
            return true;
        }

        if ($file->category === 'profile_photo' && in_array($action, [self::ACTION_VIEW, self::ACTION_DOWNLOAD], true)) {
            return true;
        }

        if ($attachable instanceof Student) {
            return RoleAccess::userCanAccessRoute($actor, 'customers.show');
        }

        if ($attachable instanceof Teacher) {
            return RoleAccess::userCanAccessRoute($actor, 'teachers.show');
        }

        if ($attachable instanceof AcademyParent) {
            return RoleAccess::userCanAccessRoute($actor, 'customers.show');
        }

        if ($attachable instanceof User) {
            return $actor->id === $attachable->id || $actor->isAdministrator();
        }

        return false;
    }

    private function staffCanMutate(User $actor, string $action): bool
    {
        return match ($action) {
            self::ACTION_VIEW, self::ACTION_DOWNLOAD => true,
            self::ACTION_UPLOAD, self::ACTION_REPLACE => (bool) $actor->can_write || $actor->isAdministrator(),
            self::ACTION_DELETE => (bool) $actor->can_delete || $actor->isAdministrator(),
            default => false,
        };
    }

    private function teacherAllows(User $actor, SecureFile $file, mixed $attachable, string $action): bool
    {
        if (! in_array($action, [self::ACTION_VIEW, self::ACTION_DOWNLOAD], true)) {
            return false;
        }

        if ($file->category !== 'profile_photo') {
            return false;
        }

        $teacher = $actor->teacherProfile;
        if ($teacher === null) {
            return false;
        }

        if ($attachable instanceof Teacher) {
            return (int) $attachable->id === (int) $teacher->id;
        }

        if ($attachable instanceof Student) {
            return $this->teacherTeachesStudent($teacher, $attachable);
        }

        return false;
    }

    private function parentAllows(User $actor, SecureFile $file, mixed $attachable, string $action): bool
    {
        if (! in_array($action, [self::ACTION_VIEW, self::ACTION_DOWNLOAD], true)) {
            return false;
        }

        if ($file->category !== 'profile_photo') {
            return false;
        }

        $parent = $actor->parentProfile;
        if ($parent === null || ! $attachable instanceof Student) {
            return false;
        }

        return $parent->students()->where('students.id', $attachable->id)->exists();
    }

    private function studentAllows(User $actor, SecureFile $file, mixed $attachable, string $action): bool
    {
        if (! in_array($action, [self::ACTION_VIEW, self::ACTION_DOWNLOAD], true)) {
            return false;
        }

        if ($file->category !== 'profile_photo') {
            return false;
        }

        $student = $actor->studentProfile;
        if ($student === null || ! $attachable instanceof Student) {
            return false;
        }

        return (int) $student->id === (int) $attachable->id;
    }

    private function teacherTeachesStudent(Teacher $teacher, Student $student): bool
    {
        $classIds = DB::table('class_lesson_teacher')
            ->join('class_lessons', 'class_lessons.id', '=', 'class_lesson_teacher.class_lesson_id')
            ->where('class_lesson_teacher.teacher_id', $teacher->id)
            ->pluck('class_lessons.academy_class_id');

        if ($classIds->isNotEmpty()
            && DB::table('academy_class_student')
                ->where('student_id', $student->id)
                ->whereIn('academy_class_id', $classIds)
                ->exists()) {
            return true;
        }

        $scheduledClassIds = DB::table('scheduled_lessons')
            ->where('teacher_id', $teacher->id)
            ->pluck('academy_class_id');

        return $scheduledClassIds->isNotEmpty()
            && DB::table('academy_class_student')
                ->where('student_id', $student->id)
                ->whereIn('academy_class_id', $scheduledClassIds)
                ->exists();
    }
}
