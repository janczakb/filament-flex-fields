<div class="fff-user-column-playground mb-4 max-w-2xl space-y-2">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Real <code>Action::make(...)->holdConfirm()</code> triggers below — press and hold (or hold Space while focused)
        until the fill completes. Default bulk duration is <strong>{{ $bulkHoldMs }}ms</strong>
        (<code>HoldConfirmEnterprise::bulkHoldMs()</code>).
        Cancel with <kbd>{{ $keyboard['cancel'] }}</kbd>.
        Audit reason required globally:
        <strong>{{ $requiresAuditReason ? 'yes' : 'no' }}</strong>.
    </p>
</div>
