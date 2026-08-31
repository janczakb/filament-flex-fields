<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class MakeFlexFieldSchemaMigrationCommand extends Command
{
    protected $signature = 'make:flex-field-schema-migration {name : The migration name}';

    protected $description = 'Create a migration stub for preset flex field schema groups';

    public function handle(): int
    {
        $name = Str::snake(trim($this->argument('name')));
        $path = database_path('flex-fields/'.now()->format('Y_m_d_His').'_'.$name.'.php');

        File::ensureDirectoryExists(dirname($path));

        File::put($path, str_replace(
            ['{{ class }}'],
            [Str::studly($name)],
            (string) File::get(__DIR__.'/../../stubs/flex-field-schema-migration.stub'),
        ));

        $this->components->info('Flex field schema migration created successfully.');
        $this->line($path);

        return self::SUCCESS;
    }
}
