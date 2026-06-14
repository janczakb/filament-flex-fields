<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        Mock table rows rendered with <code>UserColumn::formatUserDisplay()</code> — no database required.
    </p>

    <div class="fff-user-column-playground__table-wrap">
        <table class="fff-user-column-playground__table">
            <thead>
                <tr>
                    <th scope="col">Project</th>
                    <th scope="col">Author</th>
                    <th scope="col">Members</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="fff-user-column-playground__project">{{ $row['project'] }}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['author'] !!}</td>
                        <td class="fff-user-column-playground__cell">{!! $row['members'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
