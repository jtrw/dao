<?php
namespace Jtrw\DAO;


interface DataAccessObjectFactoryInterface
{
    public static function factory(object $connection): DataAccessObjectInterface;

    public static function create(string $className, object $connection = null): DataAccessObjectInterface;

    public static function getAdapterClassName(object $connection): string;

    public static function &getInstance(string $name, object $connection): DataAccessObjectInterface;

    public static function createInstance(string $name, object $connection): DataAccessObjectInterface;
}
