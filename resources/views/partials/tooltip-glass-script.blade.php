<script>
    (function () {
        const themes = {
            light: {
                bg: '#ffffffa3',
                border: 'rgb(228 228 231 / 0.65)',
                blur: 'blur(16px) saturate(180%)',
                shadow: '0 4px 6px -1px rgb(0 0 0 / 0.06), 0 12px 28px -6px rgb(0 0 0 / 0.12)',
                text: 'rgb(24 24 27)',
            },
            dark: {
                bg: '#27272a3d',
                border: 'rgb(255 255 255 / 0.12)',
                blur: 'blur(16px) saturate(180%)',
                shadow: '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)',
                text: 'rgb(244 244 245)',
            },
        };

        const resolveTheme = () => (
            document.documentElement.classList.contains('dark') ? themes.dark : themes.light
        );

        const applyTooltipGlass = (box) => {
            if (! box?.classList?.contains('tippy-box')) {
                return;
            }

            const theme = resolveTheme();

            box.style.setProperty('background', theme.bg, 'important');
            box.style.setProperty('background-color', theme.bg, 'important');
            box.style.setProperty('backdrop-filter', theme.blur, 'important');
            box.style.setProperty('-webkit-backdrop-filter', theme.blur, 'important');
            box.style.setProperty('border', `1px solid ${theme.border}`, 'important');
            box.style.setProperty('box-shadow', theme.shadow, 'important');
            box.style.setProperty('border-radius', '0.625rem', 'important');
            box.style.setProperty('color', theme.text, 'important');

            box.querySelector('.tippy-content')?.style.setProperty('background', 'transparent', 'important');
            box.querySelector('.tippy-content')?.style.setProperty('background-color', 'transparent', 'important');
            box.querySelector('.tippy-content')?.style.setProperty('color', theme.text, 'important');
            box.querySelector('.tippy-backdrop')?.style.setProperty('background-color', 'transparent', 'important');
        };

        const patchTippy = () => {
            if (! window.tippy) {
                return false;
            }

            const defaults = window.tippy.defaultProps ?? {};
            const previousOnShow = defaults.onShow;

            window.tippy.setDefaultProps({
                onShow (instance) {
                    if (typeof previousOnShow === 'function') {
                        previousOnShow(instance);
                    }

                    requestAnimationFrame(() => {
                        applyTooltipGlass(instance.popper?.querySelector?.('.tippy-box') ?? instance.popper);
                    });
                },
            });

            return true;
        };

        if (! patchTippy()) {
            const tryPatch = () => patchTippy() || setTimeout(tryPatch, 50);

            tryPatch();
        }
    })();
</script>
