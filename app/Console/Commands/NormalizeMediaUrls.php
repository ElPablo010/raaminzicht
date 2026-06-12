<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Strip het domein uit opgeslagen media-URL's, zodat ze root-relatief worden
 * ('/storage/...'). Nodig als eenmalige correctie nadat de `public`-disk-config
 * van een absolute (APP_URL-gebaseerde) URL naar '/storage' is omgezet: bestaande
 * rijen bevatten nog het oude (dev-)domein en zouden anders breken na een
 * dev → live DB-kopie.
 *
 * Idempotent: rijen die al relatief zijn worden niet geraakt. Enkel URL's die
 * naar '/storage/' wijzen worden gestript; externe links blijven ongemoeid.
 */
class NormalizeMediaUrls extends Command
{
    protected $signature = 'media:normalize-urls {--dry-run : Toon wat zou wijzigen, zonder op te slaan}';

    protected $description = 'Maak opgeslagen media-URL\'s domein-onafhankelijk (/storage/...)';

    /** Vangt `https://welk-domein-dan-ook/storage/` (en http) en vervangt door `/storage/`. */
    private const PATTERN = '#https?://[^/"\s\\\\]+/storage/#i';

    private bool $dryRun = false;

    private int $touched = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('Dry-run: er wordt niets opgeslagen.');
        }

        // 1) website_media: aparte url-kolommen.
        $this->processTable('website_media', ['url', 'fallback_url']);

        // 2) page_sections: media-URL's leven als string in de content-JSON.
        $this->processTable('page_sections', ['content']);

        // 3) settings: header/footer-logo's e.d. in de value-JSON.
        $this->processTable('settings', ['value']);

        // 4) pages: SEO-deelafbeelding.
        $this->processTable('pages', ['seo_image_url']);

        $this->newLine();
        $this->info($this->dryRun
            ? "{$this->touched} rij(en) zouden wijzigen."
            : "Klaar — {$this->touched} rij(en) bijgewerkt.");

        if (! $this->dryRun && $this->touched > 0) {
            $this->call('cache:clear');
            $this->line('Dimensie-cache (op md5(url)) geleegd.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     */
    private function processTable(string $table, array $columns): void
    {
        DB::table($table)->orderBy('id')->each(function (object $row) use ($table, $columns): void {
            $changes = [];

            foreach ($columns as $column) {
                $original = $row->{$column} ?? null;

                if ($original === null || $original === '') {
                    continue;
                }

                $replaced = preg_replace(self::PATTERN, '/storage/', $original);

                if ($replaced !== $original) {
                    $changes[$column] = $replaced;
                }
            }

            if ($changes === []) {
                return;
            }

            $this->touched++;
            $this->line("  {$table} #{$row->id}: ".implode(', ', array_keys($changes)));

            if (! $this->dryRun) {
                DB::table($table)->where('id', $row->id)->update($changes);
            }
        });
    }
}
