@once
    <script>
        const clearIconHtml = @js($clearIconHtml);
        const tagRemoveIconHtml = @js($tagRemoveIconHtml);

        window.__fffHideInitialTriggerSsr = (select) => {
            const ssr = select?.selectButton
                ?.closest('.fi-select-input')
                ?.querySelector('.fff-select-trigger-ssr');

            if (ssr) {
                ssr.classList.add('is-replaced');
            }
        };

        const hideInitialTriggerSsr = window.__fffHideInitialTriggerSsr;

        @include('filament-flex-fields::forms.components.partials.user-select-client-patches')

        @include('filament-flex-fields::forms.components.partials.user-select-trigger-patches')
    </script>
@endonce
