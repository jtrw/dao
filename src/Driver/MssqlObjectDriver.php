<?php
namespace Jtrw\DAO\Driver;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Class MssqlObjectDriver
 * @package Jtrw\DAO\Driver
 */
class MssqlObjectDriver extends AbstractObjectDriver
{
    /**
     * @override
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

        $columnsSection = implode(', ', $queryColumns);
        $fromSection    = $from.' '.implode(' ', $joins);

        $sql = "SELECT ".$columnsSection;

        if ($limit) {
            $orderBySection = $orderBy ? implode(", ", $orderBy):array_shift($columns);

            $sql .= ", ROW_NUMBER() OVER (ORDER BY ".$orderBySection.") AS __row_num ";
        }

        $sql .= " FROM ".$fromSection;

        if ($where) {
            $sql .= " WHERE ".implode(" AND ", $where);
        }

        if ($groupBy) {
            $sql .= " GROUP BY ".implode(", ", $groupBy);
        }

        if ($having) {
            $sql .= " HAVING ".implode(" AND ", $having);
        }

        if ($orderBy && !$limit) {
            $sql .= " ORDER BY ".implode(", ", $orderBy);
        }

        if ($limit) {
            if (!$offset) $offset = 0;
            $sql = "SELECT ".
                "t.* ".
                "FROM ".
                "(".$sql.") AS t ".
                "WHERE t.__row_num BETWEEN ".$offset." AND ".$limit.
                " ORDER BY ".
                "t.__row_num";
        }

        return $sql;
    } // end createSelectQuery
    
    /**
     * @param string $name
     * @return string
     */
    public function quoteTableName($name): string
    {
        return '['.$name.']';
    } // end quoteTableName
    
    /**
     * @param string $key
     * @return string|string[]
     */
    public function quoteColumnName(string $key): string
    {
        // FIXME:
        $reserved = ['sum', 'avg', 'count'];
        $regExp = "#".implode("\(|", $reserved)."\(#Umis";
        if (preg_match($regExp, $key)) {
            return $key;
        }

        $key = "[".$key."]";
        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "].[", $key);
        }
        
        return $key;
    } // end quoteColumnName

    // XXX: Dirty hack for SQL Server
    /**
     * @param DataAccessObjectInterface $object
     * @param string $query
     * @param int $col
     * @param int $page
     * @return int[]
     * @throws DatabaseException
     */
    public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array
    {
        $result = [
            'cnt' => 0
        ];

        if ($page !== 0) {
            $page -= 1;
        }

        $startLimit = ($page * $col) + 1;
        $endLimit = ($page * $col) + $col;

        if (!preg_match("#ORDER BY(?<order_by>.*$)#Umis", $query, $match)) {
            throw new DatabaseException("ORDER BY statement is required for getSplitOnPages");
        }

        $orderBy = $match['order_by'];
        $injectionStatement = ", ROW_NUMBER() OVER (ORDER BY ".$orderBy.") AS __row_num, ".
                              "COUNT(*) OVER() AS __total FROM ";
        $query = preg_replace("#ORDER BY(.*$)#Umis", "", $query);
        $query = preg_replace("#FROM#Umis", $injectionStatement, $query);

        $sqlWrapper = "SELECT ".
                "t.* ".
            "FROM ".
                "(".$query.") AS t ".
            "WHERE ".
                "t.__row_num BETWEEN ".$startLimit." AND ".$endLimit." ".
            "ORDER BY ".
                "t.__row_num";

        $result['rows'] = $object->getAll($sqlWrapper)->toNative();

        foreach ($result['rows'] as &$row) {
            $result['cnt'] = $row['__total'];
            unset($row['__row_num'], $row['__total']);
        }

        $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

        return $result;
    }// end getSplitOnPages
    
    /**
     * @param DataAccessObjectInterface $object
     * @param string $tableName
     * @return array
     * @throws DatabaseException
     */
    public function getTableIndexes(DataAccessObjectInterface $object, string $tableName): array
    {
        $sql = "SELECT
                    a.name AS Index_Name,
                    OBJECT_NAME(a.object_id) as table_name,
                    COL_NAME(b.object_id,b.column_id) AS Column_Name,
                    b.index_column_id,
                    b.key_ordinal,
                    b.is_included_column
                FROM
                     sys.indexes AS a
                    INNER JOIN
                     sys.index_columns AS b
                           ON a.object_id = b.object_id AND a.index_id = b.index_id
                    WHERE
                            a.is_hypothetical = 0 AND
                     a.object_id = OBJECT_ID('".$tableName."')";

        return $object->getAll($sql)->toNative();
    } // end getTableIndexes
    
    /**
     * @param DataAccessObjectInterface $object
     * @param bool $isEnable
     */
    public function setForeignKeyChecks(DataAccessObjectInterface $object, bool $isEnable = true)
    {
        $sql = 'sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"';
    
        if ($isEnable) {
            $sql = 'sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"';
        }
    
        $object->query($sql);
    } // end setForeignKeyChecks
    
    /**
     * @param DataAccessObjectInterface $object
     * @return array
     * @throws DatabaseException
     */
    public function getTables(DataAccessObjectInterface $object): array
    {
        return $object->getCol("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES")->toNative();
    } // end getTables
}