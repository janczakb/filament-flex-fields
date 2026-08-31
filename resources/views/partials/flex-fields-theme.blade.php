@php
    use Bjanczak\FilamentFlexFields\Support\Theme\FlexFieldsTheme;

    /** @var FlexFieldsTheme $flexFieldsTheme */
    $flexFieldsTheme = app(FlexFieldsTheme::class);
    $htmlAttributes = $flexFieldsTheme->toHtmlAttributes();
    $cssVariables = $flexFieldsTheme->toCssVariables();
@endphp

@if ($cssVariables !== [])
    <style id="fff-theme-overrides">
        :root {
@foreach ($cssVariables as $name => $value)
            {{ $name }}: {{ $value }};
@endforeach
        }
    </style>
@endif

<script>
    (() => {
        const root = document.documentElement;
        const attributes = @json($htmlAttributes);

        for (const [name, value] of Object.entries(attributes)) {
            root.setAttribute(name, value);
        }
    })();
</script>
