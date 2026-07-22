<?php

namespace App\Console\Commands;

use App\Services\Importers\MetApiImporter;
use Illuminate\Console\Command;

class ImportMetCommand extends Command
{
    protected $signature = 'import:met
                            {--batch=50 : Number of objects per batch}
                            {--limit= : Maximum objects to import}
                            {--offset=0 : Starting offset}';

    protected $description = 'Import African artifacts from the Met Museum API';

    public function handle(MetApiImporter $importer): int
    {
        $this->info('🚀 Starting Met Museum import...');
        $this->info('🔍 Using multi-strategy search (department, culture, geolocation).');
        
        $batchSize = (int) $this->option('batch');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $offset = (int) $this->option('offset');

        $this->info(sprintf("Batch size: %d, Offset: %d", $batchSize, $offset));
        if ($limit) $this->info("Limit: $limit");

        try {
            $stats = $importer->importAll($batchSize, $limit, $offset);
            $this->newLine();
            $this->info('✅ Import completed.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Imported', $stats['imported']],
                    ['Skipped', $stats['skipped']],
                    ['Failed', $stats['failed']],
                ]
            );
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}