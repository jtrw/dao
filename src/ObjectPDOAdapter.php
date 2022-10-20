<?php
namespace Jtrw\DAO;

use Jtrw\DAO\ValueObject\ArrayLiteral;
use Jtrw\DAO\ValueObject\StringLiteral;
use Jtrw\DAO\ValueObject\ValueObjectInterface;
use PDO;
use PDOStatement;
use PDOException;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Class ObjectPDOAdapter
 * @package Jtrw\DAO
 */
class ObjectPDOAdapter extends ObjectAdapter
{
    /**
     * @var PDO
     */
    protected PDO $db;
    
    
    public function __construct(PDO $db)
    {
        $this->setAttributes($db);
        
        parent::__construct($db);
    } // end __construct
    //
    /**
     * @param PDO $db
     */
    public function setAttributes(PDO $db): void
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
        $this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * @param string $obj
     * @param int $type
     * @return string
     */
    public function quote(string $obj, int $type = 0): string
    {
        return $this->db->quote($obj, $type);
    } // end quote
    
    /**
     * @param string $sql
     * @return PDOStatement
     * @throws DatabaseException
     */
    private function _execute(string $sql): PDOStatement
    {
        try {
            $query = $this->db->prepare($sql);
        } catch (PDOException $exp) {
            throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
        }
        
        if ($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
        }
        
        try {
            $res = $query->execute();
            if (!$res) {
                $info = $query->errorInfo();
                throw new DatabaseException($info[2], (int) $info[1], $sql);
            }
        } catch (PDOException $exp) {
            throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
        }
        
        return $query;
    } // end _execute
    
    /**
     * @param string $sql
     * @return ValueObjectInterface
     * @throws DatabaseException
     */
    public function getRow(string $sql): ValueObjectInterface
    {
        $query = $this->_execute($sql);
        
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = [];
        }
        
        return new ArrayLiteral($result);
    } // end getRow
    
    /**
     * @param string $sql
     * @return ValueObjectInterface
     * @throws DatabaseException
     */
    public function getAll(string $sql): ValueObjectInterface
    {
        $query = $this->_execute($sql);
        
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = [];
        }
        
        return new ArrayLiteral($result);
    } // end getAll
    
    /**
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function getCol(string $sql): ValueObjectInterface
    {
        $query = $this->_execute($sql);
        
        $result = [];
        
        while (($cell = $query->fetchColumn()) !== false) {
            $result[] = $cell;
        }
        
        return new ArrayLiteral($result);
    } // end getCol
    
    /**
     * @param string $sql
     *
     * @return ValueObjectInterface
     * @throws DatabaseException
     */
    public function getOne(string $sql): ValueObjectInterface
    {
        $query = $this->_execute($sql);
        $result = $query->fetchColumn() ?? '';
        
        return new StringLiteral($result);
    } // end getOne
    
    /**
     * @param string $sql
     * @return ValueObjectInterface
     * @throws DatabaseException
     */
    public function getAssoc(string $sql): ValueObjectInterface
    {
        $query = $this->_execute($sql);
        
        $result = [];
        
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $val = array_shift($row);
            if (count($row) === 1) {
                $row = array_shift($row);
            }
            $result[$val] = $row;
        }
        
        return new ArrayLiteral($result);
    } // end getAssoc
    
    /**
     * @param bool $isolationLevel
     */
    public function begin(bool $isolationLevel = false): void
    {
        $this->db->beginTransaction();
        
        self::$_isStartTransaction = true;
    } // end begin
    
    /**
     *
     */
    public function commit(): void
    {
        $this->db->commit();
        
        self::$_isStartTransaction = false;
    } // end commit
    
    /**
     *
     */
    public function rollback(): void
    {
        $this->db->rollBack();
        
        self::$_isStartTransaction = false;
    } // end rollback
    
    /**
     * @param string $sql
     * @return int
     * @throws DatabaseException
     */
    public function query(string $sql): int
    {
        try {
            $affectedRows = $this->db->exec($sql);
        } catch (PDOException $exp) {
            $code = (int) $exp->getCode();
            $code = $this->driver->getErrorCode($code);
            throw new DatabaseException($exp->getMessage(), $code, $sql, $exp);
        }
        
        return $affectedRows;
    } // end query
    
    /**
     * @return int
     */
    public function getInsertID(): int
    {
        return $this->db->lastInsertId();
    } // end getInsertID
    
    /**
     * @return string
     */
    public function getDatabaseType(): string
    {
        $type = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($type === "sqlsrv" || $type === "dblib") {
            return static::TYPE_MSSQL;
        }
        
        return $type;
    } // end getDatabaseType
}
