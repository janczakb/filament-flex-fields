<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

class FileUploadMimePresets
{
    /**
     * @return list<string>
     */
    public static function documents(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/rtf',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];
    }

    /**
     * @return list<string>
     */
    public static function images(): array
    {
        return [
            'image/*',
        ];
    }

    /**
     * @return list<string>
     */
    public static function spreadsheets(): array
    {
        return [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];
    }
}
