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
     * @throws \Jtrw\DAO\Exceptions\DatabaseException
     */
    public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array
    {
        // Security fix: Validate pagination parameters to prevent resource exhaustion
        $this->_validatePaginationParameters($col, $page);

        $result = [];
        if ($page !== 0) {
            $page -= 1;
        }

        // XXX: Fixed it
        if (!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
            $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
        }

        // Calculate offset with overflow check
        $offset = $page * $col;

        // Additional overflow check
        if ($offset < 0 || $offset > PHP_INT_MAX - $col) {
            throw new \Jtrw\DAO\Exceptions\DatabaseException(
                "Pagination overflow detected: offset calculation resulted in invalid value"
            );
        }

        // Use sprintf for clarity and type safety
        $query .= sprintf(" LIMIT %d, %d", $offset, $col);

        $result['rows']    = $object->getAll($query)->toNative();
        $result['cnt']     = $object->getOne('SELECT FOUND_ROWS()')->toNative();
        $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

        return $result;
    }// end getSplitOnPages

    /**
     * Validates pagination parameters to prevent resource exhaustion and overflow attacks.
     *
     * @param int $col Number of items per page
     * @param int $page Page number
     * @throws \Jtrw\DAO\Exceptions\DatabaseException If parameters are invalid
     * @return void
     */
    private function _validatePaginationParameters(int $col, int $page): void
    {
        // Maximum items per page (configurable, default 1000)
        $maxItemsPerPage = 1000;

        // Validate column count (items per page)
        if ($col <= 0) {
            throw new \Jtrw\DAO\Exceptions\DatabaseException(
                "Invalid pagination: items per page must be positive, got: {$col}"
            );
        }

        if ($col > $maxItemsPerPage) {
            throw new \Jtrw\DAO\Exceptions\DatabaseException(
                "Invalid pagination: items per page ({$col}) exceeds maximum allowed ({$maxItemsPerPage})"
            );
        }

        // Validate page number
        if ($page < 0) {
            throw new \Jtrw\DAO\Exceptions\DatabaseException(
                "Invalid pagination: page number must be non-negative, got: {$page}"
            );
        }

        // Check for potential integer overflow in offset calculation
        // This prevents attacks using very large page numbers
        if ($page > 0 && $col > 0) {
            $maxSafePage = (int) floor(PHP_INT_MAX / $col);
            if ($page > $maxSafePage) {
                throw new \Jtrw\DAO\Exceptions\DatabaseException(
                    "Invalid pagination: page number ({$page}) too large, would cause integer overflow"
                );
            }
        }
    } // end _validatePaginationParameters
    
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
