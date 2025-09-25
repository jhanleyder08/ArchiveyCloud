<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class BackupSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:create 
                          {--type=full : Tipo de backup (full, database, files)}
                          {--compress : Comprimir backup}
                          {--cleanup : Limpiar backups antiguos después del backup}
                          {--retention-days=30 : Días de retención de backups}
                          {--exclude-logs : Excluir archivos de logs del backup}
                          {--dry-run : Mostrar qué se respaldaría sin crear backup}';

    /**
     * The console command description.
     */
    protected $description = 'Crear backup completo del sistema ArchiveyCloud (base de datos y archivos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗄️  Iniciando proceso de backup de ArchiveyCloud...');
        $this->newLine();

        $type = $this->option('type');
        $compress = $this->option('compress');
        $cleanup = $this->option('cleanup');
        $retentionDays = (int) $this->option('retention-days');
        $excludeLogs = $this->option('exclude-logs');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN activado - No se creará backup real');
            $this->newLine();
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupPath = storage_path("backups/{$timestamp}");
        
        // Crear directorio de backup
        if (!$dryRun && !File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $backupInfo = [
            'timestamp' => $timestamp,
            'type' => $type,
            'path' => $backupPath,
            'compressed' => $compress,
            'size' => 0,
            'files_count' => 0,
            'components' => [],
        ];

        try {
            // Realizar backup según tipo
            switch ($type) {
                case 'full':
                    $this->createFullBackup($backupPath, $backupInfo, $excludeLogs, $dryRun);
                    break;
                case 'database':
                    $this->createDatabaseBackup($backupPath, $backupInfo, $dryRun);
                    break;
                case 'files':
                    $this->createFilesBackup($backupPath, $backupInfo, $excludeLogs, $dryRun);
                    break;
                default:
                    $this->error("❌ Tipo de backup '{$type}' no válido. Use: full, database, files");
                    return Command::FAILURE;
            }

            // Comprimir si se solicita
            if ($compress && !$dryRun) {
                $this->compressBackup($backupPath, $backupInfo);
            }

            // Limpiar backups antiguos
            if ($cleanup) {
                $this->cleanupOldBackups($retentionDays, $dryRun);
            }

            // Mostrar resumen
            $this->showBackupSummary($backupInfo, $dryRun);

            // Guardar información del backup
            if (!$dryRun) {
                $this->saveBackupMetadata($backupInfo);
            }

            $this->info('✅ Backup completado exitosamente!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error durante el backup: {$e->getMessage()}");
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'backup_info' => $backupInfo,
            ]);

            // Limpiar backup incompleto
            if (!$dryRun && File::exists($backupPath)) {
                File::deleteDirectory($backupPath);
            }

            return Command::FAILURE;
        }
    }

    private function createFullBackup(string $backupPath, array &$backupInfo, bool $excludeLogs, bool $dryRun): void
    {
        $this->info('📊 Creando backup completo...');
        
        $this->createDatabaseBackup($backupPath, $backupInfo, $dryRun);
        $this->createFilesBackup($backupPath, $backupInfo, $excludeLogs, $dryRun);
        $this->createConfigBackup($backupPath, $backupInfo, $dryRun);
        
        $backupInfo['components'][] = 'full';
    }

    private function createDatabaseBackup(string $backupPath, array &$backupInfo, bool $dryRun): void
    {
        $this->info('📁 Respaldando base de datos...');

        $dbConnection = config('database.default');
        $dbConfig = config("database.connections.{$dbConnection}");
        
        if ($dryRun) {
            $this->line("   🔍 DRY-RUN: Respaldaría base de datos '{$dbConfig['database']}'");
            $backupInfo['components'][] = 'database';
            return;
        }

        $dumpFile = $backupPath . '/database.sql';
        
        if ($dbConfig['driver'] === 'mysql') {
            $this->createMysqlBackup($dbConfig, $dumpFile);
        } elseif ($dbConfig['driver'] === 'sqlite') {
            $this->createSqliteBackup($dbConfig, $dumpFile);
        } else {
            throw new \Exception("Driver de base de datos '{$dbConfig['driver']}' no soportado para backup");
        }

        $size = File::size($dumpFile);
        $backupInfo['size'] += $size;
        $backupInfo['files_count']++;
        $backupInfo['components'][] = 'database';
        
        $this->line("   ✅ Base de datos respaldada: " . $this->formatBytes($size));
    }

    private function createMysqlBackup(array $dbConfig, string $dumpFile): void
    {
        $command = [
            'mysqldump',
            '--host=' . $dbConfig['host'],
            '--port=' . $dbConfig['port'],
            '--user=' . $dbConfig['username'],
            '--password=' . $dbConfig['password'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--lock-tables=false',
            $dbConfig['database'],
        ];

        $process = new Process($command);
        $process->setTimeout(3600); // 1 hora
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Mysqldump failed: ' . $process->getErrorOutput());
        }

        File::put($dumpFile, $process->getOutput());
    }

    private function createSqliteBackup(array $dbConfig, string $dumpFile): void
    {
        $sourceFile = $dbConfig['database'];
        
        if (!File::exists($sourceFile)) {
            throw new \Exception("Archivo SQLite no encontrado: {$sourceFile}");
        }

        File::copy($sourceFile, $dumpFile);
    }

    private function createFilesBackup(string $backupPath, array &$backupInfo, bool $excludeLogs, bool $dryRun): void
    {
        $this->info('📂 Respaldando archivos del sistema...');

        $directories = $this->getDirectoriesToBackup($excludeLogs);
        $filesBackupPath = $backupPath . '/files';
        
        if (!$dryRun) {
            File::makeDirectory($filesBackupPath, 0755, true);
        }

        foreach ($directories as $name => $path) {
            if (!File::exists($path)) {
                $this->warn("   ⚠️  Directorio no encontrado: {$path}");
                continue;
            }

            if ($dryRun) {
                $files = File::allFiles($path);
                $size = collect($files)->sum(fn($file) => $file->getSize());
                $this->line("   🔍 DRY-RUN: Respaldaría {$name}: " . count($files) . ' archivos, ' . $this->formatBytes($size));
                continue;
            }

            $destinationPath = $filesBackupPath . '/' . $name;
            File::makeDirectory($destinationPath, 0755, true);

            // Copiar archivos
            $this->copyDirectory($path, $destinationPath, $backupInfo);
            $this->line("   ✅ {$name} respaldado");
        }

        $backupInfo['components'][] = 'files';
    }

    private function createConfigBackup(string $backupPath, array &$backupInfo, bool $dryRun): void
    {
        $this->info('⚙️  Respaldando configuración...');

        $configBackupPath = $backupPath . '/config';
        
        if (!$dryRun) {
            File::makeDirectory($configBackupPath, 0755, true);
        }

        $configFiles = [
            '.env.example' => base_path('.env.example'),
            'composer.json' => base_path('composer.json'),
            'composer.lock' => base_path('composer.lock'),
            'package.json' => base_path('package.json'),
            'vite.config.js' => base_path('vite.config.js'),
            'tailwind.config.js' => base_path('tailwind.config.js'),
            'config-production.example' => base_path('config-production.example'),
        ];

        foreach ($configFiles as $name => $source) {
            if (!File::exists($source)) {
                continue;
            }

            if ($dryRun) {
                $size = File::size($source);
                $this->line("   🔍 DRY-RUN: Respaldaría {$name}: " . $this->formatBytes($size));
                continue;
            }

            $destination = $configBackupPath . '/' . $name;
            File::copy($source, $destination);
            
            $size = File::size($destination);
            $backupInfo['size'] += $size;
            $backupInfo['files_count']++;
        }

        $backupInfo['components'][] = 'config';
        $this->line("   ✅ Configuración respaldada");
    }

    private function getDirectoriesToBackup(bool $excludeLogs): array
    {
        $directories = [
            'storage_app' => storage_path('app'),
            'public_uploads' => public_path('uploads'),
            'public_documents' => public_path('documents'),
        ];

        if (!$excludeLogs) {
            $directories['storage_logs'] = storage_path('logs');
        }

        return $directories;
    }

    private function copyDirectory(string $source, string $destination, array &$backupInfo): void
    {
        $files = File::allFiles($source);
        
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $destinationFile = $destination . '/' . $relativePath;
            $destinationDir = dirname($destinationFile);
            
            if (!File::exists($destinationDir)) {
                File::makeDirectory($destinationDir, 0755, true);
            }
            
            File::copy($file->getRealPath(), $destinationFile);
            
            $backupInfo['size'] += $file->getSize();
            $backupInfo['files_count']++;
        }
    }

    private function compressBackup(string $backupPath, array &$backupInfo): void
    {
        $this->info('📦 Comprimiendo backup...');

        $archiveName = basename($backupPath) . '.tar.gz';
        $archivePath = dirname($backupPath) . '/' . $archiveName;
        
        $command = [
            'tar',
            '-czf',
            $archivePath,
            '-C',
            dirname($backupPath),
            basename($backupPath),
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception('Compresión fallida: ' . $process->getErrorOutput());
        }

        // Eliminar directorio original
        File::deleteDirectory($backupPath);
        
        $compressedSize = File::size($archivePath);
        $compressionRatio = round((1 - ($compressedSize / $backupInfo['size'])) * 100, 2);
        
        $backupInfo['compressed_path'] = $archivePath;
        $backupInfo['original_size'] = $backupInfo['size'];
        $backupInfo['compressed_size'] = $compressedSize;
        $backupInfo['compression_ratio'] = $compressionRatio;
        
        $this->line("   ✅ Backup comprimido: " . $this->formatBytes($compressedSize) . " (ahorro: {$compressionRatio}%)");
    }

    private function cleanupOldBackups(int $retentionDays, bool $dryRun): void
    {
        $this->info("🧹 Limpiando backups antiguos (>{$retentionDays} días)...");

        $backupsPath = storage_path('backups');
        
        if (!File::exists($backupsPath)) {
            $this->line("   ℹ️  No hay directorio de backups para limpiar");
            return;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $deletedCount = 0;
        $deletedSize = 0;

        $items = File::allFiles($backupsPath);
        $directories = File::directories($backupsPath);

        // Procesar archivos y directorios
        foreach (array_merge($items, $directories) as $item) {
            $itemPath = is_string($item) ? $item : $item->getRealPath();
            $lastModified = Carbon::createFromTimestamp(File::lastModified($itemPath));
            
            if ($lastModified->lt($cutoffDate)) {
                if ($dryRun) {
                    $size = is_dir($itemPath) ? $this->getDirectorySize($itemPath) : File::size($itemPath);
                    $this->line("   🔍 DRY-RUN: Eliminaría " . basename($itemPath) . " (" . $this->formatBytes($size) . ")");
                    $deletedCount++;
                    $deletedSize += $size;
                } else {
                    if (is_dir($itemPath)) {
                        $size = $this->getDirectorySize($itemPath);
                        File::deleteDirectory($itemPath);
                    } else {
                        $size = File::size($itemPath);
                        File::delete($itemPath);
                    }
                    
                    $deletedCount++;
                    $deletedSize += $size;
                }
            }
        }

        if ($deletedCount > 0) {
            $action = $dryRun ? 'eliminarían' : 'eliminados';
            $this->line("   ✅ {$deletedCount} backups antiguos {$action}: " . $this->formatBytes($deletedSize));
        } else {
            $this->line("   ℹ️  No hay backups antiguos para eliminar");
        }
    }

    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        foreach (File::allFiles($directory) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function showBackupSummary(array $backupInfo, bool $dryRun): void
    {
        $this->newLine();
        $this->info('📋 Resumen del backup:');
        
        $data = [
            ['Timestamp', $backupInfo['timestamp']],
            ['Tipo', $backupInfo['type']],
            ['Componentes', implode(', ', $backupInfo['components'])],
            ['Archivos', number_format($backupInfo['files_count'])],
        ];

        if (isset($backupInfo['compressed_size'])) {
            $data[] = ['Tamaño original', $this->formatBytes($backupInfo['original_size'])];
            $data[] = ['Tamaño comprimido', $this->formatBytes($backupInfo['compressed_size'])];
            $data[] = ['Ratio de compresión', $backupInfo['compression_ratio'] . '%'];
        } else {
            $data[] = ['Tamaño', $this->formatBytes($backupInfo['size'])];
        }

        if (!$dryRun) {
            $finalPath = $backupInfo['compressed_path'] ?? $backupInfo['path'];
            $data[] = ['Ubicación', $finalPath];
        }

        $this->table(['Métrica', 'Valor'], $data);
    }

    private function saveBackupMetadata(array $backupInfo): void
    {
        $metadataFile = storage_path('backups/backup-history.json');
        
        $history = [];
        if (File::exists($metadataFile)) {
            $history = json_decode(File::get($metadataFile), true) ?? [];
        }

        $history[] = array_merge($backupInfo, [
            'created_at' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);

        // Mantener solo los últimos 100 registros
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        File::put($metadataFile, json_encode($history, JSON_PRETTY_PRINT));
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
