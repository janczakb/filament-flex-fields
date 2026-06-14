#!/usr/bin/env php
<?php

/**
 * Generates the full ISO 3166-1 alpha-2 country list and translation files.
 *
 * Uses PHP intl (locale_get_display_region) for names. Deprecated / reserved
 * codes are excluded. Output: src/Data/iso3166-alpha2-codes.php + lang files.
 */
$deprecated = [
    'AN', 'BU', 'CS', 'DD', 'DY', 'FX', 'HV', 'NH', 'QO', 'RH', 'SU', 'TP',
    'UK', 'VD', 'YD', 'YU', 'ZR', 'EU', 'EZ', 'UN', 'XA', 'XB', 'CQ', 'CP',
];

$codes = [];

for ($first = ord('A'); $first <= ord('Z'); $first++) {
    for ($second = ord('A'); $second <= ord('Z'); $second++) {
        $code = chr($first).chr($second);

        if (in_array($code, $deprecated, true)) {
            continue;
        }

        $englishName = locale_get_display_region('-'.$code, 'en');

        if ($englishName === false || $englishName === '' || $englishName === $code) {
            continue;
        }

        if (str_contains(strtolower($englishName), 'unknown')) {
            continue;
        }

        $codes[] = $code;
    }
}

sort($codes);
$codes = array_values(array_unique($codes));

$root = dirname(__DIR__);

$dataPath = $root.'/src/Data/iso3166-alpha2-codes.php';
$dataLines = ['<?php', '', '/**', ' * @var list<string>', ' */', 'return ['];

foreach ($codes as $code) {
    $dataLines[] = "    '{$code}',";
}

$dataLines[] = '];';
$dataLines[] = '';
file_put_contents($dataPath, implode("\n", $dataLines));

echo 'Wrote iso3166-alpha2-codes.php with '.count($codes)." codes\n";

foreach (['en', 'pl'] as $locale) {
    $lines = ['<?php', '', 'return ['];

    foreach ($codes as $code) {
        $name = locale_get_display_region('-'.$code, $locale) ?: $code;
        $name = str_replace("'", "\\'", $name);
        $lines[] = "    '{$code}' => '{$name}',";
    }

    $lines[] = '];';
    $lines[] = '';

    $path = $root.'/resources/lang/'.$locale.'/countries.php';

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, implode("\n", $lines));

    echo "Wrote {$locale}/countries.php with ".count($codes)." countries\n";
}
