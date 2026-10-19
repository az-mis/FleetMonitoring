<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Gss\Import\GssImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time import of the GSS workbook. Runs as a dry run (everything rolled back) unless --commit is given.
 *
 *   php artisan gss:import "GSS 2026.xlsx"            dry run: counts and the review list, nothing saved
 *   php artisan gss:import "GSS 2026.xlsx" --commit   saves; running it again changes nothing
 */
class GssImportCommand extends Command
{
    protected $signature = 'gss:import
        {file : Path to the GSS workbook (.xlsx)}
        {--year= : The workbook year (default: the 4-digit year in the file name, else this year)}
        {--commit : Save the import (without it, a dry run that saves nothing)}';

    protected $description = 'Import the vehicle records of a GSS workbook (dry run unless --commit)';

    public function handle(GssImporter $importer): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }
        $year = (int) ($this->option('year') ?: (preg_match('/(20\d{2})/', basename($file), $m) ? $m[1] : now()->format('Y')));
        $commit = (bool) $this->option('commit');
        ini_set('memory_limit', '1G');

        $this->info(($commit ? 'Importing' : 'Dry run of').' '.basename($file)." ({$year}) into database ".DB::connection()->getDatabaseName().'…');
        $result = $importer->run($file, $year, $commit);

        $rows = [];
        foreach ($result['counts'] as $sheet => $c) {
            $rows[] = [$sheet, $c['created'], $c['updated'], $c['unchanged'], $c['skipped'], $c['review']];
        }
        $this->table(['Sheet', 'Created', 'Updated', 'Already imported', 'Not imported', 'Needs review'], $rows);
        $this->line("Review list ({$result['review_rows']} rows): {$result['review_file']}");
        $commit
            ? $this->info('Saved.')
            : $this->warn('Dry run: nothing was saved. Run again with --commit to save.');

        return self::SUCCESS;
    }
}
