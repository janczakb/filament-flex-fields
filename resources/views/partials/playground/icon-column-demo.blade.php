<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        Mock table rows rendered with <code>IconColumn::formatIconDisplay()</code> — pairs with values saved by <code>IconPickerField</code>.
    </p>

    <div class="fff-user-column-playground__table-wrap">
        <table class="fff-user-column-playground__table">
            <thead>
                <tr>
                    <th scope="col">Menu item</th>
                    <th scope="col">Icon</th>
                    <th scope="col">Icon + label</th>
                    <th scope="col">Icon + label + name</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="fff-user-column-playground__project">{{ $row['title'] }}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['icon'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['labeled'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['detailed'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
