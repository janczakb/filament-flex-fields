<span class="fff-user-column fff-user-column--rich">
    @include('filament-flex-fields::forms.components.partials.user-select-option', [
        'label' => $user['label'],
        'description' => $user['description'] ?? null,
        'image' => $user['image'] ?? null,
        'verified' => (bool) ($user['verified'] ?? false),
        'initials' => $user['initials'] ?? '',
        'layout' => 'trigger',
    ])
</span>
