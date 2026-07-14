<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = strtolower((string) app_config('db.driver', 'mysql'));

        try {
            if ($driver === 'sqlite') {
                if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                    http_response_code(500);
                    exit('SQLite driver missing. Enable/install pdo_sqlite in your PHP runtime.');
                }

                $sqlitePath = (string) app_config('db.sqlite_path', dirname(__DIR__, 2) . '/database/app.sqlite');
                $sqliteDir = dirname($sqlitePath);

                if (!is_dir($sqliteDir)) {
                    mkdir($sqliteDir, 0775, true);
                }

                $isNewDatabase = !file_exists($sqlitePath);

                self::$pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                self::$pdo->exec('PRAGMA foreign_keys = ON');

                if ($isNewDatabase || !self::sqliteHasUsersTable(self::$pdo)) {
                    self::initializeSqliteSchema(self::$pdo);
                }

                return self::$pdo;
            }

            if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
                http_response_code(500);
                exit('MySQL PDO driver missing. Enable/install pdo_mysql or switch db.driver to sqlite.');
            }

            $host = app_config('db.host');
            $port = app_config('db.port');
            $database = app_config('db.database');
            $username = app_config('db.username');
            $password = app_config('db.password');
            $charset = app_config('db.charset', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

            self::$pdo = new PDO($dsn, (string) $username, (string) $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            http_response_code(500);
            if ($driver === 'sqlite') {
                exit('SQLite connection failed: ' . $exception->getMessage());
            }

            exit('Database connection failed: ' . $exception->getMessage());
        }

        return self::$pdo;
    }

    private static function sqliteHasUsersTable(PDO $pdo): bool
    {
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users' LIMIT 1");
        $result = $statement !== false ? $statement->fetch() : false;

        return (bool) $result;
    }

    private static function initializeSqliteSchema(PDO $pdo): void
    {
        $schemaPath = dirname(__DIR__, 2) . '/database/schema.sqlite.sql';

        if (!file_exists($schemaPath)) {
            throw new PDOException('SQLite schema file missing at database/schema.sqlite.sql');
        }

        $schemaSql = (string) file_get_contents($schemaPath);
        $pdo->exec($schemaSql);
    }
}
