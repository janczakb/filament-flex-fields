@if (request()->is('*flex-fields-playground*'))
    <script>
        window.FffPlaygroundTheme = (() => {
            const storageKey = 'fff-playground-dark-mode';

            const read = () => localStorage.getItem(storageKey) === 'true';

            const apply = (isDark) => {
                document.documentElement.classList.toggle('dark', isDark);
            };

            const syncFilamentTheme = (isDark) => {
                localStorage.setItem('theme', isDark ? 'dark' : 'light');

                if (typeof Alpine !== 'undefined' && typeof Alpine.store === 'function') {
                    Alpine.store('theme', isDark ? 'dark' : 'light');
                }
            };

            const set = (isDark) => {
                localStorage.setItem(storageKey, isDark ? 'true' : 'false');
                syncFilamentTheme(isDark);
                apply(isDark);
            };

            const toggle = () => {
                set(! read());
            };

            const reset = () => {
                localStorage.setItem(storageKey, 'false');
                syncFilamentTheme(false);
                apply(false);
            };

            const restore = () => {
                if (! read()) {
                    return;
                }

                syncFilamentTheme(true);
                apply(true);
            };

            restore();

            document.addEventListener('alpine:init', restore);

            return {
                isDark: read,
                set,
                toggle,
                reset,
            };
        })();
    </script>
@endif
