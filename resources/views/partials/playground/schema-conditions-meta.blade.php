<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        <code>SchemaBlueprintPacks::crm()</code> — {{ $crmLabel }} ({{ $crmFieldCount }} fields).
        Checksum: <code>{{ $checksum }}</code>
    </p>

    <p class="text-sm mb-2">
        <span class="font-medium">JsonFieldConditions operators:</span>
        {{ implode(', ', $operators) }}
    </p>

    <pre class="text-xs overflow-x-auto whitespace-pre-wrap rounded-lg bg-gray-50 dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-700">{{ $exportPreview }}…</pre>
</div>
