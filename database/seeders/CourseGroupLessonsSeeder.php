<?php

namespace Database\Seeders;

use App\Models\CourseGroup;
use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseGroupLessonsSeeder extends Seeder
{
    private const WEEKLY_HOURS_TARGET = 20;

    /**
     * @var array<string, list<string>>
     */
    private array $academyByPattern = [
        '/^1 corso femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
        ],
        '/^2 corso femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
        ],
        '/^3 corso [AB] femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
        ],
        '/^4 corso [AB] femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE CONTEMPORANEO',
        ],
        '/^5 corso [AB] femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
        ],
        '/^6 corso [AB] femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        '/^7 corso [AB] femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        '/^8 corso femminile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        '/^4-5 \(unito\) corso maschile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
        ],
        '/^6-7-8 \(unito\) corso maschile$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $tamByPattern = [
        '/^1 anno$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE GRAHAM',
            'LEZIONE PHYSICAL THEATER',
            'LEZIONE CONTEMPORANEO IACOBONE',
            'LEZIONE CONTEMPORANEO MAIER',
            'LEZIONE CONTEMPORANEO ERIKA',
            'LEZIONE MODERN',
            'LEZIONE ACRO FLOORWORK & ACROBATICA',
            'LEZIONE HIP-HOP',
            'LEZIONE BODY PERCUSSION',
        ],
        '/^2 anno$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE GRAHAM',
            'LEZIONE MODERN',
            'LEZIONE IMPROVVISAZIONE IACOBONE',
            'LEZIONE PHYSICAL THEATER',
            'LEZIONE ACRO FLOORWORK & ACROBATICA',
            'LEZIONE CONTEMPORANEO IACOBONE',
            'LEZIONE CONTEMPORANEO MAIER',
            'LEZIONE CONTEMPORANEO ERIKA',
            'LEZIONE BODY PERCUSSION',
            'LEZIONE HIP-HOP',
            'LEZIONE TECNICA LABAN-FORSYTHE',
        ],
        '/^3 anno$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE GRAHAM',
            'LEZIONE MODERN',
            'LEZIONE IMPROVVISAZIONE IACOBONE',
            'LEZIONE IMPROVVISAZIONE LUNELLA',
            'LEZIONE LABORATORIO LUNELLA',
            'LEZIONE CONTEMPORANEO IACOBONE',
            'LEZIONE CONTEMPORANEO MAIER',
            'LEZIONE CONTEMPORANEO ERIKA',
            'LEZIONE PILATES',
            'LEZIONE HIP-HOP',
            'LEZIONE TECNICA LABAN-FORSYTHE',
            'LEZIONE PHYSICAL THEATER',
            'LEZIONE ACRO FLOORWORK & ACROBATICA',
            'LEZIONE BODY PERCUSSION',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $carcanoByPattern = [
        '/^propedeutica 1$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
        ],
        '/^propedeutica 2$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
        ],
        '/^propedeutica 3$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
        ],
        '/^1 corso$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
        ],
        '/^2-3 corso$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
        ],
        '/^4-5 corso$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE CONTEMPORANEO',
        ],
        '/^6 corso$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        '/^7-8 corso$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        '/^intermedio$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
        ],
        '/^avanz classico$/i' => [
            'LEZIONE CLASSICO',
            'LEZIONE PASSO A DUE',
        ],
        '/^avanz moderno$/i' => [
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PILATES',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            DB::table('course_group_lesson')->delete();

            $fallbackTeacherId = (int) Teacher::query()->orderBy('id')->value('id');
            $academyLessons = $this->lessonMap('ballet');
            $tamLessons = $this->lessonMap('tam');
            $carcanoLessons = $this->lessonMap('carcano');

            CourseGroup::query()
                ->with('course')
                ->get()
                ->each(function (CourseGroup $group) use ($academyLessons, $tamLessons, $carcanoLessons, $fallbackTeacherId): void {
                    $discipline = (string) $group->course?->discipline;
                    $groupName = (string) $group->name;

                    [$subjects, $lessonMap] = match ($discipline) {
                        'academy' => [$this->subjectsForGroup($groupName, $this->academyByPattern), $academyLessons],
                        'tam' => [$this->subjectsForGroup($groupName, $this->tamByPattern), $tamLessons],
                        'carcano' => [$this->subjectsForGroup($groupName, $this->carcanoByPattern), $carcanoLessons],
                        default => [[], []],
                    };

                    if ($subjects === []) {
                        return;
                    }

                    $hoursBySubject = $this->distributeWeeklyHours(self::WEEKLY_HOURS_TARGET, count($subjects));

                    foreach ($subjects as $index => $subjectName) {
                        $lesson = $lessonMap[$subjectName] ?? null;
                        if ($lesson === null) {
                            continue;
                        }

                        $teacherId = (int) ($lesson->teachers()->orderBy('teachers.id')->value('teachers.id') ?: $fallbackTeacherId);
                        if ($teacherId <= 0) {
                            continue;
                        }

                        DB::table('course_group_lesson')->insert([
                            'course_group_id' => $group->id,
                            'lesson_id' => $lesson->id,
                            'teacher_id' => $teacherId,
                            'hours' => $hoursBySubject[$index],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        });
    }

    /**
     * @return list<int>
     */
    private function distributeWeeklyHours(int $totalHours, int $subjectCount): array
    {
        if ($subjectCount <= 0) {
            return [];
        }

        $baseHours = intdiv($totalHours, $subjectCount);
        $extraHours = $totalHours % $subjectCount;

        $hours = array_fill(0, $subjectCount, $baseHours);

        for ($index = 0; $index < $extraHours; $index++) {
            $hours[$index]++;
        }

        return $hours;
    }

    /**
     * @param  array<string, list<string>>  $patterns
     * @return list<string>
     */
    private function subjectsForGroup(string $groupName, array $patterns): array
    {
        foreach ($patterns as $pattern => $subjects) {
            if (preg_match($pattern, $groupName) === 1) {
                return $subjects;
            }
        }

        return [];
    }

    /**
     * @return array<string, Lesson>
     */
    private function lessonMap(string $discipline): array
    {
        $map = [];

        Lesson::query()
            ->where('discipline', $discipline)
            ->orderBy('sort_order')
            ->get()
            ->each(function (Lesson $lesson) use (&$map): void {
                $map[$lesson->name] = $lesson;
            });

        return $map;
    }
}
