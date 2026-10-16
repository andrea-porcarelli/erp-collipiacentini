<?php

namespace App\Support;

/**
 * Helper per costruire URL quickchart.io che generano PNG di grafici.
 * DomPDF non esegue JS, quindi i grafici devono essere immagini renderizzate server-side.
 */
class QuickChart
{
    private const ENDPOINT = 'https://quickchart.io/chart';

    /**
     * Bar chart verticale (ultimi N mesi).
     *
     * @param  array<int,string>  $labels
     * @param  array<int,int|float>  $values
     */
    public static function bar(array $labels, array $values, string $color = '#ab9038', int $width = 420, int $height = 220): string
    {
        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $color,
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => ['display' => false, 'beginAtZero' => true],
                    'x' => ['grid' => ['display' => false], 'ticks' => ['color' => '#7a7a7a', 'font' => ['size' => 11]]],
                ],
            ],
        ];

        return self::buildUrl($config, $width, $height);
    }

    /**
     * Donut/doughnut chart.
     *
     * @param  array<int,string>  $labels
     * @param  array<int,int|float>  $values
     * @param  array<int,string>|null  $colors
     */
    public static function doughnut(array $labels, array $values, ?array $colors = null, int $width = 420, int $height = 220): string
    {
        $colors ??= self::defaultPalette(count($labels));

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'cutout' => '65%',
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];

        return self::buildUrl($config, $width, $height);
    }

    /**
     * @param  array<int,string>  $palette
     * @return array<int,string>
     */
    public static function defaultPalette(int $n, array $palette = ['#ab9038', '#7f6a2a', '#d7c48a', '#e8dcb0', '#5d4e22', '#c1a85a']): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $palette[$i % count($palette)];
        }

        return $out;
    }

    /**
     * Scarica l'immagine remota come data URI inline, così il PDF è self-contained.
     * Se la fetch fallisce, ritorna l'URL remoto (DomPDF proverà a scaricarlo da sé).
     */
    public static function fetchDataUri(string $url): string
    {
        $ctx = stream_context_create(['http' => ['timeout' => 8]]);
        $bytes = @file_get_contents($url, false, $ctx);
        if ($bytes === false || $bytes === '') {
            return $url;
        }

        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    private static function buildUrl(array $config, int $width, int $height): string
    {
        return self::ENDPOINT.'?'.http_build_query([
            'c'     => json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'w'     => $width,
            'h'     => $height,
            'bkg'   => 'transparent',
            'devicePixelRatio' => 2,
        ]);
    }
}
