@if (request()->is('*flex-fields-playground*'))
    <script>
        if (localStorage.getItem('fff-playground-dark-mode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
@endif
