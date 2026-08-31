<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        Mock table rows rendered with admin surface column <code>format*Display()</code> helpers — pairs with lazy CSS bundles
        <code>progress-column</code>, <code>status-chip-column</code>, <code>signature-preview-column</code>, and <code>map-pin-column</code>.
    </p>

    <div class="fff-user-column-playground__table-wrap">
        <table class="fff-user-column-playground__table">
            <thead>
                <tr>
                    <th scope="col">Record</th>
                    <th scope="col">ProgressColumn</th>
                    <th scope="col">StatusChipColumn</th>
                    <th scope="col">SignaturePreviewColumn</th>
                    <th scope="col">MapPinColumn</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="fff-user-column-playground__project">{{ $row['title'] }}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['progress'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['status'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['signature'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['location'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
