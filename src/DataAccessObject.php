<?php
namespace Jtrw\DAO;

use Jtrw\DAO\Exceptions\DatabaseException;

abstract class DataAccessObject implements DataAccessObjectFactoryInterface
{
    private static $_instances;

    public static function factory(object $connection): DataAccessObjectInterface
    {
        if ($connection instanceof DataAccessObjectInterface) {
            return $connection;
        }

        $className = static::getAdapterClassName($connection);

        if (!$className) {
            throw new DatabaseException('Not found an adapter class for storage connection.');
        }

        return static::create($className, $connection);
    } // end factory

    public static function create(string $className, object $connection = null): DataAccessObjectInterface
    {
        $className = 'Jtrw\DAO\\'.$className;
        if (!class_exists($className)) {
            throw new DatabaseException('Not found an object adapter class: '.$className);
        }

        return new $className($connection);
    } // end create

    public static function getAdapterClassName(object $connection): string
    {
        $connectionClassName = get_class($connection);

        switch ($connectionClassName) {
            case 'PDO':
                $adapterName = 'PDO';
                break;

            default:
                throw new DatabaseException("Adapter Not Found");
        }

        return 'Object' . $adapterName . 'Adapter';
    } // end getAdapterClassName

    public static function &getInstance(string $name, object $connection): DataAccessObjectInterface
    {
        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }

        self::$_instances[$name] = self::createInstance($name, $connection);

        return self::$_instances[$name];
    } // end getInstance

    public static function createInstance(string $name, object $connection): DataAccessObjectInterface
    {
        $adapter = self::factory($connection);

        $className = self::getClassName($name);

        if (!class_exists($className)) {
            throw new DatabaseException('Not found object class: '.$className);
        }
        $className = 'Jtrw\DAO\\'.$className;

        return new $className($adapter);
    } // end createInstance
}
