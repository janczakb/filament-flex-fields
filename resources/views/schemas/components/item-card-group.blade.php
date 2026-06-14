@php
    $heading = $getHeading();
    $description = $getDescription();
    $layout = $getLayout();
    $variant = $getVariant();
    $headerStyle = $getHeaderStyle();
    $isDivided = $isDivided();
    $isOutsideHeader = $headerStyle === 'outside';
    $boxClasses = [
        'item-card-group',
        'item-card-group--'.$layout,
        'item-card-group--'.$variant,
        'item-card-group--divided' => $isDivided,
    ];
@endphp

@if ($isOutsideHeader)
    <div
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                    'role' => 'group',
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'item-card-group-host',
                    'item-card-group--'.$variant,
                ])
        }}
        data-slot="item-card-group"
    >
        @if (filled($heading) || filled($description))
            <div class="item-card-group__header" data-slot="item-card-group-header">
                @if (filled($heading))
                    <h3 class="item-card-group__title" data-slot="item-card-group-title">
                        {{ $heading }}
                    </h3>
                @endif

                @if (filled($description))
                    <p class="item-card-group__description" data-slot="item-card-group-description">
                        {{ $description }}
                    </p>
                @endif
            </div>
        @endif

        <div @class($boxClasses) data-slot="item-card-group-surface">
            <div class="item-card-group__content" data-slot="item-card-group-content">
                {{ $getChildSchema() }}
            </div>
        </div>
    </div>
@else
    <div
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                    'role' => 'group',
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
                ->class($boxClasses)
        }}
        data-slot="item-card-group"
    >
        @if (filled($heading) || filled($description))
            <div class="item-card-group__header" data-slot="item-card-group-header">
                @if (filled($heading))
                    <h3 class="item-card-group__title" data-slot="item-card-group-title">
                        {{ $heading }}
                    </h3>
                @endif

                @if (filled($description))
                    <p class="item-card-group__description" data-slot="item-card-group-description">
                        {{ $description }}
                    </p>
                @endif
            </div>
        @endif

        <div class="item-card-group__content" data-slot="item-card-group-content">
            {{ $getChildSchema() }}
        </div>
    </div>
@endif
