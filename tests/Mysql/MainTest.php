<?php

namespace Jtrw\DAO\Tests\Mysql;

use Jtrw\DAO\DataAccessObjectInterface;
use Jtrw\DAO\Tests\DbConnector;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    public function testOne()
    {
        $db = DbConnector::getInstance();
        $date = $db->select("SELECT CURRENT_DATE", [], [], DataAccessObjectInterface::FETCH_ONE)->toNative();

        Assert::assertEquals($date,date("Y-m-d"));
    }
    
    public function testInsert()
    {
        $db = DbConnector::getInstance();
        $values = [
            'id_parent' => 0,
            'caption'   => 'test',
            'value'     => 'dataTest'
        ];
        $idSetting = $db->insert("settings", $values);
        Assert::assertIsInt($idSetting);
        
       $result = $db->select("SELECT * FROM settings", ['id' => $idSetting], [], DataAccessObjectInterface::FETCH_ROW)->toNative();
       
       Assert::assertEquals($result['value'], $values['value']);
    }
}