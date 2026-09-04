<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;

final class TodoListFieldAudio
{
    public static function url(string $file): string
    {
        $file = basename($file);
        $publicRelative = 'js/'.FilamentFlexFieldsPlugin::PACKAGE_NAME.'/audio/todo-list-field/'.$file;

        if (is_file(public_path($publicRelative))) {
            return asset($publicRelative);
        }

        $altPublic = 'vendor/janczakb/filament-flex-fields/audio/todo-list-field/'.$file;

        if (is_file(public_path($altPublic))) {
            return asset($altPublic);
        }

        $packageFile = dirname(__DIR__).'/../resources/dist/audio/todo-list-field/'.$file;

        if (is_file($packageFile)) {
            return 'data:audio/mpeg;base64,'.base64_encode((string) file_get_contents($packageFile));
        }

        return asset($publicRelative);
    }
}
