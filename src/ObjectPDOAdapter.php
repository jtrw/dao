<?php
namespace Jtrw\DAO;

use Jtrw\DAO\PDO\PDOHelperTrait;
use Jtrw\DAO\PDO\PDOInterface;
use PDO;
use Jtrw\DAO\Driver\ObjectDriverInterface;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Class ObjectPDOAdapter
 * @package Jtrw\DAO
 */
class ObjectPDOAdapter implements DataAccessObjectInterface
{
    use PDOHelperTrait;

    /**
     *
     */
    public const TYPE_MYSQL = 'mysql';
    /**
     *
     */
    public const TYPE_MSSQL = 'mssql';
    /**
     *
     */
    public const TYPE_PGSQL = 'pgsql';

    /**
     *
     */
    public const DEFAULT_PORT_MYSQL = '3306';
    /**
     *
     */
    public const DEFAULT_PORT_PGSQL = '5432';
    /**
     *
     */
    public const DEFAULT_PORT_MSSQL = '1433';

    /**
     *
     */
    public const FETCH_ALL   = 100;
    /**
     *
     */
    public const FETCH_ROW   = 101;
    /**
     *
     */
    public const FETCH_ASSOC = 102;
    /**
     *
     */
    public const FETCH_COL = 103;
    /**
     *
     */
    public const FETCH_ONE = 104;

    /**
     *
     */
    private const SQL_WHERE = ' WHERE %s';
    /**
     *
     */
    private const SQL_AND = ' AND ';
    /**
     *
     */
    private const SQL_OR = ' OR ';

    /**
     *
     */
    private const MSG_UNDEFINED_TYPE = "Undefined database type";

    /**
     * @var ObjectDriverInterface|mixed
     */
    protected ObjectDriverInterface $driver;

    /**
     * @var bool
     */
    protected static bool $_isStartTransaction = false;

    /**
     * @var string
     */
    protected string $_dbTableNameDelimiterInColumnName = ".";

    /**
     * @var array|string[]
     */
    protected array $reservedWords = [
        'NOW()',
        'NOT NULL',
        'NULL',
        'CURRENT_DATE()',
        'CURRENT_TIME()',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'NOW'
    ];

    /**
     * ObjectPDOAdapter constructor.
     * @param PDO $db
     * @throws DatabaseException
     */
    public function __construct(PDO $db)
    {
        $this->setAttributes($db);

        $this->driver = $this->_createDriverInstance($db);
    } // end __construct

