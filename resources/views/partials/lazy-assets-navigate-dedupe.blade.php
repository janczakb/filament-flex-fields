<script>
    document.addEventListener('livewire:navigated', () => {
        const seenStylesheets = new Set();
        const seenModulePreloads = new Set();

        document.querySelectorAll('link[rel="stylesheet"][href*="filament-flex-fields"]').forEach((link) => {
            if (seenStylesheets.has(link.href)) {
                link.remove();

                return;
            }

            seenStylesheets.add(link.href);
        });

        document.querySelectorAll('link[rel="modulepreload"][href*="filament-flex-fields"]').forEach((link) => {
            if (seenModulePreloads.has(link.href)) {
                link.remove();

                return;
            }

            seenModulePreloads.add(link.href);
        });
    });
</script>
