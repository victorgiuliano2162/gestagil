<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

test('backup:run generates consistent compressed sqlite backup', function () {
    $tempBackupDir = sys_get_temp_dir() . '/gestagil_test_backups_' . uniqid();
    File::makeDirectory($tempBackupDir, 0755, true);

    while (DB::transactionLevel() > 0) {
        DB::commit();
    }

    $this->artisan('backup:run', [
        '--label' => 'test',
        '--destination' => $tempBackupDir,
    ])->assertExitCode(0);

    $files = File::glob("{$tempBackupDir}/gestagil-test-*.sqlite.gz");
    expect(count($files))->toBe(1);

    $backupFile = $files[0];
    $gzData = file_get_contents($backupFile);
    $sqlData = gzdecode($gzData);

    expect($sqlData)->not->toBeFalse();

    // Testa integridade do banco gerado pelo backup
    $tempDb = tempnam(sys_get_temp_dir(), 'sql_test_');
    file_put_contents($tempDb, $sqlData);

    $pdo = new \PDO("sqlite:{$tempDb}");
    $result = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
    expect($result)->toBe('ok');

    unset($pdo);
    @unlink($tempDb);
    File::deleteDirectory($tempBackupDir);

    DB::beginTransaction();
});

test('backup:restore validates integrity and restores database file', function () {
    $tempDir = sys_get_temp_dir() . '/gestagil_restore_test_' . uniqid();
    File::makeDirectory($tempDir, 0755, true);

    $fakeDb = "{$tempDir}/source.sqlite";
    $pdo = new \PDO("sqlite:{$fakeDb}");
    $pdo->exec('CREATE TABLE test_items (id INTEGER PRIMARY KEY, name TEXT);');
    $pdo->exec("INSERT INTO test_items (name) VALUES ('item_recuperado');");
    unset($pdo);

    $gzBackup = "{$tempDir}/backup.sqlite.gz";
    file_put_contents($gzBackup, gzencode(file_get_contents($fakeDb), 9));

    $targetDb = "{$tempDir}/target.sqlite";
    config(['database.connections.sqlite.database' => $targetDb]);

    $this->artisan('backup:restore', [
        'file' => $gzBackup,
        '--force' => true,
    ])->assertExitCode(0);

    expect(File::exists($targetDb))->toBeTrue();

    $restoredPdo = new \PDO("sqlite:{$targetDb}");
    $check = $restoredPdo->query('PRAGMA integrity_check;')->fetchColumn();
    expect($check)->toBe('ok');

    $val = $restoredPdo->query("SELECT name FROM test_items WHERE id = 1;")->fetchColumn();
    expect($val)->toBe('item_recuperado');

    unset($restoredPdo);
    File::deleteDirectory($tempDir);
});

test('sqlite database operates with foreign keys active', function () {
    $foreignKeys = DB::select('PRAGMA foreign_keys;');
    $val = $foreignKeys[0]->foreign_keys ?? reset($foreignKeys[0]);

    expect((int) $val)->toBe(1);
});
