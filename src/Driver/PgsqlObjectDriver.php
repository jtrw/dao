<?php
namespace Jtrw\DAO\Driver;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\ObjectAdapterInterface;

/**
 * Class PgsqlObjectDriver
 * @package Jtrw\DAO\Driver
 */
class PgsqlObjectDriver extends AbstractObjectDriver
{
    /**
     * PgsqlObjectDriver constructor.
     * @param $db
     */
    public function __construct($db)
    {
        $db->query("SET NAMES 'utf8'");
    } // end __construct
    
    /**
     * @param string $name
     * @return string
     */
    public function quoteTableName(string $name): string
    {
        return '"'.$name.'"';
    } // end quoteTableName
    
    /**
     * @param string $key
     * @return string
     */
    public function quoteColumnName(string $key): string
    {
        $key = "\"".$key."\"";
        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "\".\"", $key);
        }
        
        return $key;
    } // end quoteColumnName
    
    /**
     * @param ObjectAdapterInterface $object
     * @param string $tableName
     * @return array
     * @throws \Jtrw\DAO\Exceptions\DatabaseException
     */
    public function getTableIndexes(ObjectAdapterInterface $object, string $tableName): array
    {
        $sql = "SELECT indexname as table, indexdef FROM pg_indexes WHERE tablename = ".$object->quote($tableName);

        return $object->getAll($sql);
    } // end getTableIndexes
    
    /**
     * @param ObjectAdapterInterface $object
     * @param bool $isEnable
     * @return mixed|void
     */
    public function setForeignKeyChecks(ObjectAdapterInterface $object, bool $isEnable = true)
    {
        $tables = $object->getTables();
    
        $status = 'DISABLE';
        if ($isEnable) {
            $status = 'ENABLE';
        }
    
        foreach ($tables as $table) {
            $sql = "ALTER TABLE ".$table." ".$status." TRIGGER USER";
            $this->db->query($sql);
        }
    } // end setForeignKeyChecks
    
    /**
     * @param ObjectAdapterInterface $object
     * @return array
     * @throws \Jtrw\DAO\Exceptions\DatabaseException
     */
    public function c(ObjectAdapterInterface $object): array
    {
        return $object->getCol("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
    } // end getTables
}