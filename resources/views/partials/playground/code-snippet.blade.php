@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;

    /** @var list<array{label: string, language: string, code: string, highlighted: \Illuminate\Support\HtmlString}> $tabs */
    /** @var string $filename */
    /** @var bool $hasTabs */
    $initial = $tabs[0] ?? null;

    $tabsForJs = collect($tabs ?? [])
        ->map(static fn (array $tab): array => [
            'label' => $tab['label'],
            'language' => $tab['language'],
            'code' => $tab['code'],
        ])
        ->values()
        ->all();
@endphp

@if ($initial)
    <div
        class="fff-code-snippet"
        x-data="{ copied: false, active: 0, tabs: {{ \Illuminate\Support\Js::from($tabsForJs) }} }"
    >
        <div class="fff-code-snippet__chrome">
            <span class="fff-code-snippet__path">{{ $filename }}</span>

            <button
                type="button"
                class="fff-code-snippet__copy"
                x-on:click="
                    navigator.clipboard.writeText(tabs[active]?.code ?? '').then(() => {
                        copied = true
                        setTimeout(() => { copied = false }, 1500)
                    })
                "
                x-bind:aria-label="copied ? 'Copied' : 'Copy code'"
                x-bind:title="copied ? 'Copied' : 'Copy'"
            >
                <span x-show="! copied" class="fff-code-snippet__icon-wrap" aria-hidden="true">
                    <x-filament::icon
                        :icon="GravityIcon::Copy"
                        class="fff-code-snippet__icon"
                    />
                </span>
                <span x-cloak x-show="copied" class="fff-code-snippet__icon-wrap" aria-hidden="true">
                    <x-filament::icon
                        :icon="GravityIcon::Check"
                        class="fff-code-snippet__icon"
                    />
                </span>
            </button>
        </div>

        @if ($hasTabs)
            <div class="fff-code-snippet__tabs" role="tablist" aria-label="Code variants">
                @foreach ($tabs as $index => $tab)
                    <button
                        type="button"
                        role="tab"
                        class="fff-code-snippet__tab"
                        x-bind:class="active === {{ $index }} ? 'is-active' : ''"
                        x-bind:aria-selected="active === {{ $index }}"
                        x-on:click="active = {{ $index }}"
                    >{{ $tab['label'] }}</button>
                @endforeach
            </div>
        @endif

        <div class="fff-code-snippet__body">
            @foreach ($tabs as $index => $tab)
                <pre
                    class="fff-code-snippet__pre"
                    data-language="{{ $tab['language'] }}"
                    x-show="active === {{ $index }}"
                    @if ($index > 0) x-cloak @endif
                ><code>{!! $tab['highlighted'] !!}</code></pre>
            @endforeach
        </div>
    </div>
@endif
