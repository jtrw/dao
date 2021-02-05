<?php
namespace Jtrw\DAO;

use Jtrw\DAO\Driver\ObjectDriverInterface;

/**
 * Interface DataAccessObjectInterface
 * @package Jtrw\DAO
 */
interface DataAccessObjectInterface
{
    /**
     * @param object $connection
     * @return ObjectAdapterInterface
     */
    public static function factory(object $connection): ObjectAdapterInterface;
    
    /**
     * @param string $className
     * @param object|null $connection
     * @return ObjectAdapterInterface
     */
    public static function create(string $className, object $connection = null): ObjectAdapterInterface;
    
    /**
     * @param object $connection
     * @return string
     */
    public static function getAdapterClassName(object $connection): string;
    
    /**
     * @param string $name
     * @param object $connection
     * @return ObjectAdapterInterface
     */
    public static function &getInstance(string $name, object $connection): ObjectAdapterInterface;
    
    /**
     * @param string $name
     * @param object $connection
     * @return ObjectAdapterInterface
     */
    public static function createInstance(string $name, object $connection): ObjectAdapterInterface;

}