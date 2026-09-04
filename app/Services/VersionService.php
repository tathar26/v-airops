<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class VersionService
{
    /**
     * Get the current release version / Git repository tag.
     */
    public static function getVersion(): string
    {
        return Cache::remember('app_git_version', 300, function () {
            // 1. Explicit environment variable if set
            if ($envVer = env('APP_VERSION')) {
                return $envVer;
            }

            // 2. Static version.txt created during build/deploy
            $verFile = base_path('version.txt');
            if (file_exists($verFile)) {
                $content = trim((string) @file_get_contents($verFile));
                if (!empty($content)) {
                    return $content;
                }
            }

            // 3. Query Git tag directly from the repository
            try {
                $basePath = base_path();
                $output = @shell_exec("git -C \"{$basePath}\" describe --tags --always 2>/dev/null");
                if ($output) {
                    $tag = trim($output);
                    if (!empty($tag)) {
                        return $tag;
                    }
                }
            } catch (\Throwable $e) {
                // Silently fallback if shell_exec is disabled on host
            }

            // 4. Fallback
            return config('app.version', 'v1.1.9');
        });
    }
}
