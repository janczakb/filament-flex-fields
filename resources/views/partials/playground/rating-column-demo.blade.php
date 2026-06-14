<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        Mock table rows rendered with <code>RatingColumn::formatRatingDisplay()</code> — no database required.
    </p>

    <div class="fff-user-column-playground__table-wrap">
        <table class="fff-user-column-playground__table">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Score</th>
                    <th scope="col">Satisfaction</th>
                    <th scope="col">Average (10)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="fff-user-column-playground__project">{{ $row['title'] }}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['score'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['satisfaction'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['average'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
