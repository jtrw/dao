<?php

namespace Jtrw\DAO\Query;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Class Query
 * @package Jtrw\DAO\Query
 */
class Query implements QueryInterface
{
    /**
     * @var DataAccessObjectInterface
     */
    protected DataAccessObjectInterface $connection;

    /**
     * @var array
     */
    protected array $columns = [];

    /**
     * @var string
     */
    protected string $table = "";

    /**
     * @var array
     */
    protected array $search = [];

    /**
     * @var array
     */
    protected array $groupBy = [];

    /**
     * @var array
     */
    protected array $orderBy = [];

    /**
     * @var int
     */
    protected ?int $offset = null;

    /**
     * @var int
     */
    protected ?int $limit = null;

    /**
     * @var array
     */
    protected array $joins = [];

    /**
     * @var array
     */
    protected array $having = [];

    /**
     * Query constructor.
     * @param DataAccessObjectInterface $connection
     */
    public function __construct(DataAccessObjectInterface $connection)
    {
        $this->connection = $connection;
    } // end __construct

    /**
     * @param string $name
     * @param string|null $alias
     * @return QueryInterface
     */
    public function select(string $name, ?string $alias = null): QueryInterface
    {
        $columnName = $alias ?? $name;
        $this->columns[$columnName] = $name;

        return $this;
    } // end select

    /**
     * @param string $name
     * @return QueryInterface
     */
    public function table(string $name): QueryInterface
    {
        $this->table = $name;

        return $this;
    } // end table

    /**
     * @param array $search
     * @return QueryInterface
     */
    public function where(array $search): QueryInterface
    {
        $this->search = array_merge_recursive($this->search, $search);

        return $this;
    } // end where

    /**
     * @param array $search
     * @return QueryInterface
     */
    public function having(array $search): QueryInterface
    {
        $this->having = array_merge_recursive($this->having, $search);

        return $this;
    } // end having

    /**
     * @param string $join
     * @return QueryInterface
     */
    public function join(string $join): QueryInterface
    {
        $this->joins[] = $join;

        return $this;
    } // end join

    /**
     * @param array $joins
     * @return QueryInterface
     */
    public function joins(array $joins): QueryInterface
    {
        $this->joins = array_merge_recursive($this->joins, $joins);

        return $this;
    } // end joins

    /**
     * @param string $columnName
     * @return QueryInterface
     */
    public function groupBy(string $columnName): QueryInterface
    {
        $this->groupBy[$columnName] = $columnName;

        return $this;
    } // end groupBy

    /**
     * @param string $columnName
     * @return QueryInterface
     */
    public function orderBy(string $columnName): QueryInterface
    {
        $this->orderBy[$columnName] = $columnName;

        return $this;
    } // end orderBy

    /**
     * @param int $limit
     * @param int|null $offset
     * @return QueryInterface
     */
    public function limit(int $limit, ?int $offset = null): QueryInterface
    {
        $this->offset = $offset;
        $this->limit = $limit;

        return $this;
    } // end limit

    /**
     * @return string
     * @throws DatabaseException
     */
    public function getQuery(): string
    {
        $driver = $this->connection->getDriver();

        $where = $this->connection->getSqlCondition($this->search);
        $having = $this->connection->getSqlCondition($this->having);

        if (!$this->table) {
            throw new DatabaseException("Table Not Found From Query");
        }

        return $driver->createSelectQuery(
            $this->columns,
            $this->table,
            $this->joins,
            $where,
            $this->orderBy,
            $this->limit,
            $this->offset,
            $this->groupBy,
            $having
        );
    } // end getQuery

    /**
     * @return array|null
     * @throws DatabaseException
     */
    public function fetch(): ?array
    {
        return $this->connection->getRow($this->getQuery())->toNative();
    } // end fetch

    /**
     * @return array
     * @throws DatabaseException
     */
    public function fetchAll(): array
    {
        return $this->connection->getAll($this->getQuery())->toNative();
    } // end fetchAll

}
