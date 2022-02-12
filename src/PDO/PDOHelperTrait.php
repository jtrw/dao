<?php
namespace Jtrw\DAO\PDO;

use Jtrw\DAO\ObjectPDOAdapter;
use PDO;
use PDOException;
use PDOStatement;
use Jtrw\DAO\Exceptions\DatabaseException;

/**
 * Trait PDOHelperTrait
 * @package Jtrw\DAO\PDO
 */
trait PDOHelperTrait
{
    /**
     * @var PDO
     */
    protected PDO $db;
    
    /**
     * @param PDO $db
     */
    public function setAttributes(PDO $db)
    {
        $this->db = $db;
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
        $this->db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * @param string $obj
     * @param int|null $type
     * @return string
     */
    public function quote(string $obj, int $type = null): string
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
        $query = null;
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
     * @return array
     * @throws DatabaseException
     */
    public function getRow(string $sql): array
    {
        $query = $this->_execute($sql);
        
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = [];
        }
        
        return $result;
    } // end getRow
    
    /**
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function getAll(string $sql): array
    {
        $query = $this->_execute($sql);
        
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = [];
        }
        
        return $result;
    } // end getAll
    
    /**
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function getCol(string $sql): array
    {
        $query = $this->_execute($sql);
        
        $result = [];
        
        while (($cell = $query->fetchColumn()) !== false) {
            $result[] = $cell;
        }
        
        return $result;
    } // end getCol
    
    /**
     * @param string $sql
     * @return string|null
     * @throws DatabaseException
     */
    public function getOne(string $sql): ?string
    {
        $query = $this->_execute($sql);
        
        return $query->fetchColumn() ?? null;
    } // end getOne
    
    /**
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function getAssoc(string $sql): array
    {
        $query = $this->_execute($sql);
        
        $result = [];
        
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $val = array_shift($row);
            if (count($row) == 1) {
                $row = array_shift($row);
            }
            $result[$val] = $row;
        }
        
        return $result;
    } // end getAssoc
    
    /**
     * @param bool $isolationLevel
     */
    public function begin(bool $isolationLevel = false)
    {
        $this->db->beginTransaction();
        
        self::$_isStartTransaction = true;
    } // end begin
    
    /**
     *
     */
    public function commit()
    {
        $this->db->commit();
        
        self::$_isStartTransaction = false;
    } // end commit
    
    /**
     *
     */
    public function rollback()
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
        $affectedRows = 0;
        
        try {
            $affectedRows = $this->db->exec($sql);
        } catch (PDOException $exp) {
            $code = (int) $exp->getCode();
            $code = $this->driver->getErrorCode($code);
            throw new DatabaseException($exp->getMessage(), $code, $sql, $exp);
        }
        
        // TODO: Remove deprecated logic
        if ($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
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
            return ObjectPDOAdapter::TYPE_MSSQL;
        }
        
        return $type;
    } // end getDatabaseType
}