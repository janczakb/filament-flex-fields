<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class AudioWaveformGenerator
{
    public const SAMPLE_COUNT = 64;

    /**
     * @return list<int>
     */
    public static function fromFingerprint(string $fingerprint, int $sampleCount = self::SAMPLE_COUNT): array
    {
        if ($fingerprint === '') {
            return self::placeholderWaveform($sampleCount);
        }

        $seed = self::hashString($fingerprint);
        $random = self::makeRandom($seed);

        $f1 = 2 + ($seed % 7);
        $f2 = 5 + (($seed >> 8) % 11);
        $f3 = 12 + (($seed >> 16) % 15);
        $phase1 = $random() * M_PI * 2;
        $phase2 = $random() * M_PI * 2;

        $peaks = [];

        for ($index = 0; $index < $sampleCount; $index++) {
            $t = $index / $sampleCount;
            $envelope = 0.55 + (0.45 * sin($t * M_PI));
            $harmonicOne = abs(sin($t * M_PI * $f1 + $phase1));
            $harmonicTwo = abs(sin($t * M_PI * $f2 + $phase2)) * 0.7;
            $harmonicThree = abs(sin($t * M_PI * $f3)) * 0.4;
            $noise = 0.15 * $random();

            $peaks[] = $envelope * ($harmonicOne + $harmonicTwo + $harmonicThree + $noise);
        }

        return self::normalizePeaks(self::smoothPeaks($peaks, 2));
    }

    /**
     * @return list<int>
     */
    public static function placeholderWaveform(int $sampleCount = self::SAMPLE_COUNT): array
    {
        return array_fill(0, $sampleCount, 12);
    }

    /**
     * @param  list<float>  $peaks
     * @return list<int>
     */
    public static function normalizePeaks(array $peaks, int $minPeak = 8, int $maxPeak = 100): array
    {
        if ($peaks === []) {
            return [];
        }

        $maximum = max($peaks);

        if ($maximum <= 0) {
            return array_fill(0, count($peaks), $minPeak);
        }

        $range = max(1, $maxPeak - $minPeak);

        return array_map(
            fn (float $peak): int => (int) round($minPeak + (($peak / $maximum) * $range)),
            $peaks,
        );
    }

    /**
     * @param  list<float>  $peaks
     * @return list<float>
     */
    public static function smoothPeaks(array $peaks, int $passes = 2): array
    {
        $result = $peaks;

        for ($pass = 0; $pass < $passes; $pass++) {
            $next = [];

            foreach ($result as $index => $peak) {
                $previous = $result[$index - 1] ?? $peak;
                $following = $result[$index + 1] ?? $peak;

                $next[] = ($previous + $peak + $following) / 3;
            }

            $result = $next;
        }

        return $result;
    }

    private static function hashString(string $value): int
    {
        $hash = 2166136261;

        for ($index = 0, $length = strlen($value); $index < $length; $index++) {
            $hash ^= ord($value[$index]);
            $hash = ($hash * 16777619) & 0xFFFFFFFF;
        }

        return $hash;
    }

    /**
     * @return \Closure(): float
     */
    private static function makeRandom(int $seed): \Closure
    {
        $state = $seed;

        return function () use (&$state): float {
            $state = ($state * 1664525 + 1013904223) & 0xFFFFFFFF;

            return $state / 4294967296;
        };
    }
}