    /**
     * @param object $db
     * @return mixed
     * @throws DatabaseException
     */
    private function _createDriverInstance(object $db)
    {
        $type = $this->getDatabaseType();

        $className = ucfirst($type).'ObjectDriver';
        $className = "\Jtrw\DAO\Driver\\".$className;
        if (!class_exists($className)) {
            throw new DatabaseException("Driver Not Found");
        }

        return new $className($db);
    } // end _createDriverInstance

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return self::$_isStartTransaction;
    } // end inTransaction

    /**
     * @param string $table
     * @param array $values
     * @param bool $is_update_dublicate
     * @return int
     * @throws DatabaseException
     */
    public function insert(string $table, array $values, bool $is_update_dublicate = false): int
    {
        $sql = $this->getInsertSQL($table, $values, $is_update_dublicate);

        $this->query($sql);

        return $this->getInsertID();
    } // end insert

    /**
     * @param string $table
     * @param array $condition
     * @return int
     * @throws DatabaseException
     */
    public function delete(string $table, array $condition)
    {
        $where = $this->getSqlCondition($condition);

        $sql = "DELETE FROM ".$table;

        if ($where) {
            $sql .= sprintf(static::SQL_WHERE, implode(static::SQL_AND, $where));
        }

        return $this->query($sql);
    } // end delete

    /**
     * @param string $table
     * @param array $values
     * @param bool $inForeach
     * @return array|int
     * @throws DatabaseException
     */
    public function massInsert(string $table, array $values, bool $inForeach = false)
    {
        if ($inForeach) {
            $res = [];
            foreach ($values as $value) {
                $res[] = $this->insert($table, $value);
            }

        } else {
            $rows = [];
            foreach ($values as $items) {
                $data = $this->getInsertValues($items);
                $rows[] = '('.implode(', ', $data).')';
            }

            $columns = array_keys($values[0]);
            foreach ($columns as &$column) {
                $column = $this->quoteColumnName($column);
            }
            unset($column);

            $table = $this->quoteTableName($table);

            $sql = "INSERT INTO ".$table." (".implode(", ", $columns).") VALUES ".
                implode(', ', $rows);

            $res = $this->query($sql);
        }

        return $res;
    } // end massInsert

    /**
     * @param array $values
     * @return array
     */
    private function getInsertValues(array $values)
    {
        foreach ($values as &$item) {
            if (is_null($item)) {
                $item = 'NULL';
                continue;
            }

            if (!in_array($item, $this->reservedWords)) {
                $item = $this->quote($item);
            }
        }
        unset($item);

        return $values;
    } // end getInsertValues

    /**
     * @param string $table
     * @param array $values
     * @param array $condition
     * @return int
     * @throws DatabaseException
     */
    public function update(string $table, array $values, array $condition = [])
    {
        $sql = $this->getUpdateSQL($table, $values, $condition);

        return $this->query($sql);
    } // end update

    /**
     * @param string $table
     * @param array $values
     * @param bool $isUpdateDublicate
     * @return string
     * @throws DatabaseException
     */
    public function getInsertSQL(string $table, array $values, bool $isUpdateDublicate = false)
    {
        $values = $this->getInsertValues($values);
        $columns = array_keys($values);

        foreach ($columns as &$column) {
            $column = $this->quoteColumnName($column);
        }
        unset($column);

        $sql = "INSERT INTO ".
                    $this->quoteTableName($table)." ".
                    "(".implode(", ", $columns).") ".
                    "VALUES (".implode(", ", $values).")";

        if ($isUpdateDublicate) {
            $sql = $this->_getUpdateDuplicateSQL($sql, $values);
        }

        return $sql;
    } // end getInsertSQL

    /**
     * @param array $values
     * @param string|null $tableName
     * @return array
     */
    public function getUpdateValues(array $values, string $tableName = null): array
    {
        foreach ($values as $key => &$item) {

            if ($tableName) {
                $key = $tableName.$this->_dbTableNameDelimiterInColumnName.$key;
            }

            if (is_null($item)) {
                $item = $this->quoteColumnName($key)." = NULL";
                continue;
            }

            $lastSymbol = mb_substr($key, mb_strlen($key) - 1, 1);
            if (in_array($lastSymbol, ['+', '-'])) {
                $key = str_replace($lastSymbol, "", $key);
                $item = $key." = ".$key." ".$lastSymbol." ".$this->quote($item);
                continue;
            }

            $key = $this->quoteColumnName($key);

            if (!$this->reservedWords || !in_array($item, $this->reservedWords)) {
                $item = $key." = ".$this->quote($item);
            } else {
                $item = $key." = ".$item;
            }
        }
        unset($item);

        return $values;
    } // end getUpdateValues

    /**
     * @param string $table
     * @param array $values
     * @param array $condition
     * @return string
     */
    public function getUpdateSQL(string $table, array $values, array $condition = []): string
    {
        $values = $this->getUpdateValues($values);

        $sql = "UPDATE ".$table." SET ".implode(", ", $values);

        if (is_array($condition)) {
            $sqlCondition = $this->getSqlCondition($condition);
            if ($sqlCondition) {
                $sql .= sprintf(static::SQL_WHERE, implode(static::SQL_AND, $sqlCondition));
            }
        } else {
            $sql .= sprintf(static::SQL_WHERE, $condition);
        }

        return $sql;
    } // end getUpdateSQL

    /**
     * Condition statement builder.
     *
     * <code>
     * $search = array(
     *  'columnName' => 5,
     *  'columnName2&IN' => array(1, 2, 3, 4)
     *  'columnName3 = 5 AND columnName4 < 7'
     * );
     * </code>
     *
     * @param array|bool $obj
     * @return array|mixed
     */
    public function getSqlCondition(array $obj = null): array
    {
        $result = [];

        if ($obj === null) {
            return $result;
        }

        foreach ($obj as $key => $item) {
            // XXX: if numeric then we get sql condition statement
            if (is_numeric($key)) {
                $conditionResult = $item;
            } else {
                $conditionResult = $this->_getConditionResult($key, $item);
            }

            if ($conditionResult) {
                $result[] = $conditionResult;
            }
        }

        return $result;
    } // end getSqlCondition

    /**
     * @param string $key
     * @param $item
     * @return string
     */
    private function _getConditionResult(string $key, $item): string
    {
        $result = "";

        $buffer = explode("&", $key);
        $action = !empty($buffer[1]) ? $buffer[1] : "=";

        if ($this->_isNull($item, $buffer)) {
            $result = $buffer[0] . ' IS NULL';
            return $result;
        }

        if (in_array($action, ['IN', 'NOT IN'])) {
            $values = [];
            if (!$item) {
                return $result;
            }
            $item = is_scalar($item) ? explode(", ", $item) : $item;
            foreach ($item as $val) {
                $values[] = $this->quote($val);
            }

            if ($values) {
                $result = $buffer[0]." ".$action." (".implode(', ', $values).')';
            }
            return $result;
        }

        $resultKey = $this->_getConditionByKey($key, $item);

        if ($resultKey) {
            return $resultKey;
        }

        $resultCommand = $this->_getSqlConditionByActionCommand($action, $item, $buffer);
        if ($resultCommand) {
            return $resultCommand;
        }

        // FIXME:
        if ($this->getDatabaseType() === static::TYPE_PGSQL &&
            $this->_isNeedCastValueToStringByAction($action)) {
            $buffer[0] .= '::text';
        }

        if (!in_array($item, $this->reservedWords)) {
            if (!is_numeric($item)) {
                $item = $this->quote($item);
            }
        }

        $result = $buffer[0].' '.$action.' '.$item;

        return $result;
    } // end _getConditionResult

    /**
     * @param string $action
     * @return bool
     */
    private function _isNeedCastValueToStringByAction(string $action): bool
    {
        $actions = [
            'like',
            'not like',
            'ilike',
            'not ilike',
        ];

        return in_array(strtolower($action), $actions);
    } // end _isNeedCastValueToStringByAction

    /**
     * @param string $key
     * @param $item
     * @return string|null
     */
    private function _getConditionByKey(string $key, $item): ?string
    {
        if ($key === 'sql_or') {
            return $this->_getSqlConditionOR($item);
        }

        if ($key === 'sql_and') {
            return $this->_getSqlConditionAND($item);
        }

        return null;
    } // end _getConditionByKey

    /**
     * @param string $action
     * @param $item
     * @param array $buffer
     * @return string
     * @throws DatabaseException
     */
    private function _getSqlConditionByActionCommand(string $action, $item, array $buffer): string
    {
        $command = strtolower($action);

        switch ($command) {
            case 'or_sql':
                $sql = '('.implode(' OR ', $item).')';
                break;
            case 'or':
                $sql = $this->_getOrCondition($buffer, $item);
                break;
            case 'match':
                $sql = "MATCH (".$buffer[0].") AGAINST (".$this->quote($item).")";
                break;
            case 'between':
                $sql = $this->_getBetweenCondition($buffer, $item);
                break;
            case 'soundex':
                $sql = $this->_getSoundexCondition($buffer, $item);
                break;
            default:
                $sql = '';
        }

        return $sql;
    } // end _getSqlConditionByActionCommand

    /**
     * @param array $buffer
     * @param array $item
     * @return string
     */
    private function _getOrCondition(array $buffer, array $item): string
    {
        list($value, $others) = $item;

        $action = empty($buffer[2]) ? '&=' : '&'.$buffer[2];

        $condition = [$buffer[0].$action => $value];
        $ors = $this->getSqlCondition($condition);

        $others = $this->getSqlCondition($others);
        $conditions = array_merge($ors, $others);
        return '('.implode(static::SQL_OR, $conditions).')';
    } // end _getOrCondition

    /**
     * @param array $item
     * @return string
     */
    private function _getSqlConditionAND(array $item): string
    {
        $search = [];
        foreach ($item as $row) {
            if (is_scalar($row)) {
                $search[] = $row;
            } else {
                $search[] = implode(static::SQL_AND, $this->getSqlCondition($row));
            }
        }

        return implode(static::SQL_AND, $search);
    } // end _getSqlConditionAND

    /**
     * @param array $item
     * @return string
     */
    private function _getSqlConditionOR(array $item): string
    {
        $search = [];
        foreach ($item as $row) {
            if (is_scalar($row)) {
                $search[] = $row;
            } else {
                $search[] = implode(static::SQL_AND, $this->getSqlCondition($row));
            }
        }

        return '(('.implode(' )'.static::SQL_OR.'(', $search).'))';
    } // end _getSqlConditionOR

    /**
     * Returns a SOUNDEX condition. Use:
     *
     * <code>
     * $search = array(
     *      'city&SOUNDEX' => 'Chicago'
     * );
     *
     * $search = array(
     *      'city&SOUNDS LIKE' => 'Chicago'
     * );
     *
     * </code>
     *
     * @param array $buffer
     * @param string $item
     * @return string
     */
    private function _getSoundexCondition(array $buffer, string $item): string
    {
        return "SOUNDEX(".$buffer[0].") = SOUNDEX(".$this->quote($item).")";
    } // end _getSoundexCondition

    /**
     * Returns a BETWEEN condition. Use:
     *
     * <code>
     * $search = array(
     *      'cdate&BETWEEN' => array(
     *          'XXXX-XX-XX',
     *          'XXXX-XX-XX'
     *      )
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => array('XXXX-XX-XX') // cdate >= 'XXXX-XX-XX'
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => array(1 => 'XXXX-XX-XX') // cdate <= 'XXXX-XX-XX'
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => 'XXXX-XX-XX AND XXXX-XX-XX'
     * );
     * </code>
     *
     * @param array $buffer
     * @param mixed $item
     * @throws DatabaseException
     * @return string
     */
    private function _getBetweenCondition(array $buffer, $item): string
    {
        $columnName = $this->quoteColumnName($buffer[0]);

        if (is_array($item)) {
            if (count($item) == 1) {

                if (array_key_exists(0, $item)) {
                    $operation = ' >= ';
                    $value = $item[0];
                } else if (array_key_exists(1, $item)) {
                    $operation = ' <= ';
                    $value = $item[1];
                } else {
                    throw new DatabaseException(
                        "Syntax error into BETWEEN condition"
                    );
                }

                $condition = $columnName.$operation.$this->quote($value);
            } else {
                $condition = $columnName." BETWEEN ".$this->quote($item[0]).
                             static::SQL_AND.$this->quote($item[1]);
            }

        } else {
            $condition = $columnName." BETWEEN ".$item;
        }

        return $condition;
    } // end _getBetweenCondition

    /**
     * @param string $name
     * @return string
     */
    public function quoteTableName(string $name): string
    {
        return $this->driver->quoteTableName($name);
    } // end quoteTableName

    /**
     * @param string $name
     * @return string
     */
    public function quoteColumnName(string $name): string
    {
        return $this->driver->quoteColumnName($name);
    } // end quoteColumnName

    /**
     * Returns generate select sql query
     *
     * @param array $condition
     * @param string $sql
     * @param array $orderBy
     * @return string
     */
    public function getSelectSQL(array $condition, string $sql, array $orderBy = []): string
    {
        $where = $this->getSqlCondition($condition);

        if ($where) {
            $sql .=  sprintf(static::SQL_WHERE, implode(static::SQL_AND, $where));
        }

        if ($orderBy) {
            $sql .= " ORDER BY ".implode(', ', $orderBy);
        }

        return $sql;
    } // end getSelectSQL

    /**
     * Fetch rows returned from a query
     *
     * @param string|array $selectSql
     * @param array $condition
     * @param array|bool $orderBy
     * @param int $type
     * @return array
     * @throws DatabaseException
     */
    public function select(
        string $selectSql,
        array $condition = [],
        array $orderBy = [],
        int $type = self::FETCH_ALL
    ): array
    {
        $sql = $this->getSelectSQL($condition, $selectSql, $orderBy);

        $methods = [
            static::FETCH_ALL   => 'getAll',
            static::FETCH_ROW   => 'getRow',
            static::FETCH_ASSOC => 'getAssoc',
            static::FETCH_COL   => 'getCol',
            static::FETCH_ONE   => 'getOne',
        ];

        if (!isset($methods[$type])) {
            $msg = sprintf('Undefined select type %s', $type);
            throw new DatabaseException($msg, 3005);
        }

        if (!is_callable([$this, $methods[$type]])) {
            $msg = sprintf(
                'Method "%s" was not found in Object.',
                $methods[$type]
            );

            throw new DatabaseException($msg, 3006);
        }

        // XXX: Compatibility with PHP7+ hinting ?array
        // FIXME: We've to fix all get* methods in to adapters
        $data = call_user_func_array([$this, $methods[$type]], [$sql]);
        return $data ? $data : [];
    } // end select

    /**
     * Returns an array of filter fields
     *
     * @param $search
     * @return array
     */
    public function getConditionFields(array $search): array
    {
        $fields = [];

        foreach ($search as $key => $item) {
            $buffer = explode("&", $key);

            $info = explode('.', $buffer[0]);

            if (!isset($info[1])) {
                continue;
            }

            $fields[$info[0]][$info[1]] = $buffer[0];
        }

        return $fields;
    } // end getConditionFields

    /**
     * Finds whether a value should be null
     *
     * @param mixed $value
     * @param array $buffer
     * @return bool
     */
    private function _isNull($value, $buffer): bool
    {
        if (!empty($buffer[1]) &&
            strtolower($buffer[1]) === 'is' &&
            is_scalar($value) &&
            strtolower($value) === 'null'
        ) {
            return true;
        }

        if (is_null($value)) {
            return true;
        }

        return false;
    } // end _isNull

    /**
     * Returns tables list.
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function getTables(): array
    {
        return $this->getDriver()->getTables($this);
    } // end getTables


    /**
     * @param string $tableName
     * @return array
     */
    public function getTableIndexes(string $tableName): array
    {
        return $this->getDriver()->getTableIndexes($this, $tableName);
    } // end getTableIndexes

    /**
     * @param bool $isEnable
     * @return bool|void
     */
    public function setForeignKeyChecks(bool $isEnable = true)
    {
        $this->getDriver()->setForeignKeyChecks($this, $isEnable);
    } // end setForeignKeyChecks

    /**
     * Remove a table.
     * @param $table
     * @return mixed
     */
    public function deleteTable(string $table)
    {
        $type = $this->getDatabaseType();

        $sql = "DROP TABLE ".$this->quoteTableName($table);

        if ($type == static::TYPE_PGSQL) {
            $sql = $sql.' CASCADE';
        }

        return $this->query($sql);
    } // end deleteTable

    /**
     * @param string $sql
     * @param array $values
     * @return string
     * @throws DatabaseException
     */
    private function _getUpdateDuplicateSQL(string $sql, array $values): string
    {
        $type = $this->getDatabaseType();

        switch ($type) {
            case static::TYPE_MYSQL:
                $sql .= " ON duplicate KEY UPDATE ";
                break;

            case static::TYPE_PGSQL:
                $msg = sprintf(
                    "Method Insert Not Support Third Param For %s DB Type.",
                    static::TYPE_PGSQL
                );
                throw new DatabaseException($msg);

            default:
                throw new DatabaseException(static::MSG_UNDEFINED_TYPE);
        }

        $rows = [];

        foreach ($values as $field => $value) {
            $rows[] = $field." = ".$value;
        }

        $sql .= implode(", ", $rows);

        return $sql;
    } // end _getUpdateDuplicateSQL

    /**
     * @return ObjectDriverInterface
     */
    public function getDriver(): ObjectDriverInterface
    {
        return $this->driver;
    } // end getDriver
}
