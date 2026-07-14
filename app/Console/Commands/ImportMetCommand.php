<?php

namespace App\Console\Commands;

use App\Services\Importers\MetApiImporter;
use Illuminate\Console\Command;

class ImportMetCommand extends Command
{
    protected $signature = 'import:met
                            {--limit= : Maximum number of artifacts to import}
                            {--department= : Filter by department ID}
                            {--offset=0 : Number of object IDs to skip}
                            {--skip-filter : Import all artifacts without African filtering (debug mode)}';

    protected $description = 'Import African artifacts from the Metropolitan Museum of Art API';

    public function handle(MetApiImporter $importer): int
    {
        $this->info('Starting Met Museum import...');
        $this->info('🔍 Filtering for African cultural heritage artifacts...');

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $department = $this->option('department') ? (int) $this->option('department') : null;
        $offset = (int) $this->option('offset');
        $skipFiltering = $this->option('skip-filter');

        if ($skipFiltering) {
            $this->warn('⚠️  African filtering disabled. Importing ALL artifacts (debug mode).');
        }

        $start = microtime(true);

        $stats = $importer->import($limit, $department, $offset, $skipFiltering);

        $duration = round(microtime(true) - $start, 2);

        $this->newLine();
        $this->info("✅ Import completed in {$duration} seconds.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported', $stats['imported']],
                ['Skipped (API error)', $stats['skipped']],
                ['Skipped (Not African)', $stats['skipped_by_filter']],
                ['Failed', $stats['failed']],
            ]
        );

        if ($stats['imported'] > 0) {
            $this->info('🎉 New African artifacts added to the collection!');
        } else {
            $this->warn('No African artifacts were found. Try using --skip-filter to debug.');
        }

        return $stats['failed'] > 0 ? 1 : 0;
    }
}