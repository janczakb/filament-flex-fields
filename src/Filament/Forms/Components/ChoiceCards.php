<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasChoiceCardOptions;
use Filament\Forms\Components\Field;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class ChoiceCards extends Field
{
    use HasChoiceCardOptions;

    protected string $view = 'filament-flex-fields::forms.components.choice-cards';

    public function getIndicator(): string
    {
        $indicator = $this->evaluate($this->indicator);

        if (filled($indicator)) {
            return (string) $indicator;
        }

        return match ($this->getLayout()) {
            'media' => 'none',
            'featured' => 'check',
            default => 'radio',
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (ChoiceCards $component): In {
            return Rule::in($component->getOptionKeys());
        });
    }
}
