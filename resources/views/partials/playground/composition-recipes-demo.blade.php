<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        <code>CertifiedRecipes::all()</code> — first-class layout stacks covered by Composition OS tests and docs.
    </p>

    <ul class="space-y-4 text-sm">
        @foreach ($recipes as $recipe)
            <li class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <p class="font-semibold"><code>{{ $recipe['id'] }}</code></p>
                <p class="mt-1 text-gray-600 dark:text-gray-400">{{ $recipe['description'] }}</p>
                <p class="mt-2">
                    Nesting:
                    @foreach ($recipe['nesting'] as $component)
                        <code class="mr-1">{{ $component }}</code>
                    @endforeach
                </p>
            </li>
        @endforeach
    </ul>
</div>
