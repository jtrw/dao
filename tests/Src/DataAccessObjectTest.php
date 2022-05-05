<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\DataAccessObject;
use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Exceptions\DatabaseException;
use Jtrw\DAO\Tests\DbConnector;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use stdClass;

class DataAccessObjectTest extends TestCase
{
    public function testFactory()
    {
        $pdo = DbConnector::getSourcePdo();
        
        $instance = DataAccessObject::factory($pdo);
        
        Assert::assertInstanceOf(DataAccessObjectInterface::class, $instance);
    }
    
    public function testExceptionAdapterFactory()
    {
        try {
            DataAccessObject::factory(new stdClass());
            $this->fail('DatabaseException was not thrown');
        } catch (DatabaseException $exp) {
            $this->assertEquals('Adapter Not Found',$exp->getMessage());
        }
    }
    
    public function testExceptionCreateConnection()
    {
        $testAdapterName = "TestAdapter";
        try {
            DataAccessObject::create("TestAdapter", DbConnector::getSourcePdo());
            $this->fail('DatabaseException was not thrown');
        } catch (DatabaseException $exp) {
            $adapterClass = 'Jtrw\DAO\\'.$testAdapterName;
            $msg = sprintf("Not found an object adapter class: %s", $adapterClass);
            
            $this->assertEquals($msg, $exp->getMessage());
        }
    }
}