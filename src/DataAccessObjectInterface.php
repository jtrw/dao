<?php

namespace Jtrw\DAO;

use Jtrw\DAO\Driver\ObjectDriverInterface;
use Jtrw\DAO\ValueObject\ValueObjectInterface;

/**
 * Interface ObjectAdapterInterface
 * @package Jtrw\DAO
 */
interface DataAccessObjectInterface
{
    public const FETCH_ALL   = 100;
    public const FETCH_ROW   = 101;
    public const FETCH_ASSOC = 102;
    public const FETCH_COL   = 103;
    public const FETCH_ONE   = 104;

    /**
     * @param string $table
     * @param array $values
     * @param bool $isUpdateDuplicate
     * @return int
     */
    public function insert(string $table, array $values, bool $isUpdateDuplicate = false): int;

    /**
     * @param string $table
     * @param array $values
     * @param array $condition
     * @return mixed
     */
    public function update(string $table, array $values, array $condition);

    /**
     * @param string $table
     * @param array $condition
     * @return mixed
     */
    public function delete(string $table, array $condition);

    /**
     * @param string $table
     * @param array $values
     * @param bool $inForeach
     * @return mixed
     */
    public function massInsert(string $table, array $values, bool $inForeach = false);

    /**
     * Returns true if enabled transaction mode.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * @param string $name
     * @return mixed
     */
    public function quoteTableName(string $name);

    /**
     * @param string $name
     * @return mixed
     */
    public function quoteColumnName(string $name);


    /**
     * Returns tables list.
     *
     * @return mixed
     */
    public function getTables(): array;

    /**
     * Enable or disable foreign key checks.
     *
     * @param $isEnable = true
     * @return boolean
     */
    public function setForeignKeyChecks(bool $isEnable = true);

    /**
     * Remove a table.
     * @param $table
     * @return mixed
     */
    public function deleteTable(string $table);

    /**
     * Returns prepared conditions to filter data.
     *
     * @param array $obj
     * @return mixed
     */
    public function getSqlCondition(array $obj = []);

    /**
     * Returns table indexes list.
     *
     * @param $tableName
     * @return array
     */
    public function getTableIndexes(string $tableName): array;


    /**
     * Returns Driver instance.
     *
     * @return ObjectDriverInterface
     */
    public function getDriver(): ObjectDriverInterface;

    /**
     * @param string $selectSql
     * @param array $condition
     * @param array $orderBy
     * @param int $type
     * @return ValueObjectInterface
     */
    public function select(
        string $selectSql,
        array $condition = [],
        array $orderBy = [],
        int $type = self::FETCH_ALL
    ): ValueObjectInterface;

    //Copy
    /**
     * @param string $obj
     * @param int|null $type
     * @return string
     */
    public function quote(string $obj, int $type = null): string;
    
    /**
     * @param string $sql
     * @return ValueObjectInterface
     */
    public function getRow(string $sql): ValueObjectInterface;

    /**
     * @param string $sql
     * @return ValueObjectInterface
     */
    public function getAll(string $sql): ValueObjectInterface;

    /**
     * @param string $sql
     * @return ValueObjectInterface
     */
    public function getCol(string $sql): ValueObjectInterface;

    /**
     * @param string $sql
     * @return ValueObjectInterface|null
     */
    public function getOne(string $sql): ValueObjectInterface;

    /**
     * @param string $sql
     * @return ValueObjectInterface
     */
    public function getAssoc(string $sql): ValueObjectInterface;

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
