<?php

namespace Jtrw\DAO\Tests\Src;

use Jtrw\DAO\DataAccessObject;
use Jtrw\DAO\Exceptions\DatabaseException;
use Jtrw\DAO\Tests\DbConnector;
use PHPUnit\Framework\TestCase;

/**
 * Security tests for SQL injection and other vulnerability fixes
 *
 * Tests for:
 * - CVE-003: SQL injection through numeric keys
 * - CVE-005: BETWEEN vulnerability
 * - CVE-006: Pagination parameter validation
 */
class SecurityTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $pdo = DbConnector::getSourcePdo();
        $this->db = DataAccessObject::factory($pdo);
    }

    /**
     * CVE-003: Test that dangerous SQL keywords are blocked in numeric key conditions
     */
    public function testSqlInjectionBlockedInNumericKeys()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/Security violation.*dangerous sql keyword/i');

        // Attempt SQL injection via numeric key
        $maliciousCondition = [
            0 => "id = 1 OR 1=1; DROP TABLE users--"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that DROP keyword is blocked
     */
    public function testDropKeywordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*DROP/i');

        $maliciousCondition = [
            0 => "status = 'active' AND 1=1; DROP TABLE users"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that DELETE keyword is blocked
     */
    public function testDeleteKeywordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*DELETE/i');

        $maliciousCondition = [
            0 => "id = 1; DELETE FROM users WHERE 1=1"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that UNION keyword is blocked
     */
    public function testUnionKeywordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*UNION/i');

        $maliciousCondition = [
            0 => "id = 1 UNION SELECT password FROM users"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that EXEC keyword is blocked
     */
    public function testExecKeywordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*EXEC/i');

        $maliciousCondition = [
            0 => "id = 1; EXEC sp_executesql"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that semicolons (multiple statements) are blocked
     */
    public function testSemicolonBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/multiple sql statements/i');

        $maliciousCondition = [
            0 => "id = 1; SELECT 1"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that legitimate conditions still work with numeric keys
     */
    public function testLegitimateNumericKeyConditions()
    {
        $condition = [
            0 => "status = 'active'",
            1 => "created_at > '2023-01-01'"
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains("status = 'active'", $result);
        $this->assertContains("created_at > '2023-01-01'", $result);
    }

    /**
     * CVE-003: Test that column names containing dangerous keywords are allowed
     * e.g., "created_at" contains "CREATE", "updated_at" contains "UPDATE"
     */
    public function testColumnNamesWithKeywordSubstrings()
    {
        // These should all pass - keywords are part of column names, not separate SQL keywords
        $conditions = [
            0 => "created_at > '2023-01-01'",       // contains "create"
            1 => "updated_at < '2024-01-01'",       // contains "update"
            2 => "deleted_flag = 0",                 // contains "delete"
            3 => "execution_time < 1000",            // contains "exec"
            4 => "grant_type = 'authorization'"     // contains "grant"
        ];

        $result = $this->db->getSqlCondition($conditions);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    /**
     * CVE-003: Test that actual CREATE keyword as whole word is blocked
     */
    public function testCreateKeywordAsWholeWordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*CREATE/i');

        $maliciousCondition = [
            0 => "id = 1 OR CREATE TABLE evil"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test that actual UPDATE keyword as whole word is blocked
     */
    public function testUpdateKeywordAsWholeWordBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql keyword.*UPDATE/i');

        $maliciousCondition = [
            0 => "id = 1; UPDATE users SET role = 'admin'"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test SQL comment patterns are blocked
     */
    public function testSqlCommentBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql pattern/i');

        $maliciousCondition = [
            0 => "id = 1 -- comment out the rest"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test multiline comment patterns are blocked
     */
    public function testMultilineCommentBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql pattern/i');

        $maliciousCondition = [
            0 => "id = 1 /* comment */"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-003: Test hex literals are blocked (often used in injection)
     */
    public function testHexLiteralsBlocked()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/dangerous sql pattern/i');

        $maliciousCondition = [
            0 => "id = 0x41646D696E"  // Hex for "Admin"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-005: Test BETWEEN with malicious string injection
     */
    public function testBetweenMaliciousString()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/security violation.*dangerous characters/i');

        // Attempt to inject SQL via BETWEEN
        $maliciousCondition = [
            'date&BETWEEN' => "2023-01-01' OR '1'='1' AND '2023-12-31"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-005: Test BETWEEN with invalid format
     */
    public function testBetweenInvalidFormat()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/invalid between condition format/i');

        $maliciousCondition = [
            'date&BETWEEN' => "2023-01-01"  // Missing second value
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-005: Test BETWEEN with semicolon
     */
    public function testBetweenWithSemicolon()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/security violation.*dangerous characters/i');

        $maliciousCondition = [
            'date&BETWEEN' => "2023-01-01; DROP TABLE users-- AND 2023-12-31"
        ];

        $this->db->getSqlCondition($maliciousCondition);
    }

    /**
     * CVE-005: Test that legitimate BETWEEN conditions work with array
     */
    public function testLegitimateBetweenArray()
    {
        $condition = [
            'date&BETWEEN' => ['2023-01-01', '2023-12-31']
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        // Should contain properly quoted values
        $this->assertMatchesRegularExpression('/BETWEEN.*2023-01-01.*AND.*2023-12-31/i', $result[0]);
    }

    /**
     * CVE-005: Test that legitimate BETWEEN conditions work with string
     */
    public function testLegitimateBetweenString()
    {
        $condition = [
            'date&BETWEEN' => "2023-01-01 AND 2023-12-31"
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        // Should contain properly quoted values
        $this->assertMatchesRegularExpression('/BETWEEN.*2023-01-01.*AND.*2023-12-31/i', $result[0]);
    }

    /**
     * CVE-005: Test BETWEEN with single array value (>= operator)
     */
    public function testBetweenSingleValueGreaterOrEqual()
    {
        $condition = [
            'date&BETWEEN' => ['2023-01-01']
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertMatchesRegularExpression('/>=.*2023-01-01/i', $result[0]);
    }

    /**
     * CVE-006: Test pagination with negative items per page
     */
    public function testPaginationNegativeItemsPerPage()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/invalid pagination.*must be positive/i');

        $driver = $this->db->getDriver();
        $driver->getSplitOnPages($this->db, "SELECT * FROM users", -10, 1);
    }

    /**
     * CVE-006: Test pagination with zero items per page
     */
    public function testPaginationZeroItemsPerPage()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/invalid pagination.*must be positive/i');

        $driver = $this->db->getDriver();
        $driver->getSplitOnPages($this->db, "SELECT * FROM users", 0, 1);
    }

    /**
     * CVE-006: Test pagination with excessive items per page
     */
    public function testPaginationExcessiveItemsPerPage()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/exceeds maximum allowed/i');

        $driver = $this->db->getDriver();
        $driver->getSplitOnPages($this->db, "SELECT * FROM users", 10000, 1);
    }

    /**
     * CVE-006: Test pagination with negative page number
     */
    public function testPaginationNegativePage()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/invalid pagination.*non-negative/i');

        $driver = $this->db->getDriver();
        $driver->getSplitOnPages($this->db, "SELECT * FROM users", 10, -1);
    }

    /**
     * CVE-006: Test pagination with very large page number (overflow)
     */
    public function testPaginationOverflowPage()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/integer overflow/i');

        $driver = $this->db->getDriver();
        // Use very large page number that would cause overflow
        $driver->getSplitOnPages($this->db, "SELECT * FROM users", 1000, PHP_INT_MAX);
    }

    /**
     * CVE-006: Test that legitimate pagination parameters work
     */
    public function testLegitimatePagination()
    {
        // This test requires actual database connection and data
        // Skip if MySQL driver is not available
        $driver = $this->db->getDriver();

        if (!method_exists($driver, 'getSplitOnPages')) {
            $this->markTestSkipped('getSplitOnPages not available for this driver');
        }

        try {
            // Create a temporary test table
            $this->db->query("CREATE TEMPORARY TABLE IF NOT EXISTS test_pagination (id INT, name VARCHAR(50))");
            $this->db->query("INSERT INTO test_pagination (id, name) VALUES (1, 'test1'), (2, 'test2'), (3, 'test3')");

            $result = $driver->getSplitOnPages($this->db, "SELECT * FROM test_pagination", 2, 1);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('rows', $result);
            $this->assertArrayHasKey('cnt', $result);
            $this->assertArrayHasKey('pageCnt', $result);

            // Clean up
            $this->db->query("DROP TEMPORARY TABLE IF EXISTS test_pagination");
        } catch (DatabaseException $e) {
            // If table operations fail, mark as skipped
            $this->markTestSkipped('Database operations not available: ' . $e->getMessage());
        }
    }

    /**
     * Test that associative array conditions (the recommended approach) work correctly
     */
    public function testAssociativeArrayConditions()
    {
        $condition = [
            'id' => 1,
            'status' => 'active',
            'created_at&>' => '2023-01-01'
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    /**
     * Test IN operator with array
     */
    public function testInOperatorWithArray()
    {
        $condition = [
            'status&IN' => ['active', 'pending', 'completed']
        ];

        $result = $this->db->getSqlCondition($condition);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertMatchesRegularExpression('/status IN \(.*active.*pending.*completed.*\)/i', $result[0]);
    }
}
