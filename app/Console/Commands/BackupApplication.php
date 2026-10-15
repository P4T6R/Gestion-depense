<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupApplication extends Command
{
    protected $signature = 'backup:application {--dry-run : Vérifier la configuration sans créer d’archive}';

    protected $description = 'Sauvegarde la base MySQL et les justificatifs privés dans une archive chiffrable hors dépôt';

    public function handle(): int
    {
        $destination = (string) config('backup.path');
        $privatePath = storage_path('app/private');

        if ($this->option('dry-run')) {
            $this->info('Destination : '.$destination);
            $this->info('Justificatifs : '.$privatePath);
            $this->info('Rétention : '.config('backup.retention_days').' jours');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($destination, 0750, true);
        $temporary = storage_path('app/backup-temp/'.Str::uuid());
        File::ensureDirectoryExists($temporary, 0700, true);
        $dumpPath = $temporary.'/database.sql';
        $credentialsPath = $temporary.'/mysql.cnf';

        try {
            File::put($credentialsPath, $this->mysqlCredentials());
            @chmod($credentialsPath, 0600);

            $database = config('database.connections.mysql.database');
            $process = new Process([
                (string) config('backup.mysqldump_binary'),
                '--defaults-extra-file='.$credentialsPath,
                '--single-transaction', '--quick', '--routines', '--triggers',
                '--result-file='.$dumpPath,
                (string) $database,
            ]);
            $process->setTimeout(900);
            $process->mustRun();

            $archivePath = rtrim($destination, '\\/').DIRECTORY_SEPARATOR.'gestion-depense-'.now()->format('Ymd-His').'.zip';
            $archive = new ZipArchive();
            if ($archive->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new \RuntimeException('Impossible de créer l’archive de sauvegarde.');
            }
            $archive->addFile($dumpPath, 'database.sql');
            if (File::isDirectory($privatePath)) {
                foreach (File::allFiles($privatePath) as $file) {
                    $archive->addFile($file->getPathname(), 'private/'.$file->getRelativePathname());
                }
            }
            if (! $archive->close()) {
                throw new \RuntimeException('Impossible de finaliser l’archive de sauvegarde.');
            }

            $this->removeExpiredBackups($destination);
            $this->info('Sauvegarde créée : '.$archivePath);

            return self::SUCCESS;
        } finally {
            File::deleteDirectory($temporary);
        }
    }

    private function mysqlCredentials(): string
    {
        $connection = config('database.connections.mysql');
        $escape = fn ($value) => str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value);

        return "[client]\n"
            .'host="'.$escape($connection['host']).'"'."\n"
            .'port="'.$escape($connection['port']).'"'."\n"
            .'user="'.$escape($connection['username']).'"'."\n"
            .'password="'.$escape($connection['password']).'"'."\n";
    }

    private function removeExpiredBackups(string $destination): void
    {
        $cutoff = now()->subDays((int) config('backup.retention_days'))->getTimestamp();
        foreach (File::glob(rtrim($destination, '\\/').DIRECTORY_SEPARATOR.'gestion-depense-*.zip') as $file) {
            if (File::lastModified($file) < $cutoff) {
                File::delete($file);
            }
        }
    }
}
