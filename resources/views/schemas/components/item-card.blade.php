@php
    $heading = $getHeading();
    $description = $getDescription();
    $icon = $getIcon();
    $image = $getImage();
    $imageShape = $getImageShape();
    $imageAlt = $getImageAlt();
    $variant = $getVariant();
    $context = $getContext();
    $isPressable = $isPressable();
    $url = $getUrl();
    $pressableAction = $isPressable ? $getPressableAction() : null;
    $tag = $isPressable && filled($url) ? 'a' : ($isPressable ? 'button' : 'div');

    $classes = [
        'item-card',
        'item-card--'.$variant,
        'item-card--context-'.$context,
        'item-card--pressable' => $isPressable,
    ];

    $attributes = $attributes
        ->merge([
            'id' => $getId(),
        ], escape: false)
        ->merge($getExtraAttributes(), escape: false)
        ->class($classes);

    if ($isPressable && filled($url)) {
        $attributes = $attributes
            ->merge([
                'href' => $url,
                'target' => $shouldOpenUrlInNewTab() ? '_blank' : null,
                'rel' => $shouldOpenUrlInNewTab() ? 'noopener noreferrer' : null,
            ], escape: false);
    }

    if ($isPressable && blank($url)) {
        $attributes = $attributes->merge(['type' => 'button'], escape: false);
    }

    if ($isPressable && blank($url) && filled($pressableAction?->getLivewireClickHandler())) {
        $attributes = $attributes->merge([
            'wire:click' => $pressableAction->getLivewireClickHandler(),
        ], escape: false);
    }

    if ($isPressable) {
        $attributes = $attributes->merge([
            'x-data' => '{ ripple(event) {
                const card = event.currentTarget;
                const circle = document.createElement(\'span\');
                const diameter = Math.max(card.clientWidth, card.clientHeight);

                circle.className = \'item-card__ripple\';
                circle.style.width = `${diameter}px`;
                circle.style.height = `${diameter}px`;
                circle.style.left = `${event.offsetX - (diameter / 2)}px`;
                circle.style.top = `${event.offsetY - (diameter / 2)}px`;

                card.appendChild(circle);

                window.setTimeout(() => circle.remove(), 650);
            } }',
            'x-on:click' => 'ripple($event)',
        ], escape: false);
    }
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'item-card'])

<{{ $tag }} {{ $attributes }} data-slot="item-card">
    @if (filled($image))
        <div
            @class([
                'item-card__image',
                'item-card__image--'.$imageShape,
            ])
            data-slot="item-card-image"
        >
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy" decoding="async" />
        </div>
    @elseif (filled($icon))
        <div class="item-card__icon" data-slot="item-card-icon" aria-hidden="true">
            <x-filament::icon :icon="$icon" />
        </div>
    @endif

    <div class="item-card__content" data-slot="item-card-content">
        @if (filled($heading))
            <span class="item-card__title" data-slot="item-card-title">{{ $heading }}</span>
        @endif

        @if (filled($description))
            <span class="item-card__description" data-slot="item-card-description">{{ $description }}</span>
        @endif
    </div>

    <div class="item-card__action fi-fixed-positioning-context" data-slot="item-card-action">
        {{ $getChildSchema() }}

        @if ($hasChevron())
            <span class="item-card__chevron" data-slot="item-card-chevron" aria-hidden="true">
                <x-filament::icon :icon="$getChevronIcon()" />
            </span>
        @endif
    </div>
</{{ $tag }}>
