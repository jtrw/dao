<?php
namespace Jtrw\DAO\Driver;

use Jtrw\DAO\DataAccessObjectInterface;

/**
 * Interface ObjectDriverInterface
 * @package Jtrw\DAO\Driver
 */
interface ObjectDriverInterface
{
    /**
     * @param string $name
     * @return string
     */
    public function quoteTableName(string $name): string;
    
    /**
     * @param string $name
     * @return string
     */
    public function quoteColumnName(string $name): string;
    
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
    ): string;
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $query
     * @param int $col
     * @param int $page
     * @return array
     */
    public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array;
    
    /**
     * @param ObjectAdapterInterface $object
     * @param string $tableName
     * @return array
     */
    public function getTableIndexes(DataAccessObjectInterface $object, string $tableName): array;
    
    /**
     * @param ObjectAdapterInterface $object
     * @param bool $isEnable
     * @return mixed
     */
    public function setForeignKeyChecks(DataAccessObjectInterface $object, bool $isEnable = true);
    
    /**
     * @param ObjectAdapterInterface $object
     * @return array
     */
    public function getTables(DataAccessObjectInterface $object): array;
}