<?php
namespace Jtrw\DAO\Driver;

use PDO;
use Jtrw\DAO\DataAccessObjectInterface;

/**
 * Class MysqlObjectDriver
 * @package Jtrw\DAO\Driver
 */
class MysqlObjectDriver extends AbstractObjectDriver
{
    /**
     * MysqlObjectDriver constructor.
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
        return '`'.$name.'`';
    } // end quoteTableName
    
    /**
     * @param string $key
     * @return string
     */
    public function quoteColumnName(string $key): string
    {
        $key = "`".$key."`";

        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "`.`", $key);
            $keys = explode(".", $key);
            if (isset($keys[1]) && $keys[1] === '`*`') {
    
                $keys[1] = trim($keys[1], '`');
                $key = implode(".", $keys);
            }
           
        }
        
        return $key;
    } // end quoteColumnName
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $query
     * @param int $col
     * @param int $page
     * @return array
     */
    public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array
    {
        $result = [];
        if ($page !== 0) {
            $page -= 1;
        }

        // XXX: Fixed it
        if (!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
            $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
        }

        $query .= " LIMIT ".($page * $col).", ".$col;

        $result['rows']    = $object->getAll($query)->toNative();
        $result['cnt']     = $object->getOne('SELECT FOUND_ROWS()')->toNative();
        $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

        return $result;
    }// end getSplitOnPages
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $tableName
     * @return array
     */
    public function getTableIndexes(DataAccessObjectInterface $object, string $tableName): array
    {
        return $object->getAll("SHOW INDEX FROM ".$this->quoteTableName($tableName))->toNative();
    } // end getTableIndexes
    
    /**
     * @param DataAccessObjectInterface $object
     * @param bool $isEnable
     */
    public function setForeignKeyChecks(DataAccessObjectInterface $object, bool $isEnable = true)
    {
        $sql = "SET FOREIGN_KEY_CHECKS=".(int) $isEnable;
    
        $object->query($sql);
    } // end setForeignKeyChecks
    
    /**
     * @param DataAccessObjectInterface $object
     * @return array
     */
    public function getTables(DataAccessObjectInterface $object): array
    {
        return $object->getCol("SHOW TABLES")->toNative();
    } // end getTables
}
