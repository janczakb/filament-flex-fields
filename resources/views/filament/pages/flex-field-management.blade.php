<x-filament-panels::page>
    @if ($groups === [])
        <x-filament::section>
            <x-slot name="heading">
                {{ __('filament-flex-fields::default.schema.management_empty_heading') }}
            </x-slot>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-flex-fields::default.schema.management_empty_description') }}
            </p>
        </x-filament::section>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($groups as $group)
                <x-filament::section>
                    <x-slot name="heading">
                        {{ $group->name }}
                    </x-slot>

                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div><strong>{{ __('Slug') }}:</strong> {{ $group->slug }}</div>
                        <div><strong>{{ __('Fields') }}:</strong> {{ is_array($group->fields) ? count($group->fields) : 0 }}</div>
                        <div><strong>{{ __('Sections') }}:</strong> {{ is_array($group->sections) ? count($group->sections) : 0 }}</div>
                    </div>

                    <div class="mt-4">
                        <x-filament::button
                            tag="a"
                            :href="\Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource::getUrl('edit', ['record' => $group])"
                        >
                            {{ __('Edit') }}
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
