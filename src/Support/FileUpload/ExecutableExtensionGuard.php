<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Closure;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ExecutableExtensionGuard
{
    /**
     * @var list<string>
     */
    public const BLOCKED_EXTENSIONS = [
        'php',
        'phtml',
        'phar',
        'php3',
        'php4',
        'php5',
        'phps',
        'htaccess',
    ];

    public static function isBlocked(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === '') {
            $basename = strtolower(basename($filename));

            return in_array($basename, self::BLOCKED_EXTENSIONS, true);
        }

        return in_array($extension, self::BLOCKED_EXTENSIONS, true);
    }

    public static function validationRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if ($value instanceof TemporaryUploadedFile) {
                if (self::isBlocked($value->getClientOriginalName())) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.executable_blocked'));
                }

                return;
            }

            if (is_string($value) && self::isBlocked($value)) {
                $fail(__('filament-flex-fields::default.file_upload.validation.executable_blocked'));
            }
        };
    }
}
