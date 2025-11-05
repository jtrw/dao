#!/usr/bin/env php
<?php
/**
 * Quick validation test script for security fixes
 * Tests SQL validation logic without requiring database connection
 */

require __DIR__ . '/../vendor/autoload.php';

use Jtrw\DAO\Exceptions\DatabaseException;

echo "🧪 Testing SQL Validation Logic...\n\n";

// Mock driver for testing
class TestDriver extends \Jtrw\DAO\Driver\AbstractObjectDriver {
    public function quoteTableName(string $name): string {
        return '`' . $name . '`';
    }

    public function quoteColumnName(string $key): string {
        return '`' . $key . '`';
    }

    public function getTables(\Jtrw\DAO\DataAccessObjectInterface $object): array {
        return [];
    }

    public function getTableIndexes(\Jtrw\DAO\DataAccessObjectInterface $object, string $tableName): array {
        return [];
    }

    public function setForeignKeyChecks(\Jtrw\DAO\DataAccessObjectInterface $object, bool $isEnable = true) {
        // Empty implementation
    }
}

// Create a test class that exposes the validation method for testing
class TestObjectAdapter extends \Jtrw\DAO\ObjectAdapter {
    public function __construct() {
        // Skip parent constructor and initialize driver manually
        $this->driver = new TestDriver();
    }

    public function quote(string $obj, int $type = 0): string {
        return "'" . addslashes($obj) . "'";
    }

    public function getDatabaseType(): string {
        return 'mysql';
    }

    // Make protected method public for testing
    public function testGetSqlCondition(?array $obj = null): array {
        return $this->getSqlCondition($obj);
    }

    // Dummy implementations for abstract methods
    public function getRow(string $sql): \Jtrw\DAO\ValueObject\ValueObjectInterface {
        throw new \Exception("Not implemented in test");
    }

    public function getAll(string $sql): \Jtrw\DAO\ValueObject\ValueObjectInterface {
        throw new \Exception("Not implemented in test");
    }

    public function getCol(string $sql): \Jtrw\DAO\ValueObject\ValueObjectInterface {
        throw new \Exception("Not implemented in test");
    }

    public function getOne(string $sql): \Jtrw\DAO\ValueObject\ValueObjectInterface {
        throw new \Exception("Not implemented in test");
    }

    public function getAssoc(string $sql): \Jtrw\DAO\ValueObject\ValueObjectInterface {
        throw new \Exception("Not implemented in test");
    }

    public function begin(bool $isolationLevel = false): void {
        throw new \Exception("Not implemented in test");
    }

    public function commit(): void {
        throw new \Exception("Not implemented in test");
    }

    public function rollback(): void {
        throw new \Exception("Not implemented in test");
    }

    public function query(string $sql): int {
        throw new \Exception("Not implemented in test");
    }

    public function getInsertID(): int {
        throw new \Exception("Not implemented in test");
    }
}

$db = new TestObjectAdapter();

$testsPassed = 0;
$testsFailed = 0;

/**
 * Helper function to run a test
 */
function runTest($name, $callback) {
    global $testsPassed, $testsFailed;

    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS: $name\n";
            $testsPassed++;
        } else {
            echo "❌ FAIL: $name\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "❌ FAIL: $name - " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

echo "Testing CVE-003: SQL Injection through Numeric Keys\n";
echo str_repeat("-", 60) . "\n";

// Test 1: Column names with keyword substrings should PASS
runTest("Column names with keyword substrings (created_at, updated_at)", function() use ($db) {
    $condition = [
        0 => "created_at > '2023-01-01'",
        1 => "updated_at < '2024-01-01'",
        2 => "deleted_flag = 0"
    ];

    $result = $db->testGetSqlCondition($condition);
    return is_array($result) && count($result) === 3;
});

// Test 2: DROP keyword should be BLOCKED
runTest("DROP keyword should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1; DROP TABLE users"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false; // Should have thrown exception
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'DROP') !== false;
    }
});

// Test 3: CREATE keyword should be BLOCKED
runTest("CREATE keyword should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1 OR CREATE TABLE evil"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'CREATE') !== false;
    }
});

// Test 4: DELETE keyword should be BLOCKED
runTest("DELETE keyword should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1; DELETE FROM users"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'DELETE') !== false;
    }
});

// Test 5: UPDATE keyword should be BLOCKED
runTest("UPDATE keyword should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1; UPDATE users SET role = 'admin'"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'UPDATE') !== false;
    }
});

// Test 6: UNION keyword should be BLOCKED
runTest("UNION keyword should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1 UNION SELECT password FROM users"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'UNION') !== false;
    }
});

// Test 7: Semicolons should be BLOCKED
runTest("Semicolons (multiple statements) should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1; SELECT 1"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'Multiple SQL statements') !== false;
    }
});

// Test 8: SQL comments should be BLOCKED
runTest("SQL comments (--) should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1 -- comment"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'pattern') !== false;
    }
});

// Test 9: Multiline comments should be BLOCKED
runTest("Multiline comments (/* */) should be blocked", function() use ($db) {
    $condition = [
        0 => "id = 1 /* comment */"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'pattern') !== false;
    }
});

// Test 10: Legitimate conditions should PASS
runTest("Legitimate conditions should pass", function() use ($db) {
    $condition = [
        0 => "status = 'active'",
        1 => "created_at > '2023-01-01'",
        2 => "price <= 100"
    ];

    $result = $db->testGetSqlCondition($condition);
    return is_array($result) && count($result) === 3;
});

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "Testing CVE-005: BETWEEN Vulnerability\n";
echo str_repeat("-", 60) . "\n";

// Test 11: BETWEEN with array should PASS
runTest("BETWEEN with array format should pass", function() use ($db) {
    $condition = [
        'date&BETWEEN' => ['2023-01-01', '2023-12-31']
    ];

    $result = $db->testGetSqlCondition($condition);
    return is_array($result) && count($result) === 1;
});

// Test 12: BETWEEN with valid string should PASS
runTest("BETWEEN with valid string format should pass", function() use ($db) {
    $condition = [
        'date&BETWEEN' => '2023-01-01 AND 2023-12-31'
    ];

    $result = $db->testGetSqlCondition($condition);
    return is_array($result) && count($result) === 1;
});

// Test 13: BETWEEN with semicolon should be BLOCKED
runTest("BETWEEN with semicolon should be blocked", function() use ($db) {
    $condition = [
        'date&BETWEEN' => "2023-01-01; DROP TABLE users-- AND 2023-12-31"
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'dangerous characters') !== false;
    }
});

// Test 14: BETWEEN with invalid format should be BLOCKED
runTest("BETWEEN with invalid format should be blocked", function() use ($db) {
    $condition = [
        'date&BETWEEN' => "2023-01-01"  // Missing second value
    ];

    try {
        $db->testGetSqlCondition($condition);
        return false;
    } catch (DatabaseException $e) {
        return strpos($e->getMessage(), 'Invalid BETWEEN') !== false;
    }
});

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "📊 Test Results:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";
echo "📈 Total:  " . ($testsPassed + $testsFailed) . "\n";
echo str_repeat("=", 60) . "\n";

if ($testsFailed === 0) {
    echo "\n🎉 All tests passed! Security fixes are working correctly.\n";
    exit(0);
} else {
    echo "\n⚠️  Some tests failed. Please review the fixes.\n";
    exit(1);
}
