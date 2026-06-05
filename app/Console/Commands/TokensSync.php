<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TokensSync extends Command
{
    protected $signature = 'tokens:sync
                            {--source=public/backoffice/css/Miticko.css : Sorgente CSS con i design token}
                            {--mode=Light : Blocco modalità colore (Light|Dark)}
                            {--brands=Miticko,Veleia : Lista CSV dei brand da estrarre}
                            {--default=Miticko : Brand di default applicato quando nessuno è specificato a runtime}
                            {--output=config/design.php : Path del file PHP da generare}';

    protected $description = 'Estrae i design token da Miticko.css, risolve le var() e rigenera config/design.php';

    public function handle(): int
    {
        $sourcePath = base_path($this->option('source'));

        if (! is_file($sourcePath)) {
            $this->error("File sorgente non trovato: {$sourcePath}");

            return self::FAILURE;
        }

        $css = file_get_contents($sourcePath);
        $blocks = $this->parseBlocks($css);

        $mode = $this->option('mode');
        $brands = array_filter(array_map('trim', explode(',', $this->option('brands'))));
        $default = $this->option('default');

        if (! isset($blocks[$mode])) {
            $this->error("Blocco [data-mode*=\"{$mode}\"] non trovato nel CSS sorgente.");

            return self::FAILURE;
        }

        $brandsOut = [];
        foreach ($brands as $brand) {
            if (! isset($blocks[$brand])) {
                $this->warn("Blocco [data-mode*=\"{$brand}\"] non trovato, brand saltato.");

                continue;
            }

            // Primitive e mapping brand → primitive vivono nel blocco brand.
            // I token semantici (--text-main, --brand-primary-brand, …) vivono nel blocco mode.
            // Il merge mode-dopo-brand fa sì che, in caso di chiavi in comune, vinca il mode.
            $vars = array_merge($blocks[$brand], $blocks[$mode]);

            $resolved = [];
            foreach ($vars as $name => $value) {
                $resolved[$name] = $this->resolve($value, $vars, []);
            }

            ksort($resolved);

            $brandsOut[strtolower($brand)] = [
                'meta' => ['brand' => $brand, 'mode' => $mode, 'tokens_count' => count($resolved)],
                'tokens' => $resolved,
            ];
        }

        if ($brandsOut === []) {
            $this->error('Nessun brand risolto. Controlla il flag --brands.');

            return self::FAILURE;
        }

        $output = $this->renderConfig($brandsOut, $sourcePath, $mode, strtolower($default));
        $outputPath = base_path($this->option('output'));

        file_put_contents($outputPath, $output);

        $this->info(sprintf(
            'Generati %d brand in %s (mode: %s, default: %s).',
            count($brandsOut),
            $this->option('output'),
            $mode,
            $default,
        ));

        foreach ($brandsOut as $slug => $data) {
            $this->line(sprintf('  - %s: %d token', $slug, $data['meta']['tokens_count']));
        }

        return self::SUCCESS;
    }

    /**
     * Estrae i blocchi [data-mode*="X"] { --name: value; … } come dizionari.
     *
     * @return array<string, array<string, string>>
     */
    private function parseBlocks(string $css): array
    {
        $blocks = [];

        if (! preg_match_all('/\[data-mode\*="([^"]+)"\]\s*\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER)) {
            return $blocks;
        }

        foreach ($matches as $match) {
            $name = $match[1];
            $body = $match[2];

            $vars = [];
            if (preg_match_all('/--([a-zA-Z0-9-]+)\s*:\s*([^;]+);/', $body, $declarations, PREG_SET_ORDER)) {
                foreach ($declarations as $declaration) {
                    $vars['--' . $declaration[1]] = trim($declaration[2]);
                }
            }

            // Se lo stesso selettore compare più volte (è capitato negli export Figma),
            // i token successivi vincono — comportamento coerente con il CSS reale.
            $blocks[$name] = array_merge($blocks[$name] ?? [], $vars);
        }

        return $blocks;
    }

    /**
     * Risolve ricorsivamente var(--ref) → valore concreto.
     * Gestisce fallback `var(--ref, fallback)` e cicli.
     */
    private function resolve(string $value, array $vars, array $seen): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (! preg_match('/^var\(\s*(--[a-zA-Z0-9-]+)\s*(?:,\s*(.+?))?\s*\)$/', $value, $m)) {
            return $value;
        }

        $ref = $m[1];
        $fallback = $m[2] ?? '';

        if (in_array($ref, $seen, true)) {
            // ciclo: ritorno il fallback o il riferimento testuale per non bloccare
            return $fallback !== '' ? $this->resolve($fallback, $vars, $seen) : $value;
        }

        if (isset($vars[$ref])) {
            return $this->resolve($vars[$ref], $vars, [...$seen, $ref]);
        }

        if ($fallback !== '') {
            return $this->resolve($fallback, $vars, $seen);
        }

        return $value;
    }

    /**
     * @param  array<string, array{meta: array<string,mixed>, tokens: array<string,string>}>  $brands
     */
    private function renderConfig(array $brands, string $sourcePath, string $mode, string $defaultBrand): string
    {
        $brandsBlock = '';
        foreach ($brands as $slug => $data) {
            $tokenEntries = '';
            foreach ($data['tokens'] as $name => $value) {
                $key = ltrim($name, '-');
                $tokenEntries .= "                " . var_export($key, true) . ' => ' . var_export($value, true) . ",\n";
            }
            $brandsBlock .= "        " . var_export($slug, true) . " => [\n";
            $brandsBlock .= "            'meta' => " . var_export($data['meta'], true) . ",\n";
            $brandsBlock .= "            'tokens' => [\n{$tokenEntries}            ],\n";
            $brandsBlock .= "        ],\n";
        }

        $generatedAt = date('Y-m-d H:i:s');
        $sourceRel = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $sourcePath);

        return <<<PHP
<?php

/*
|--------------------------------------------------------------------------
| Design tokens
|--------------------------------------------------------------------------
| Generato automaticamente da `php artisan tokens:sync`.
| Non modificare a mano: rilancia il comando dopo aver aggiornato il CSS.
|
| Sorgente      : {$sourceRel}
| Mode          : {$mode}
| Default brand : {$defaultBrand}
| Generato     : {$generatedAt}
*/

return [
    'meta' => [
        'source'    => '{$sourceRel}',
        'mode'      => '{$mode}',
        'synced_at' => '{$generatedAt}',
    ],

    'default_brand' => '{$defaultBrand}',

    'brands' => [
{$brandsBlock}    ],
];
PHP;
    }
}
