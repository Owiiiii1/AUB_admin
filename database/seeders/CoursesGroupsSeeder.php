<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesGroupsSeeder extends Seeder
{
    /**
     * @var array<string, list<array{name:string,groups:list<string>}>>
     */
    private array $courseBlueprintByDiscipline = [
        'academy' => [
            [
                'name' => 'accademia ucraina',
                'groups' => [
                    '1 corso femminile',
                    '2 corso femminile',
                    '3 corso A femminile',
                    '3 corso B femminile',
                    '4 corso A femminile',
                    '4 corso B femminile',
                    '5 corso A femminile',
                    '5 corso B femminile',
                    '6 corso A femminile',
                    '6 corso B femminile',
                    '7 corso A femminile',
                    '7 corso B femminile',
                    '8 corso femminile',
                    '4-5 (unito) corso maschile',
                    '6-7-8 (unito) corso maschile',
                ],
            ],
        ],
        'carcano' => [
            [
                'name' => 'Scuola del Carcano',
                'groups' => [
                    'propedeutica 1',
                    'propedeutica 2',
                    'propedeutica 3',
                    '1 corso',
                    '2-3 corso',
                    '4-5 corso',
                    '6 corso',
                    '7-8 corso',
                    'intermedio',
                    'avanz classico',
                    'avanz moderno',
                ],
            ],
        ],
        'tam' => [
            [
                'name' => 'formazione tam',
                'groups' => [
                    '1 anno',
                    '2 anno',
                    '3 anno',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCoursesAndGroups();
            $this->distributeStudentsEvenly();
        });
    }

    private function seedCoursesAndGroups(): void
    {
        foreach ($this->courseBlueprintByDiscipline as $discipline => $courses) {
            foreach ($courses as $courseIndex => $courseData) {
                $courseName = $courseData['name'];
                $groupNames = $courseData['groups'];

                $course = Course::query()->updateOrCreate(
                    ['discipline' => $discipline, 'name' => $courseName],
                    ['sort_order' => $courseIndex + 1],
                );

                foreach ($groupNames as $groupIndex => $groupName) {
                    CourseGroup::query()->updateOrCreate(
                        ['course_id' => $course->id, 'name' => $groupName],
                        ['sort_order' => $groupIndex + 1],
                    );
                }

                CourseGroup::query()
                    ->where('course_id', $course->id)
                    ->whereNotIn('name', $groupNames)
                    ->get()
                    ->each(static fn (CourseGroup $group): bool => $group->delete());
            }

            $courseNames = array_values(array_map(
                static fn (array $course): string => $course['name'],
                $courses,
            ));

            Course::query()
                ->where('discipline', $discipline)
                ->whereNotIn('name', $courseNames)
                ->get()
                ->each(static fn (Course $course): bool => $course->delete());
        }

        Course::query()
            ->whereNotIn('discipline', array_keys($this->courseBlueprintByDiscipline))
            ->get()
            ->each(static fn (Course $course): bool => $course->delete());
    }

    private function distributeStudentsEvenly(): void
    {
        DB::table('course_group_customer')->delete();

        $groups = CourseGroup::query()
            ->with('course:id,discipline,sort_order')
            ->get()
            ->sortBy([
                static fn (CourseGroup $group): int => match ($group->course->discipline) {
                    'academy' => 0,
                    'tam' => 1,
                    'carcano' => 2,
                    default => 99,
                },
                static fn (CourseGroup $group): int => (int) $group->course->sort_order,
                static fn (CourseGroup $group): int => (int) $group->sort_order,
            ])
            ->values();

        $studentIds = Customer::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $groupCount = $groups->count();

        if ($groupCount === 0 || $studentIds === []) {
            return;
        }

        $baseSize = intdiv(count($studentIds), $groupCount);
        $extraGroups = count($studentIds) % $groupCount;
        $offset = 0;

        foreach ($groups as $index => $group) {
            $size = $baseSize + ($index < $extraGroups ? 1 : 0);
            $chunk = array_slice($studentIds, $offset, $size);
            $offset += $size;

            if ($chunk === []) {
                continue;
            }

            $attachData = collect($chunk)
                ->mapWithKeys(static fn (int $id): array => [
                    $id => ['discipline' => $group->course->discipline],
                ])
                ->all();

            $group->customers()->attach($attachData);
        }
    }
}
