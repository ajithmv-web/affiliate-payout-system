<?php

class Database
{
    private static ?PDO $connection = null;
    private static array $config = [];

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::loadConfig();
            self::connect();
        }
        return self::$connection;
    }

    private static function loadConfig(): void
    {
        $configPath = __DIR__ . '/../config/database.php';
        
        if (!file_exists($configPath)) {
            throw new RuntimeException('Database configuration file not found');
        }

        self::$config = require $configPath;
    }

    private static function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['database'],
                self::$config['charset']
            );

            self::$connection = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                self::$config['options']
            );

        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection failed. Please check your configuration.');
        }
    }

    public static function closeConnection(): void
    {
        self::$connection = null;
    }

    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log('Database connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
