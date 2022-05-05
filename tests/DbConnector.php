<?php

namespace Jtrw\DAO\Tests;

use Jtrw\DAO\DataAccessObject;
use Jtrw\DAO\DataAccessObjectInterface;
use RuntimeException;
use PDO;

class DbConnector
{
    public const DRIVER_MYSQL = "mysql";
    
    /**
     * @var DataAccessObjectInterface[]
     */
    public static array $db = [
        self::DRIVER_MYSQL => null
    ];
    
    public static array $sourcePDO = [
        self::DRIVER_MYSQL => null
    ];
    
    public static function init()
    {
        static::$db[static::DRIVER_MYSQL] = self::initMysql();
    }
    
    private static function initMysql(): DataAccessObjectInterface
    {
        $dbName = getenv('MYSQL_DATABASE');
        $dsn = "mysql:dbname=dao;port=3306;host=dao_mariadb";

        $db = new \PDO(
            $dsn,
            getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD')
        );
    
        static::$sourcePDO[static::DRIVER_MYSQL] = $db;
        
        return DataAccessObject::factory($db);
    }
    
    public static function getInstance(string $driver = self::DRIVER_MYSQL): DataAccessObjectInterface
    {
        if (null !== static::$db[$driver]) {
            return static::$db[$driver];
        }
        
        throw new RuntimeException("Driver Not be Initialized");
    }
    
    public static function getSourcePdo(string $driver = self::DRIVER_MYSQL): PDO
    {
        if (null !== static::$sourcePDO[$driver]) {
            return static::$sourcePDO[$driver];
        }
    
        throw new RuntimeException("PDO Not be Initialized");
    }
}