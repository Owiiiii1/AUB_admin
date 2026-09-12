<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Teacher;
use App\Services\SecureFiles\SecureFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSecureFilesCommand extends Command
{
    protected $signature = 'aub:secure-files:migrate
                            {--dry-run : Report planned work without writing}
                            {--purge-legacy : Delete quarantined legacy files after a successful copy}';

    protected $description = 'Copy legacy public student/teacher files onto the private aub_private disk.';

    public function handle(SecureFileService $files): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $purgeLegacy = (bool) $this->option('purge-legacy');

        $summary = [
            'db_references' => 0,
            'physical_found' => 0,
            'missing' => 0,
            'migrated' => 0,
            'skipped_existing' => 0,
            'conflicts' => 0,
            'quarantined' => 0,
            'categories' => [],
        ];

        foreach ($this->legacyTargets() as $target) {
            $summary['db_references']++;
            $summary['categories'][$target['category']] = ($summary['categories'][$target['category']] ?? 0) + 1;

            $disk = Storage::disk('public');
            $exists = is_string($target['path']) && $target['path'] !== '' && $disk->exists($target['path']);

            if (! $exists) {
                $summary['missing']++;
                $this->warn("Missing {$target['label']}: {$target['path']}");
                continue;
            }

            $summary['physical_found']++;
            $existing = $target['model']->secureFile($target['category']);
            if ($existing !== null) {
                $summary['skipped_existing']++;
                if (! $dryRun) {
                    $this->quarantine($target['path'], $purgeLegacy);
                    $this->clearLegacyColumn($target);
                    $summary['quarantined']++;
                }
                continue;
            }

            if ($dryRun) {
                $this->line("Would migrate {$target['label']} → {$target['category']}");
                continue;
            }

            $binary = $disk->get($target['path']);
            if (! is_string($binary) || $binary === '') {
                $summary['missing']++;
                $this->warn("Empty {$target['label']}: {$target['path']}");
                continue;
            }

            try {
                $files->importExisting(
                    $target['model'],
                    $target['category'],
                    $binary,
                    basename($target['path']),
                );
                $this->quarantine($target['path'], $purgeLegacy);
                $this->clearLegacyColumn($target);
                $summary['migrated']++;
                $this->info("Migrated {$target['label']}");
            } catch (\Throwable $e) {
                $summary['conflicts']++;
                $this->error("Failed {$target['label']}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run (no writes).' : 'Migration finished.');
        $this->table(
            ['Metric', 'Count'],
            collect($summary)
                ->except('categories')
                ->map(fn ($value, $key) => [$key, $value])
                ->values()
                ->all()
        );
        $this->line('Planned categories: '.json_encode($summary['categories']));

        return self::SUCCESS;
    }

    /**
     * @return list<array{model: Student|Teacher, category: string, column: string, path: string|null, label: string}>
     */
    private function legacyTargets(): array
    {
        $targets = [];

        foreach (Student::query()->orderBy('id')->get() as $student) {
            foreach (config('aub-files.legacy_map.student', []) as $column => $category) {
                $path = $student->{$column};
                if (! is_string($path) || trim($path) === '') {
                    continue;
                }
                $targets[] = [
                    'model' => $student,
                    'category' => $category,
                    'column' => $column,
                    'path' => $path,
                    'label' => "student#{$student->id}.{$column}",
                ];
            }
        }

        foreach (Teacher::query()->orderBy('id')->get() as $teacher) {
            foreach (config('aub-files.legacy_map.teacher', []) as $column => $category) {
                $path = $teacher->{$column};
                if (! is_string($path) || trim($path) === '') {
                    continue;
                }
                $targets[] = [
                    'model' => $teacher,
                    'category' => $category,
                    'column' => $column,
                    'path' => $path,
                    'label' => "teacher#{$teacher->id}.{$column}",
                ];
            }
        }

        return $targets;
    }

    /**
     * @param  array{path: string}  $target
     */
    private function quarantine(string $path, bool $purge): void
    {
        $public = Storage::disk('public');
        if (! $public->exists($path)) {
            return;
        }

        $contents = $public->get($path);
        if (is_string($contents) && $contents !== '') {
            Storage::disk((string) config('aub-files.legacy_quarantine_disk', 'aub_legacy_quarantine'))
                ->put($path, $contents);
        }

        $public->delete($path);

        if ($purge) {
            Storage::disk((string) config('aub-files.legacy_quarantine_disk', 'aub_legacy_quarantine'))->delete($path);
        }
    }

    /**
     * @param  array{model: Student|Teacher, column: string}  $target
     */
    private function clearLegacyColumn(array $target): void
    {
        $target['model']->forceFill([$target['column'] => null])->save();
    }
}
