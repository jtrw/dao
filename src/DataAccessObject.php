<?php
namespace Jtrw\DAO;

use Jtrw\DAO\Exceptions\DatabaseException;

abstract class DataAccessObject implements DataAccessObjectFactoryInterface
{
    public static function factory(object $connection): DataAccessObjectInterface
    {
        if ($connection instanceof DataAccessObjectInterface) {
            return $connection;
        }

        $className = static::getAdapterClassName($connection);

        return static::create($className, $connection);
    } // end factory

    public static function create(string $className, ?object $connection = null): DataAccessObjectInterface
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
}
