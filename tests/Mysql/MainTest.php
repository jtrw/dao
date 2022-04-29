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
}