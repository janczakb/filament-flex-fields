<div class="fff-user-column-playground">
    <p class="fff-user-column-playground__intro">
        <code>CompliancePack::exportReport()</code> —
        {{ $report['standard'] ?? 'WCAG 2.2 AA' }}
        generated at
        <code>{{ $report['generated_at'] }}</code>
    </p>

    <dl class="text-sm space-y-2 mb-6">
        <div>
            <dt class="inline font-medium">Field types inventoried:</dt>
            <dd class="inline">{{ $report['field_type_count'] }}</dd>
        </div>
        <div>
            <dt class="inline font-medium">Supported locales:</dt>
            <dd class="inline"><code>{{ implode(', ', $report['locales']) }}</code></dd>
        </div>
        <div>
            <dt class="inline font-medium">AA summary:</dt>
            <dd class="inline">
                pass {{ $report['summary']['pass'] ?? 0 }}
                · pending {{ $report['summary']['pending'] ?? 0 }}
                · fail {{ $report['summary']['fail'] ?? 0 }}
                ({{ $aaTotal }} components; sample below)
            </dd>
        </div>
    </dl>

    @if (! empty($report['criteria']))
        <h3 class="text-sm font-semibold mb-2">Audit criteria</h3>
        <ul class="text-sm list-disc ps-5 mb-6 space-y-1">
            @foreach ($report['criteria'] as $criterion)
                <li>{{ $criterion }}</li>
            @endforeach
        </ul>
    @endif

    <table class="fff-user-column-playground__table text-sm">
        <thead>
            <tr>
                <th scope="col">Component</th>
                <th scope="col">WCAG AA status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($aaSample as $component => $status)
                <tr>
                    <td><code>{{ $component }}</code></td>
                    <td><code>{{ $status }}</code></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
