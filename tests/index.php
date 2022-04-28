<?php
require __DIR__."/../vendor/autoload.php";

use Jtrw\DAO\DataAccessObject;

$dbName = getenv('MYSQL_DATABASE');
$dsn = "mysql:dbname={$dbName};port=3306;host=dao_mariadb";

$db = new \PDO(
    $dsn,
    getenv('MYSQL_USER'),
    getenv('MYSQL_PASSWORD')
);
$object = DataAccessObject::factory($db);

var_dump($object);