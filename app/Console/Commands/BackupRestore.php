<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupRestore extends Command
{
    protected $signature = 'backup:restore {file : Path or filename of the .sqlite.gz backup} {--force : Force restoration without interactive confirmation}';

    protected $description = 'Restore a SQLite database from a verified .sqlite.gz backup';

    public function handle(): int
    {
        $backupArg = $this->argument('file');
        $backupDir = env('BACKUP_PATH', base_path('backups'));

        $backupPath = File::exists($backupArg) ? $backupArg : "{$backupDir}/{$backupArg}";

        if (! File::exists($backupPath)) {
            $this->error("Backup file not found at: {$backupPath}");
            return self::FAILURE;
        }

        $dbPath = config('database.connections.sqlite.database');

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to restore database from [{$backupPath}]? This will replace the active database!")) {
            $this->info('Restore aborted by user.');
            return self::SUCCESS;
        }

        $this->info("Decompressing backup [{$backupPath}]...");

        $gzData = file_get_contents($backupPath);
        $sqlData = @gzdecode($gzData);

        if ($sqlData === false) {
            $this->error('Failed to decompress backup file. File might be corrupted.');
            return self::FAILURE;
        }

        $tempPath = "{$dbPath}.restore_tmp";
        file_put_contents($tempPath, $sqlData);

        // Verify SQLite integrity before swapping active DB
        try {
            $pdo = new \PDO("sqlite:{$tempPath}");
            $stmt = $pdo->query('PRAGMA integrity_check;');
            $result = $stmt ? $stmt->fetchColumn() : null;

            if ($result !== 'ok') {
                @unlink($tempPath);
                $this->error("Integrity check failed on restored database: {$result}");
                return self::FAILURE;
            }
            unset($pdo);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            $this->error("Failed to connect to restored database: {$e->getMessage()}");
            return self::FAILURE;
        }

        // Archive current active DB if exists
        if (File::exists($dbPath)) {
            $archivePath = "{$dbPath}.pre_restore_" . now()->format('Ymd_His');
            File::move($dbPath, $archivePath);
            @unlink("{$dbPath}-wal");
            @unlink("{$dbPath}-shm");
            $this->info("Active database archived to: {$archivePath}");
        }

        File::move($tempPath, $dbPath);

        $this->info("Database successfully restored from: {$backupPath}");
        Log::info("Database successfully restored from: {$backupPath}");

        return self::SUCCESS;
    }
}
