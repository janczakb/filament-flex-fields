<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Actions\Action as FlexAction;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Tests\Support\FilamentOverrideSignatureGuard;
use Composer\InstalledVersions;
use Filament\Actions\Action as FilamentAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Concerns\HasActions;
use Filament\Schemas\Components\Concerns\HasChildComponents;
use Filament\Schemas\Components\Concerns\HasState;

/**
 * Critical Filament overrides that can fatal on upstream signature changes
 * (see https://github.com/janczakb/filament-flex-fields/issues/61).
 *
 * @return list<array{0: class-string, 1: class-string, 2: string}>
 */
function filamentCriticalOverrides(): array
{
    return [
        [TranslatableFields::class, HasChildComponents::class, 'schema'],
        [TranslatableFields::class, HasChildComponents::class, 'getChildSchema'],
        [TranslatableFields::class, HasState::class, 'fillStateWithNull'],
        [SelectField::class, Select::class, 'getOptions'],
        [SelectField::class, Select::class, 'getOptionsForJs'],
        [SelectField::class, Select::class, 'getOptionLabels'],
        [SelectField::class, Select::class, 'getSearchResults'],
        [SelectField::class, Select::class, 'getSearchResultsForJs'],
        [SelectField::class, Select::class, 'relationship'],
        [SelectField::class, Select::class, 'searchable'],
        [SelectField::class, Select::class, 'multiple'],
        [SelectField::class, Select::class, 'native'],
        [SelectField::class, Select::class, 'allowHtml'],
        [SelectField::class, Select::class, 'selectablePlaceholder'],
        [SelectField::class, Select::class, 'hasDynamicOptions'],
        [SelectField::class, Select::class, 'hasDynamicSearchResults'],
        [UserSelect::class, SelectField::class, 'relationship'],
        [FlexTextInput::class, TextInput::class, 'prefix'],
        [FlexTextInput::class, TextInput::class, 'suffix'],
        [FlexFileUpload::class, FileUpload::class, 'disk'],
        [FlexRichEditor::class, RichEditor::class, 'getTools'],
        [FlexRichEditor::class, RichEditor::class, 'getToolbarButtons'],
        [ItemCard::class, HasActions::class, 'action'],
        [FlexAction::class, FilamentAction::class, 'toButtonHtml'],
    ];
}

it('keeps critical Filament overrides LSP-compatible with the installed parent APIs', function (): void {
    $failures = [];

    foreach (filamentCriticalOverrides() as [$child, $parent, $method]) {
        $parentReflection = new ReflectionClass($parent);
        $childReflection = new ReflectionClass($child);

        if (! $parentReflection->hasMethod($method)) {
            $failures[] = "Parent {$parent}::{$method}() missing — Filament API removed or relocated.";

            continue;
        }

        if (! $childReflection->hasMethod($method)) {
            $failures[] = "Child {$child}::{$method}() missing — override disappeared.";

            continue;
        }

        $issues = FilamentOverrideSignatureGuard::assertCompatible($child, $parent, $method);

        foreach ($issues as $issue) {
            $failures[] = $issue;
        }
    }

    expect($failures)->toBeEmpty(
        "Filament override LSP incompatibilities detected:\n- ".implode("\n- ", $failures),
    );
});

it('reports the installed Filament schemas version for CI matrix diagnostics', function (): void {
    expect(InstalledVersions::isInstalled('filament/schemas'))->toBeTrue();

    $version = InstalledVersions::getPrettyVersion('filament/schemas');

    expect($version)->not->toBeEmpty()
        ->and(version_compare((string) InstalledVersions::getVersion('filament/schemas'), '5.0.0', '>='))->toBeTrue();
});
