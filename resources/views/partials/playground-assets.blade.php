@if (count($playgroundBundles = \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::playgroundNavigateStylesheetMap()) > 0)
    <script>
        const fffPlaygroundBundles = @json($playgroundBundles);

        const fffEnsurePlaygroundBundle = (slug) => {
            const href = fffPlaygroundBundles[slug] ?? null;

            if (! href) {
                return Promise.resolve();
            }

            const existing = document.querySelector('link[data-fff-playground-bundle]');

            if (existing?.href === href) {
                return Promise.resolve();
            }

            if (document.querySelector(`link[rel="stylesheet"][href="${href}"]`)) {
                if (existing) {
                    existing.remove();
                }

                return Promise.resolve();
            }

            return new Promise((resolve) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                link.setAttribute('data-navigate-track', '');
                link.setAttribute('data-fff-playground-bundle', '');
                link.setAttribute('data-fff-playground-slug', slug);
                link.addEventListener('load', resolve, { once: true });
                link.addEventListener('error', resolve, { once: true });

                if (existing) {
                    existing.replaceWith(link);
                } else {
                    document.head.appendChild(link);
                }
            });
        };

        document.addEventListener('livewire:navigating', (event) => {
            const destination = event.detail?.destination?.url ?? event.detail?.url ?? '';
            const match = destination.match(/flex-fields-playground\/([^/]+)/);

            if (! match) {
                return;
            }

            void fffEnsurePlaygroundBundle(match[1]);
        });
    </script>
@endif
