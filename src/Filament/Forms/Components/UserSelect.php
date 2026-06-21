<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresUserSelectSearch;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect\ConfiguresUserSelectColumns;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect\ConfiguresUserSelectModel;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect\HasUserSelectCollaborators;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect\InteractsWithUserSelectPresentation;

class UserSelect extends SelectField
{
    use ConfiguresUserSelectColumns;
    use ConfiguresUserSelectModel;
    use ConfiguresUserSelectSearch;
    use HasUserSelectCollaborators;
    use InteractsWithUserSelectPresentation;

    protected string $view = 'filament-flex-fields::forms.components.user-select';

    protected function setUp(): void
    {
        parent::setUp();

        $this->richOptions();
        $this->allowHtml();

        $this->getOptionLabelsUsing(function (UserSelect $component): array {
            if (! $component->isMultiple()) {
                return [];
            }

            $state = $component->getState();

            if (! is_array($state)) {
                return [];
            }

            return $component->resolveOptionLabelsForValues($state);
        });

        $this->configureModelBindingsIfNeeded();
    }
}
