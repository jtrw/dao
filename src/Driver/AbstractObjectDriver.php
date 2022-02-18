<?php
namespace Jtrw\DAO\Driver;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Class AbstractObjectDriver
 * @package Jtrw\DAO\Driver
 */
abstract class AbstractObjectDriver implements ObjectDriverInterface
{
    /**
     * @param array $columns
     * @param string $from
     * @param array|null $joins
     * @param array|null $where
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @param array|null $groupBy
     * @param array|null $having
     * @return string
     */
    public function createSelectQuery(
        array $columns,
        string $from,
        ?array $joins = null,
        ?array $where = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $groupBy = null,
        ?array $having = null
    ): string
    {
        $sql = "SELECT ";

        $queryColumns = [];
        foreach ($columns as $columnAlias => $columnName) {
            if ($columnName == $columnAlias) {
                $columnAlias = null;
            }

            $columnName = $this->quoteColumnName($columnName);

            $queryColumns[] = $columnAlias ? $columnName.' AS '.$columnAlias : $columnName;
        }

        if (!$queryColumns) {
            $queryColumns[] = '*';
        }

        $sql .= implode(", ", $queryColumns)." FROM ".$from;

        if ($joins) {
            $sql .= " ".implode(" ", $joins);
        }

        if ($where) {
            $sql .= " WHERE ".implode(" AND ", $where);
        }

        if ($groupBy) {
            $sql .= " GROUP BY ".implode(", ", $groupBy);
        }

        if ($having) {
            $sql .= " HAVING ".implode(" AND ", $having);
        }

        if ($orderBy) {
            $sql .= " ORDER BY ".implode(", ", $orderBy);
        }

        if ($limit) {
            $sql .= " LIMIT ".$limit;
            if ($offset) {
                $sql .= " OFFSET ".$offset;
            }
        }

        return $sql;
    } // end createSelectQuery
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $table
     * @return int
     */
    public function deleteTable(DataAccessObjectInterface $object, string $table): int
    {
        $sql = "DROP TABLE ".$this->quoteTableName($table);

        return $object->query($sql);
    } // end deleteTable
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $query
     * @param int $col
     * @param int $page
     * @return array
     * @throws DatabaseException
     */
    public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array
    {
        throw new DatabaseException("Undefined method");
    } // end getSplitOnPages

}