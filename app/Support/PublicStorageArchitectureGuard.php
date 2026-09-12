<?php

namespace App\Support;

class PublicStorageArchitectureGuard
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        "disk('public')",
        'disk("public")',
        'storePublicly',
        'storePubliclyAs',
        'Storage::url',
        '/storage/',
    ];

    /**
     * Relative paths from the application base that may mention public storage.
     *
     * @var list<string>
     */
    private const WHITELIST = [
        'app/Console/Commands/MigrateSecureFilesCommand.php',
        'app/Support/PublicStorageArchitectureGuard.php',
    ];

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $whitelist
     * @return list<array{file: string, line: int, snippet: string}>
     */
    public function scan(string $basePath, array $roots = ['app', 'resources/js', 'routes', 'database/seeders'], array $whitelist = self::WHITELIST): array
    {
        $hits = [];
        $base = rtrim($basePath, '/\\');

        foreach ($roots as $root) {
            $absolute = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $root);
            if (! is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (! in_array($extension, ['php', 'js', 'jsx', 'ts', 'tsx'], true)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                if (in_array($relative, $whitelist, true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                if (! is_string($contents)) {
                    continue;
                }

                foreach (preg_split("/\r\n|\n|\r/", $contents) as $index => $line) {
                    foreach (self::FORBIDDEN as $needle) {
                        if (str_contains($line, $needle)) {
                            $hits[] = [
                                'file' => $relative,
                                'line' => $index + 1,
                                'snippet' => trim($line),
                            ];
                        }
                    }
                }
            }
        }

        return $hits;
    }
}
