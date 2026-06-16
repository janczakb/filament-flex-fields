<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TestableSocialLinksForm extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var list<\Filament\Schemas\Components\Component> */
    public static array $formSchema = [];

    /** @var array<string, mixed> */
    public array $data = [];

    public mixed $record = null;

    public function mount(mixed $record = null): void
    {
        $this->record = $record instanceof Model ? $record : null;

        if ($this->record !== null) {
            $this->getSchema('form')->fill($this->record->attributesToArray());
        }
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->getSchema('form') }}
                <button type="button" wire:click="save">Save</button>
            </div>
        BLADE;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::$formSchema)
            ->statePath('data')
            ->model($this->record ?? TranslatablePost::class)
            ->operation($this->record !== null ? 'edit' : 'create');
    }

    public function save(): void
    {
        try {
            $this->getSchema('form')->getState();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }
        }
    }

    public function getErrorBag(): MessageBag
    {
        return parent::getErrorBag() ?? new MessageBag;
    }
}
