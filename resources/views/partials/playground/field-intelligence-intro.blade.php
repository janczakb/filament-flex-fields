<div class="fff-user-column-playground mb-3 max-w-4xl space-y-3">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        These demos call the same always-on safe <code>FormulaEngine</code> used by JSON
        <code>formula</code> / <code>config.calculated</code> fields. Money inputs use
        <code>CurrencyField</code> (minor units) with <code>moneyMajor()</code> /
        <code>moneyMinor()</code> — not plain text inputs.
    </p>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-xs text-gray-500 dark:text-gray-400">
        <div class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2">
            <div class="font-semibold text-gray-700 dark:text-gray-200">Safe arithmetic</div>
            <div class="mt-0.5"><code>+ − × ÷ ( )</code>, <code>{field}</code>, <code>if/and/or</code>, <code>pct/sum/clamp</code>, short-circuit safe</div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2">
            <div class="font-semibold text-gray-700 dark:text-gray-200">evaluateMap()</div>
            <div class="mt-0.5">Acyclic multi-field chains in dependency order</div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2">
            <div class="font-semibold text-gray-700 dark:text-gray-200">Cycle guard</div>
            <div class="mt-0.5"><code>detectCycle()</code> blocks A→B→A graphs</div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2">
            <div class="font-semibold text-gray-700 dark:text-gray-200">Money helpers</div>
            <div class="mt-0.5"><code>moneyMajor()</code> / <code>moneyMinor()</code> for CurrencyField cents</div>
        </div>
    </div>
</div>
