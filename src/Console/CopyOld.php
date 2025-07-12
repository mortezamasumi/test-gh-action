<?php

namespace Mortezamasumi\CopyDb\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'copy-old:db',
    description: 'Copy database',
)]
class CopyOld extends Command
{
    protected $signature = 'copy-old:db
                            {src : The source database in format user:pass@host}
                            {db : Database name (for both source and destination)}
                            {--sdb= : Source database name}
                            {--scdrv= : Source connection driver (default: mysql)}
                            {--ddb= : Destination database name}
                            {--ddbc= : Destination database connection (default: mysql)}';

    public function handle()
    {
        if (! Str::contains($this->argument('src'), '@')) {
            $this->error('Invalid connection string format. Expected user:pass@host');

            return;
        }

        [$credentials, $host] = explode('@', $this->argument('src'), 2);

        if (empty($host)) {
            $this->error('Host cannot be emptyt');

            return;
        }

        $parts = explode(':', $credentials, 2);

        $user = $parts[0] ?? null;
        $pass = $parts[1] ?? null;

        if (empty($user)) {
            $this->error('Username cannot be empt');

            return;
        }
        // dd($this->argument('db'));
        Config::set('database.connections.read_connection', [
            'driver' => $this->option('scdrv') ?: 'mysql',
            'host' => $host,
            'port' => '3306',
            'database' => $this->option('sdb') ?: $this->argument('db'),
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        $sourceConnection = 'read_connection';
        $databaseName = $this->option('ddb') ?: $this->argument('db');
        $destinationConnection = $this->option('ddbc') ?: 'mysql';

        // dd($this->option('ddb') ?: $this->argument('db'));

        Config::set('database.connections.mysql.database', null);
        DB::reconnect($destinationConnection);
        $result = DB::connection($destinationConnection)
            ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$databaseName]);

        // dd($result);

        if (empty($result)) {
            DB::connection($destinationConnection)->statement("CREATE DATABASE `$databaseName`");
            Config::set('database.connections.mysql.database', $databaseName);
            DB::reconnect($destinationConnection);
            Artisan::call('migrate');
            $this->info("Database '$databaseName' created successfully.");
        } else {
            Config::set('database.connections.mysql.database', $databaseName);
            DB::reconnect($destinationConnection);
            $this->info("Database '$databaseName' already exists.");
        }

        $tables = DB::connection($sourceConnection)->select('SHOW TABLES');

        DB::connection($destinationConnection)->statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_{$databaseName}"};

            if (in_array($tableName, ['migrations'])) {
                $this->info("Ignore processing of table $tableName.");

                continue;
            }

            $this->info("Start processing of table $tableName.");

            $columns = collect(DB::connection($sourceConnection)
                    ->select("SHOW FULL COLUMNS FROM $tableName"))
                ->filter(function ($column) {
                    return strpos($column->Extra ?? '', 'VIRTUAL') === false &&
                        strpos($column->Extra ?? '', 'STORED') === false;
                })
                ->pluck('Field')
                ->toArray();

            $data = DB::connection($sourceConnection)->table($tableName)->select($columns)->get();

            if ($tableName === 'users') {
                $data = collect($data->toArray())->map(function ($item) {
                    $item = (array) $item;

                    if (array_key_exists('account_expires_at', $item)) {
                        $item['expired_at'] = $item['account_expires_at'];
                        unset($item['account_expires_at']);
                    }

                    if (array_key_exists('mobile_verified_at', $item)) {
                        $item['email_verified_at'] = $item['mobile_verified_at'];
                        unset($item['mobile_verified_at']);
                    }

                    if (array_key_exists('otp_code', $item)) {
                        unset($item['otp_code']);
                    }

                    if (array_key_exists('otp_expires_at', $item)) {
                        unset($item['otp_expires_at']);
                    }

                    return $item;
                });
            }

            if ($data->isNotEmpty()) {
                $tableName = match ($tableName) {
                    'mars_questions' => 'mars',
                    'settings' => 'fb_settings',
                    default => $tableName,
                };

                DB::connection($destinationConnection)->table($tableName)->truncate();

                collect($data)
                    ->chunk(1000)
                    ->each(
                        fn ($items) => DB::connection($destinationConnection)
                            ->table($tableName)
                            ->insert(json_decode(json_encode($items), true))
                    );
            }

            $this->info("Copied data for table $tableName.");
        }

        DB::connection($destinationConnection)->statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Database copy completed successfully!');
    }
}
