{{-- Parent meta chips (sub-stack progress + optional date). Gravity icons only. --}}
<template x-if="hasMeta(item)">
    <div class="fff-todo-list-field__meta" @click.stop>
        <template x-if="hasChildren(item)">
            <span class="fff-todo-list-field__meta-chip" title="{{ __('filament-flex-fields::default.todo_list.subtasks') }}">
                <x-filament::icon
                    :icon="$childrenIcon"
                    class="fff-todo-list-field__meta-icon h-3.5 w-3.5"
                />
                <span x-text="childProgressLabel(item)"></span>
            </span>
        </template>
        <template x-if="item.date">
            <span class="fff-todo-list-field__meta-chip">
                <x-filament::icon
                    :icon="$dateIcon"
                    class="fff-todo-list-field__meta-icon h-3.5 w-3.5"
                />
                <span x-text="item.date"></span>
            </span>
        </template>
    </div>
</template>
