@php
    $initialUserSelectDisplay = $field->isMultiple() ? $field->getInitialUserSelectDisplay() : null;
    $initialMultipleTriggerHtml = $initialUserSelectDisplay['triggerHtml'] ?? null;
    $userSelectTagsHtml = $initialUserSelectDisplay['tagsHtml'] ?? null;
    $initialSelectedUserEntriesForJs = $field->getInitialSelectedUserEntriesForJs();
@endphp

@include('filament-flex-fields::forms.components.select-field')
