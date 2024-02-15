<?php
namespace Jtrw\DAO\Driver;

use PDO;
use Jtrw\DAO\DataAccessObjectInterface;

/**
 * Class PgsqlObjectDriver
 * @package Jtrw\DAO\Driver
 */
class PgsqlObjectDriver extends AbstractObjectDriver
{
    /**
     * PgsqlObjectDriver constructor.
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $db->exec("SET NAMES 'utf8mb4'");
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
     * @param string $name
     * @return string
     */
    public function quoteColumnName(string $name): string
    {
        $name = "\"".$name."\"";
        if (strpos($name, '.') !== false) {
            $name = str_replace(".", "\".\"", $name);
        }
        
        return $name;
    } // end quoteColumnName
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $tableName
     * @return array
     */
    public function getTableIndexes(DataAccessObjectInterface $object, string $tableName): array
    {
        $sql = "SELECT indexname as table, indexdef FROM pg_indexes WHERE tablename = ".$object->quote($tableName);

        return $object->getAll($sql)->toNative();
    } // end getTableIndexes
    
    /**
     * @param DataAccessObjectInterface $object
     * @param bool $isEnable
     * @return mixed|void
     */
    public function setForeignKeyChecks(DataAccessObjectInterface $object, bool $isEnable = true)
    {
        $tables = $object->getTables();
    
        $status = 'DISABLE';
        if ($isEnable) {
            $status = 'ENABLE';
        }
    
        foreach ($tables as $table) {
            $sql = "ALTER TABLE ".$table." ".$status." TRIGGER USER";
            $object->query($sql);
        }
    } // end setForeignKeyChecks
    
    /**
     * @param DataAccessObjectInterface $object
     * @return array
     */
    public function getTables(DataAccessObjectInterface $object): array
    {
        return $object->getCol("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")->toNative();
    } // end getTables
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $table
     * @return int
     */
    public function deleteTable(DataAccessObjectInterface $object, string $table): int
    {
        $sql = "DROP TABLE ".$this->quoteTableName($table)." CASCADE";
        
        return $object->query($sql);
    } // end deleteTable
}
