<?php

namespace Jtrw\DAO\PDO;

/**
 * Interface PDOInterface
 * @package Jtrw\DAO\PDO
 */
interface PDOInterface
{
    /**
     * @param string $obj
     * @param int|null $type
     * @return string
     */
    public function quote(string $obj, int $type = null): string;
    
    /**
     * @param string $sql
     * @return array
     */
    public function getRow(string $sql): array;
    
    /**
     * @param string $sql
     * @return array
     */
    public function getAll(string $sql): array;
    
    /**
     * @param string $sql
     * @return array
     */
    public function getCol(string $sql): array;
    
    /**
     * @param string $sql
     * @return string|null
     */
    public function getOne(string $sql): ?string;
    
    /**
     * @param string $sql
     * @return array
     */
    public function getAssoc(string $sql): array;
    
    /**
     * @param bool $isolationLevel
     * @return mixed
     */
    public function begin(bool $isolationLevel = false);
    
    /**
     * @return mixed
     */
    public function commit();
    
    /**
     * @return mixed
     */
    public function rollback();
    
    /**
     * @param string $sql
     * @return int
     */
    public function query(string $sql): int;
    
    /**
     * @return int
     */
    public function getInsertID(): int;
    
    /**
     * @return string
     */
    public function getDatabaseType(): string;
}