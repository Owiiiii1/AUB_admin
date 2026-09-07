<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LessonsSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private array $subjectsByDiscipline = [
        'ballet' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
        'tam' => [
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
            'LEZIONE IMPROVVISAZIONE IACOBONE',
            'LEZIONE TECNICA LABAN-FORSYTHE',
            'LEZIONE IMPROVVISAZIONE LUNELLA',
            'LEZIONE LABORATORIO LUNELLA',
        ],
        'carcano' => [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE SBARRA A TERRA',
            'LEZIONE DANZE STORICHE',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE CARATTERE',
            'LEZIONE PASSO A DUE',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $teacherIds = Teacher::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            foreach ($this->subjectsByDiscipline as $discipline => $subjects) {
                $validNames = [];

                foreach ($subjects as $index => $name) {
                    $validNames[] = $name;

                    $lesson = Lesson::query()->updateOrCreate(
                        ['discipline' => $discipline, 'name' => $name],
                        ['sort_order' => $index + 1],
                    );

                    if ($teacherIds !== []) {
                        $teacherCount = count($teacherIds);
                        $assignedTeacherIds = [];

                        for ($offset = 0; $offset < min(2, $teacherCount); $offset++) {
                            $assignedTeacherIds[] = $teacherIds[($index + $offset) % $teacherCount];
                        }

                        $lesson->teachers()->sync(array_values(array_unique($assignedTeacherIds)));
                    }
                }

                Lesson::query()
                    ->where('discipline', $discipline)
                    ->whereNotIn('name', $validNames)
                    ->get()
                    ->each(static fn (Lesson $lesson): bool => $lesson->delete());
            }
        });
    }
}
