<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixFigmaCss extends Command
{
    protected $signature = 'figma:fix-css {file=public/backoffice/css/miticko.css}';

    protected $description = 'Corregge il CSS esportato da Figma: selettori data-mode, unità px e proprietà duplicate';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (!file_exists($path)) {
            $this->error("File non trovato: {$path}");
            return self::FAILURE;
        }

        $content = file_get_contents($path);

        // 1. Converti selettori [data-mode="..."] → [data-mode*="..."]
        $selectorCount = substr_count($content, '[data-mode="');
        $content = str_replace('[data-mode="', '[data-mode*="', $content);

        // 2. Aggiungi "px" ai valori numerici puri, escludendo weight
        $pxCount = 0;
        $content = preg_replace_callback(
            '/^(\s*--.+?:\s*)(\d+)(;\s*)$/m',
            function ($matches) use (&$pxCount) {
                if (str_contains($matches[1], 'weight')) {
                    return $matches[0];
                }
                $pxCount++;
                return $matches[1] . $matches[2] . 'px' . $matches[3];
            },
            $content
        );

        // 3. Rimuovi proprietà duplicate nello stesso blocco (mantiene l'ultima occorrenza)
        $duplicateCount = 0;
        $content = preg_replace_callback(
            '/\{([^}]+)\}/s',
            function ($matches) use (&$duplicateCount) {
                $lines = explode("\n", $matches[1]);
                $seen = [];
                $cleaned = [];

                foreach ($lines as $line) {
                    if (preg_match('/^\s*(--[\w-]+)\s*:/', $line, $prop)) {
                        $key = $prop[1];
                        if (isset($seen[$key])) {
                            // Rimuovi la precedente e tieni questa (l'ultima vince)
                            unset($cleaned[$seen[$key]]);
                            $duplicateCount++;
                        }
                        $seen[$key] = count($cleaned);
                    }
                    $cleaned[] = $line;
                }

                return '{' . implode("\n", array_values($cleaned)) . '}';
            },
            $content
        );

        file_put_contents($path, $content);

        $this->info("Selettori data-mode convertiti: {$selectorCount}");
        $this->info("Unità px aggiunte: {$pxCount}");
        $this->info("Proprietà duplicate rimosse: {$duplicateCount}");

        return self::SUCCESS;
    }
}
