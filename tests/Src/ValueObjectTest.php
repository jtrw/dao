<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\ValueObject\ArrayLiteral;
use Jtrw\DAO\ValueObject\StringLiteral;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ValueObjectTest extends TestCase
{
    public function testStringLiteral()
    {
        $msg = "Test Message";
        $stringLiteral = new StringLiteral($msg);
        
        Assert::assertEquals($stringLiteral, $msg);
        Assert::assertEquals($stringLiteral->toNative(), $msg);
    }
    
    public function testArrayLiteral()
    {
        $arr = [
            "Test",
            "test2"
        ];
        $stringLiteral = new ArrayLiteral($arr);
        
        Assert::assertEquals($stringLiteral, json_encode($arr, JSON_THROW_ON_ERROR));
        Assert::assertEquals($stringLiteral->toNative(), $arr);
    }
}