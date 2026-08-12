<?php

use Aldhi88\StarterKit\Installation\StarterDatabaseProvisioner;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

function sqliteProvisioner(string $database): StarterDatabaseProvisioner
{
    config([
        'database.default' => 'starterkit-provisioning',
        'database.connections.starterkit-provisioning' => [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('starterkit-provisioning');

    return new StarterDatabaseProvisioner(app(ConnectionFactory::class));
}

it('creates a missing sqlite database and connects to it', function () {
    $database = sys_get_temp_dir().'/starterkit-provisioning-'.bin2hex(random_bytes(8)).'.sqlite';
    $provisioner = sqliteProvisioner($database);

    try {
        $result = $provisioner->connectOrCreate();

        expect($result->created)->toBeTrue()
            ->and($result->database)->toBe($database)
            ->and(is_file($database))->toBeTrue()
            ->and(DB::connection('starterkit-provisioning')->getPdo())->not->toBeNull();
    } finally {
        DB::purge('starterkit-provisioning');

        if (isset($result)) {
            $provisioner->rollback($result);
        }
    }
});

it('keeps an existing sqlite database', function () {
    $database = sys_get_temp_dir().'/starterkit-existing-'.bin2hex(random_bytes(8)).'.sqlite';
    touch($database);
    $provisioner = sqliteProvisioner($database);

    try {
        $result = $provisioner->connectOrCreate();

        expect($result->created)->toBeFalse()
            ->and(is_file($database))->toBeTrue();

        $provisioner->rollback($result);

        expect(is_file($database))->toBeTrue();
    } finally {
        DB::purge('starterkit-provisioning');
        @unlink($database);
    }
});

it('removes a database created by the installer during rollback', function () {
    $database = sys_get_temp_dir().'/starterkit-rollback-'.bin2hex(random_bytes(8)).'.sqlite';
    $provisioner = sqliteProvisioner($database);
    $result = $provisioner->connectOrCreate();

    DB::connection('starterkit-provisioning')->statement('CREATE TABLE rollback_test (id INTEGER)');
    $provisioner->rollback($result);

    expect(is_file($database))->toBeFalse();
});

it('creates a missing server database with the driver-specific safe identifier', function (
    string $driver,
    string $maintenanceDatabase,
    string $createStatement,
): void {
    $connectionName = 'starterkit-server-provisioning';
    $database = 'company_test';
    config([
        'database.default' => $connectionName,
        "database.connections.{$connectionName}" => [
            'driver' => $driver,
            'host' => '127.0.0.1',
            'port' => 1234,
            'database' => $database,
            'username' => 'starter',
            'password' => 'redacted',
            'maintenance_database' => $maintenanceDatabase,
        ],
    ]);

    $target = Mockery::mock(Connection::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getPdo')->once()->andReturn(new PDO('sqlite::memory:'));
    });
    $statement = Mockery::mock(PDOStatement::class, function (MockInterface $mock) use ($database): void {
        $mock->shouldReceive('execute')->once()->with([$database])->andReturnTrue();
        $mock->shouldReceive('fetchColumn')->once()->andReturnFalse();
    });
    $pdo = Mockery::mock(PDO::class, function (MockInterface $mock) use ($statement): void {
        $mock->shouldReceive('prepare')->once()->andReturn($statement);
    });
    $admin = Mockery::mock(Connection::class, function (MockInterface $mock) use ($pdo, $createStatement): void {
        $mock->shouldReceive('getPdo')->once()->andReturn($pdo);
        $mock->shouldReceive('unprepared')->once()->with($createStatement)->andReturnTrue();
        $mock->shouldReceive('disconnect')->once();
    });
    $factory = Mockery::mock(ConnectionFactory::class, function (MockInterface $mock) use ($admin, $maintenanceDatabase): void {
        $mock->shouldReceive('make')
            ->once()
            ->with(Mockery::on(fn (array $config): bool => $config['database'] === $maintenanceDatabase), 'starterkit-admin')
            ->andReturn($admin);
    });

    DB::shouldReceive('connection')->once()->with($connectionName)->andThrow(new RuntimeException('unknown database'));
    DB::shouldReceive('purge')->once()->with($connectionName);
    DB::shouldReceive('connection')->once()->with($connectionName)->andReturn($target);

    $result = (new StarterDatabaseProvisioner($factory))->connectOrCreate();

    expect($result->created)->toBeTrue()
        ->and($result->driver)->toBe($driver)
        ->and($result->database)->toBe($database);
})->with([
    'mysql' => ['mysql', '', 'CREATE DATABASE `company_test`'],
    'mariadb' => ['mariadb', '', 'CREATE DATABASE `company_test`'],
    'postgresql' => ['pgsql', 'postgres', 'CREATE DATABASE "company_test"'],
    'sql server' => ['sqlsrv', 'master', 'CREATE DATABASE [company_test]'],
]);
