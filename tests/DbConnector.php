<?php

namespace Jtrw\DAO\Tests;

use Jtrw\DAO\DataAccessObject;
use Jtrw\DAO\DataAccessObjectInterface;
use RuntimeException;

class DbConnector
{
    public const DRIVER_MYSQL = "mysql";
    
    /**
     * @var DataAccessObjectInterface[]
     */
    public static array $db = [
        self::DRIVER_MYSQL => null
    ];
    
    public static function init()
    {
        static::$db[static::DRIVER_MYSQL] = self::initMysql();
    }
    
    private static function initMysql(): DataAccessObjectInterface
    {
        $dbName = getenv('MYSQL_DATABASE');
        $dsn = "mysql:dbname={$dbName};port=3306;host=127.0.0.1";
    
        $db = new \PDO(
            $dsn,
            getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD')
        );
        return DataAccessObject::factory($db);
    }
    
    public static function getInstance(string $driver = self::DRIVER_MYSQL): DataAccessObjectInterface
    {
        if (null !== static::$db[$driver]) {
            return static::$db[$driver];
        }
        
        throw new RuntimeException("Driver Not be Initialized");
    }
}