<?php

namespace Jtrw\DAO\Query;

/**
 * Interface QueryInterface
 * @package Jtrw\DAO\Query
 */
interface QueryInterface
{
    /**
     * @param string $name
     * @param string|null $alias
     * @return QueryInterface
     */
    public function select(string $name, ?string $alias = null): QueryInterface;
    
    /**
     * @param string $name
     * @return QueryInterface
     */
    public function table(string $name): QueryInterface;
    
    /**
     * @param array $search
     * @return QueryInterface
     */
    public function where(array $search): QueryInterface;
    
    /**
     * @param string $columnName
     * @return QueryInterface
     */
    public function groupBy(string $columnName): QueryInterface;
    
    /**
     * @param string $columnName
     * @return QueryInterface
     */
    public function orderBy(string $columnName): QueryInterface;
    
    /**
     * @param int $limit
     * @param int|null $offset
     * @return QueryInterface
     */
    public function limit(int $limit, ?int $offset = null): QueryInterface;
    
    /**
     * @param string $join
     * @return QueryInterface
     */
    public function join(string $join): QueryInterface;
    
    /**
     * @param array $joins
     * @return QueryInterface
     */
    public function joins(array $joins): QueryInterface;
    
    /**
     * @return string
     */
    public function getQuery(): string;
    
    /**
     * @return array|null
     */
    public function fetch(): ?array;
    
    /**
     * @return array
     */
    public function fetchAll(): array;
}
