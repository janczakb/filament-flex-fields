@php
    $stackGap = $getStackGap();
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'item-card'])

<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fff-item-card-stack',
                'fff-item-card-stack--'.$stackGap,
            ])
    }}
    data-slot="item-card-stack"
>
    {{ $getChildSchema() }}
</div>
