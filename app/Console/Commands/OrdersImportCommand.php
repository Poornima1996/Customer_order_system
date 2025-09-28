<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OrdersImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:import {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import orders from CSV file using queued jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Starting CSV import from: {$file}");
        
        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Cannot open file: {$file}");
            return 1;
        }

        $header = fgetcsv($handle);
        $rowCount = 0;
        $queuedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $orderData = array_combine($header, $row);
            
            // Queue the order processing job
            ProcessOrderJob::dispatch($orderData);
            $queuedCount++;

            if ($rowCount % 100 === 0) {
                $this->info("Processed {$rowCount} rows, queued {$queuedCount} orders...");
            }
        }

        fclose($handle);

        $this->info("Import completed!");
        $this->info("Total rows processed: {$rowCount}");
        $this->info("Orders queued for processing: {$queuedCount}");
        
        return 0;
    }
}
