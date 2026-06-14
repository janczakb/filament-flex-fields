<style>
    /* Loaded after Filament / tippy theme so glass tooltips win the cascade. */
    .tippy-box[data-theme~='light'] {
        --fff-tooltip-bg: #ffffffa3;
        --fff-tooltip-border: rgb(228 228 231 / 0.65);
        --fff-tooltip-text: rgb(24 24 27);
        --fff-tooltip-blur: blur(16px) saturate(180%);
        --fff-tooltip-shadow:
            0 4px 6px -1px rgb(0 0 0 / 0.06),
            0 12px 28px -6px rgb(0 0 0 / 0.12);
    }

    .tippy-box[data-theme~='dark'] {
        --fff-tooltip-bg: #27272a3d;
        --fff-tooltip-border: rgb(255 255 255 / 0.12);
        --fff-tooltip-text: rgb(244 244 245);
        --fff-tooltip-blur: blur(16px) saturate(180%);
        --fff-tooltip-shadow:
            0 4px 6px -1px rgb(0 0 0 / 0.28),
            0 12px 28px -6px rgb(0 0 0 / 0.5);
    }

    .tippy-box[data-theme~='light'],
    .tippy-box[data-theme~='dark'] {
        border: 1px solid var(--fff-tooltip-border) !important;
        border-radius: 0.625rem !important;
        background: var(--fff-tooltip-bg) !important;
        background-color: var(--fff-tooltip-bg) !important;
        backdrop-filter: var(--fff-tooltip-blur) !important;
        -webkit-backdrop-filter: var(--fff-tooltip-blur) !important;
        box-shadow: var(--fff-tooltip-shadow) !important;
        color: var(--fff-tooltip-text) !important;
    }

    .tippy-box[data-theme~='light'] .tippy-content,
    .tippy-box[data-theme~='dark'] .tippy-content {
        padding: 0.4375rem 0.625rem !important;
        background: transparent !important;
        background-color: transparent !important;
        font-size: 0.75rem !important;
        line-height: 1.4 !important;
        color: var(--fff-tooltip-text) !important;
    }

    .tippy-box[data-theme~='light'] > .tippy-backdrop,
    .tippy-box[data-theme~='dark'] > .tippy-backdrop {
        background-color: transparent !important;
    }
</style>
