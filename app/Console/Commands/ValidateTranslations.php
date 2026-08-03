<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ValidateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate translation files against English source of truth';

    /**
     * The base language directory path.
     *
     * @var string
     */
    protected $langPath;

    /**
     * The source language code.
     *
     * @var string
     */
    protected $sourceLanguage = 'en';

    /**
     * Statistics for the validation report.
     *
     * @var array
     */
    protected $stats = [
        'total_languages' => 0,
        'total_files_checked' => 0,
        'missing_files' => 0,
        'missing_keys' => 0,
        'extra_keys' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->langPath = lang_path();

        if (! File::exists($this->langPath)) {
            $this->error("Language directory not found: {$this->langPath}");
            return 1;
        }

        $this->info('=== Translation Validation Report ===');
        $this->newLine();

        $sourcePath = $this->langPath.'/'.$this->sourceLanguage;
        if (! File::exists($sourcePath)) {
            $this->error("Source language directory not found: {$sourcePath}");
            return 1;
        }

        // Get all language directories except source
        $languages = File::directories($this->langPath);
        $languages = array_filter($languages, function ($path) {
            return basename($path) !== $this->sourceLanguage;
        });

        $this->stats['total_languages'] = count($languages);

        $hasIssues = false;

        foreach ($languages as $languagePath) {
            $language = basename($languagePath);
            $this->validateLanguage($language, $languagePath, $sourcePath);

            if ($this->stats['missing_files'] > 0 || $this->stats['missing_keys'] > 0) {
                $hasIssues = true;
            }
        }

        $this->printSummary();

        return $hasIssues ? 1 : 0;
    }

    /**
     * Validate a single language directory.
     *
     * @param  string  $language
     * @param  string  $languagePath
     * @param  string  $sourcePath
     */
    protected function validateLanguage($language, $languagePath, $sourcePath)
    {
        $this->info("Language: {$language}");
        $this->newLine();

        // Get all PHP files in source directory
        $sourceFiles = File::files($sourcePath);
        $sourceFiles = array_filter($sourceFiles, function ($file) {
            return $file->getExtension() === 'php';
        });

        $missingFiles = [];
        $missingKeysReport = [];
        $extraKeysReport = [];

        foreach ($sourceFiles as $sourceFile) {
            $fileName = $sourceFile->getFilename();
            $targetFile = $languagePath.'/'.$fileName;

            $this->stats['total_files_checked']++;

            if (! File::exists($targetFile)) {
                $missingFiles[] = $fileName;
                $this->stats['missing_files']++;
                continue;
            }

            // Load source and target translation arrays
            $sourceTranslations = $this->loadTranslationFile($sourceFile->getPathname());
            $targetTranslations = $this->loadTranslationFile($targetFile);

            // Flatten keys using dot notation
            $sourceKeys = $this->flattenKeys($sourceTranslations);
            $targetKeys = $this->flattenKeys($targetTranslations);

            // Find missing keys
            $missing = array_diff(array_keys($sourceKeys), array_keys($targetKeys));
            if (! empty($missing)) {
                $missingKeysReport[$fileName] = $missing;
                $this->stats['missing_keys'] += count($missing);
            }

            // Find extra keys
            $extra = array_diff(array_keys($targetKeys), array_keys($sourceKeys));
            if (! empty($extra)) {
                $extraKeysReport[$fileName] = $extra;
                $this->stats['extra_keys'] += count($extra);
            }
        }

        // Report missing files
        if (! empty($missingFiles)) {
            $this->warn('Missing translation files:');
            foreach ($missingFiles as $file) {
                $this->line("  * {$file}");
            }
            $this->newLine();
        }

        // Report missing keys
        if (! empty($missingKeysReport)) {
            $this->warn('Missing translation keys:');
            foreach ($missingKeysReport as $file => $keys) {
                $this->line("File: {$file}");
                foreach ($keys as $key) {
                    $this->line("  * {$key}");
                }
                $this->newLine();
            }
        }

        // Report extra keys
        if (! empty($extraKeysReport)) {
            $this->comment('Extra keys not found in English:');
            foreach ($extraKeysReport as $file => $keys) {
                $this->line("File: {$file}");
                foreach ($keys as $key) {
                    $this->line("  * {$key}");
                }
                $this->newLine();
            }
        }

        if (empty($missingFiles) && empty($missingKeysReport) && empty($extraKeysReport)) {
            $this->info('✓ All translation files are in sync.');
        }

        $this->newLine();
    }

    /**
     * Load a PHP translation file.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function loadTranslationFile($filePath)
    {
        $translations = include $filePath;

        return is_array($translations) ? $translations : [];
    }

    /**
     * Flatten nested array keys using dot notation.
     *
     * @param  array  $array
     * @param  string  $prefix
     * @return array
     */
    protected function flattenKeys(array $array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix.'.'.$key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenKeys($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Print the validation summary.
     */
    protected function printSummary()
    {
        $this->info('=== Summary ===');
        $this->line("Total languages checked: {$this->stats['total_languages']}");
        $this->line("Total files checked: {$this->stats['total_files_checked']}");
        $this->line("Total missing files: {$this->stats['missing_files']}");
        $this->line("Total missing keys: {$this->stats['missing_keys']}");
        $this->line("Total extra keys: {$this->stats['extra_keys']}");
        $this->newLine();

        if ($this->stats['missing_files'] > 0 || $this->stats['missing_keys'] > 0) {
            $this->error('❌ Validation failed: Missing translations detected.');
        } else {
            $this->info('✅ Validation passed: All translation files are in sync.');
        }
    }
}
