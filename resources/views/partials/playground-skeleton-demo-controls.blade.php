<div class="fff-playground-skeleton-demo">
    <p class="fff-playground-skeleton-demo__title">Asset injector — skeleton demo</p>
    <p class="fff-playground-skeleton-demo__text">
        Skeleton appears only while lazy CSS is still downloading. On this page, hover-preload and browser cache usually load styles before the modal opens.
        Use the steps below to force a visible ~1.8s shimmer (playground-only).
    </p>
    <ol class="fff-playground-skeleton-demo__steps">
        <li>Hard-refresh this page (<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd>).</li>
        <li>Click <strong>1. Enable slow CSS demo</strong> first (required — clears cached flex-field stylesheets).</li>
        <li>Click <strong>2. Open skeleton demo modal</strong> right away — do not hover that button first.</li>
        <li>Skeleton should disappear after ~1.8s and show the form fields.</li>
    </ol>
    <div class="fff-playground-skeleton-demo__actions">
        <button
            type="button"
            class="fff-playground-skeleton-demo__button fff-playground-skeleton-demo__button--primary"
            onclick="window.FffSkeletonDemo?.enable()"
        >
            1. Enable slow CSS demo
        </button>
        <button
            type="button"
            class="fff-playground-skeleton-demo__button"
            onclick="window.FffSkeletonDemo?.disable()"
        >
            Disable demo
        </button>
    </div>
</div>
