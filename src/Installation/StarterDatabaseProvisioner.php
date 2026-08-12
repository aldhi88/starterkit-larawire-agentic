<?php

namespace Aldhi88\StarterKit\Installation;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class StarterDatabaseProvisioner
{
    public function __construct(
        private readonly ConnectionFactory $factory,
    ) {}

    public function connectOrCreate(): StarterDatabaseProvisioning
    {
        $connectionName = (string) config('database.default');
        $config = (array) config("database.connections.{$connectionName}", []);
        $driver = (string) ($config['driver'] ?? '');
        $database = (string) ($config['database'] ?? '');

        if ($connectionName === '' || $driver === '' || $database === '') {
            throw new RuntimeException('Konfigurasi koneksi dan nama database wajib diisi di .env.');
        }

        try {
            DB::connection($connectionName)->getPdo();

            return new StarterDatabaseProvisioning(false, $connectionName, $driver, $database);
        } catch (Throwable $targetException) {
            // A missing target database is provisioned below. Invalid server credentials,
            // permissions, or other connection failures still stop the installer.
        }

        $created = match ($driver) {
            'sqlite' => $this->createSqliteDatabase($database, $targetException),
            'mysql', 'mariadb' => $this->createServerDatabase(
                $config,
                $database,
                '',
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                $this->quoteIdentifier($database, '`', '`'),
                $targetException,
            ),
            'pgsql' => $this->createServerDatabase(
                $config,
                $database,
                (string) ($config['maintenance_database'] ?? 'postgres'),
                'SELECT datname FROM pg_database WHERE datname = ?',
                $this->quoteIdentifier($database, '"', '"'),
                $targetException,
            ),
            'sqlsrv' => $this->createServerDatabase(
                $config,
                $database,
                'master',
                'SELECT name FROM sys.databases WHERE name = ?',
                $this->quoteIdentifier($database, '[', ']'),
                $targetException,
            ),
            default => throw new RuntimeException(
                "Driver database {$driver} belum mendukung pembuatan database otomatis. ".
                'Buat database secara manual, lalu jalankan installer kembali.',
                previous: $targetException,
            ),
        };

        if (! $created) {
            throw new RuntimeException(
                "Database {$database} sudah ada, tetapi koneksi gagal: {$targetException->getMessage()}",
                previous: $targetException,
            );
        }

        DB::purge($connectionName);

        try {
            DB::connection($connectionName)->getPdo();
        } catch (Throwable $exception) {
            $provisioning = new StarterDatabaseProvisioning(true, $connectionName, $driver, $database);

            try {
                $this->rollback($provisioning);
            } catch (Throwable) {
                // Preserve the actionable target connection error below.
            }

            throw new RuntimeException(
                "Database {$database} berhasil dibuat, tetapi koneksi ulang gagal: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        return new StarterDatabaseProvisioning(true, $connectionName, $driver, $database);
    }

    public function rollback(StarterDatabaseProvisioning $provisioning): void
    {
        if (! $provisioning->created) {
            return;
        }

        DB::purge($provisioning->connection);

        if ($provisioning->driver === 'sqlite') {
            foreach ([$provisioning->database, $provisioning->database.'-shm', $provisioning->database.'-wal'] as $path) {
                if (is_file($path) && ! unlink($path)) {
                    throw new RuntimeException("Database SQLite rollback gagal dihapus: {$path}");
                }
            }

            return;
        }

        $config = (array) config("database.connections.{$provisioning->connection}", []);
        $maintenanceDatabase = match ($provisioning->driver) {
            'mysql', 'mariadb' => '',
            'pgsql' => (string) ($config['maintenance_database'] ?? 'postgres'),
            'sqlsrv' => 'master',
            default => throw new RuntimeException(
                "Driver database {$provisioning->driver} belum mendukung rollback database otomatis.",
            ),
        };
        $identifier = match ($provisioning->driver) {
            'mysql', 'mariadb' => $this->quoteIdentifier($provisioning->database, '`', '`'),
            'pgsql' => $this->quoteIdentifier($provisioning->database, '"', '"'),
            'sqlsrv' => $this->quoteIdentifier($provisioning->database, '[', ']'),
        };
        $admin = $this->adminConnection($config, $maintenanceDatabase);

        try {
            if ($provisioning->driver === 'pgsql') {
                $statement = $admin->getPdo()->prepare(
                    'SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()',
                );
                $statement->execute([$provisioning->database]);
            }

            $admin->unprepared("DROP DATABASE {$identifier}");
        } finally {
            $admin->disconnect();
        }
    }

    private function createSqliteDatabase(string $database, Throwable $targetException): bool
    {
        if ($database === ':memory:') {
            throw new RuntimeException(
                'Koneksi database SQLite memory gagal: '.$targetException->getMessage(),
                previous: $targetException,
            );
        }

        if (is_file($database)) {
            return false;
        }

        $directory = dirname($database);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException(
                "Directory database SQLite tidak tersedia atau tidak dapat ditulis: {$directory}",
                previous: $targetException,
            );
        }

        $handle = @fopen($database, 'x');

        if ($handle === false) {
            throw new RuntimeException(
                "Database SQLite tidak dapat dibuat: {$database}",
                previous: $targetException,
            );
        }

        fclose($handle);

        return true;
    }

    /** @param array<string, mixed> $config */
    private function createServerDatabase(
        array $config,
        string $database,
        string $maintenanceDatabase,
        string $existenceQuery,
        string $identifier,
        Throwable $targetException,
    ): bool {
        try {
            $admin = $this->adminConnection($config, $maintenanceDatabase);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Koneksi ke server database gagal. Periksa host, port, username, password, dan izin akun: '.
                $exception->getMessage(),
                previous: $targetException,
            );
        }

        try {
            $statement = $admin->getPdo()->prepare($existenceQuery);
            $statement->execute([$database]);

            if ($statement->fetchColumn() !== false) {
                return false;
            }

            $admin->unprepared("CREATE DATABASE {$identifier}");

            return true;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Database {$database} belum tersedia dan tidak dapat dibuat otomatis. ".
                'Pastikan akun database memiliki izin CREATE DATABASE: '.$exception->getMessage(),
                previous: $exception,
            );
        } finally {
            $admin->disconnect();
        }
    }

    /** @param array<string, mixed> $config */
    private function adminConnection(array $config, string $database): Connection
    {
        unset($config['read'], $config['write'], $config['sticky']);
        $config['database'] = $database;

        return $this->factory->make($config, 'starterkit-admin');
    }

    private function quoteIdentifier(string $identifier, string $open, string $close): string
    {
        $escaped = str_replace($close, $close.$close, $identifier);

        return $open.$escaped.$close;
    }
}
