<?php

namespace Jtrw\DAO\Tests;

use Jtrw\DAO\DataAccessObject;

class DbConnector
{
    public static $db;
    
    public static function init()
    {
        $dbName = getenv('MYSQL_DATABASE');
        $dsn = "mysql:dbname={$dbName};port=3306;host=dao_mariadb";
    
        $db = new \PDO(
            $dsn,
            getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD')
        );
        
        static::$db = DataAccessObject::factory($db);
    }
    
    public static function get()
    {
        return static::$db;
    }
}