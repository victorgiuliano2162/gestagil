<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--label= : Optional label for backup (e.g., pre-deploy)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a consistent backup of the SQLite database using VACUUM INTO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connection = config('database.default');

        if ($connection !== 'sqlite') {
            $this->error('Backup is only supported for SQLite databases.');
            return self::FAILURE;
        }

        $dbPath = config('database.connections.sqlite.database');

        if (! file_exists($dbPath)) {
            $this->error("Database file not found at: {$dbPath}");
            return self::FAILURE;
        }

        $backupDir = env('BACKUP_PATH', base_path('backups'));

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $label = $this->option('label') ? preg_replace('/[^a-zA-Z0-9_-]/', '', $this->option('label')) . '-' : '';
        $timestamp = now()->format('Ymd-His');
        $rawBackupPath = "{$backupDir}/gestagil-{$label}{$timestamp}.sqlite";
        $gzBackupPath = "{$rawBackupPath}.gz";

        $this->info("Creating SQLite backup via VACUUM INTO at: {$rawBackupPath}...");

        try {
            // VACUUM INTO requires target path to NOT exist beforehand
            if (file_exists($rawBackupPath)) {
                @unlink($rawBackupPath);
            }

            // Run VACUUM INTO
            DB::statement("VACUUM INTO '{$rawBackupPath}'");

            // Integrity check on the backup copy
            $backupPdo = new \PDO("sqlite:{$rawBackupPath}");
            $stmt = $backupPdo->query('PRAGMA integrity_check;');
            $result = $stmt ? $stmt->fetchColumn() : null;

            if ($result !== 'ok') {
                throw new \RuntimeException("Integrity check failed on backup copy: {$result}");
            }

            unset($backupPdo);

            // Compress using gzip
            $data = file_get_contents($rawBackupPath);
            $gzdata = gzencode($data, 9);
            file_put_contents($gzBackupPath, $gzdata);
            @unlink($rawBackupPath);

            $this->info("Backup created and verified successfully: {$gzBackupPath}");
            Log::info("Backup created successfully: {$gzBackupPath}");

            // Prune old backups
            $keepDays = (int) env('BACKUP_KEEP_DAYS', 14);
            $this->pruneOldBackups($backupDir, $keepDays);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("Backup failed: {$e->getMessage()}", ['exception' => $e]);

            if (file_exists($rawBackupPath)) {
                @unlink($rawBackupPath);
            }

            return self::FAILURE;
        }
    }

    /**
     * Prune backups older than given days.
     */
    protected function pruneOldBackups(string $directory, int $keepDays): void
    {
        $threshold = now()->subDays($keepDays)->timestamp;
        $files = File::glob("{$directory}/gestagil-*.sqlite.gz");

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                @unlink($file);
                $this->line("Pruned old backup: " . basename($file));
            }
        }
    }
}
