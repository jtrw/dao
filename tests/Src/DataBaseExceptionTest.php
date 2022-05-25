<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\Exceptions\DatabaseException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class DataBaseExceptionTest extends TestCase
{
    public function testThrowError()
    {
        try {
            DatabaseException::throwError(DatabaseException::ERROR_UNSUPPORTABLE_METHOD);
            Assert::fail('DatabaseException was not thrown');
        } catch (DatabaseException $exp) {
            Assert::assertEquals("Unsupportable method", $exp->getMessage());
            Assert::assertEquals(DatabaseException::ERROR_UNSUPPORTABLE_METHOD, $exp->getCode());
        }
        
    }
}