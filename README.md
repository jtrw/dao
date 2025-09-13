# Data Access Object

[![Phpunit](https://github.com/jtrw/dao/workflows/Docker%20Image%20CI/badge.svg)](https://github.com/jtrw/dao/actions)
[![Codecov](https://codecov.io/gh/jtrw/dao/branch/master/graph/badge.svg?token=FYMTSQDQP5)](https://codecov.io/gh/jtrw/dao)
[![Latest Stable Version](http://poser.pugx.org/jtrw/dao/v)](https://packagist.org/packages/jtrw/dao)
[![Total Downloads](http://poser.pugx.org/jtrw/dao/downloads)](https://packagist.org/packages/jtrw/dao)
[![Latest Unstable Version](http://poser.pugx.org/jtrw/dao/v/unstable)](https://packagist.org/packages/jtrw/dao)
[![License](http://poser.pugx.org/jtrw/dao/license)](https://packagist.org/packages/jtrw/dao)
[![PHP Version Require](http://poser.pugx.org/jtrw/dao/require/php)](https://packagist.org/packages/jtrw/dao)

Data Access Object is tiny wrapper on php PDO. There was add more comfortable methods usage conditions in select query.

## Installation

Install via Composer:

```bash
composer require jtrw/dao
```

## Requirements

- PHP >= 7.4
- PDO extension
- JSON extension
- mbstring extension

## Quick Start

### Basic Setup

```php
<?php
$db = new PDO(
    $GLOBALS['config']['db']['dsn'],
    $GLOBALS['config']['db']['user'],
    $GLOBALS['config']['db']['pass']
);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
$db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING); 
$db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL); 


$res = $db->query('SET NAMES utf8mb4');

if (!$res) {
    throw new Exception('Database connection error');
}

$db = DataAccessObject::factory($db);
```

## API Documentation

### Core Methods

#### Insert
```php
// Insert single record
$id = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Insert with duplicate key update
$id = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
], true);
```

#### Update
```php
// Update records
$db->update('users',
    ['name' => 'Jane Doe'],
    ['id' => 1]
);
```

#### Delete
```php
// Delete records
$db->delete('users', ['id' => 1]);
```

#### Mass Insert
```php
// Insert multiple records
$data = [
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com']
];
$db->massInsert('users', $data);
```

#### Select with Conditions
```php
$search = [
       'columnName'     => 5,
       'columnName2&IN' => [1, 2, 3, 4]
       'columnName3&<'  => 7,
       'columnName4&>=' => 3
     ];

$sql = "SELECT * FROM users";
$result = $db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ALL);
$data = $result->toNative(); // Convert to native PHP array
```

### Transaction Support
```php
// Manual transaction handling
$db->begin();
try {
    $db->insert('users', ['name' => 'John']);
    $db->insert('orders', ['user_id' => $db->getInsertID(), 'total' => 100]);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Utility Methods
```php
// Get tables list
$tables = $db->getTables();

// Quote table/column names
$quotedTable = $db->quoteTableName('user_data');
$quotedColumn = $db->quoteColumnName('user-name');

// Get database type
$dbType = $db->getDatabaseType(); // mysql, pgsql, etc.

// Check transaction status
if ($db->inTransaction()) {
    // Inside transaction
}
```

## Search Conditions

|    key           |    value                                                   |    result                                  |
|------------------|------------------------------------------------------------|--------------------------------------------|
|-                 |`'column = 5'`                                              |`column = 5`                                |
|`col&<action>`    |`'item'`                                                    |`col <action> 'item'`                       |
|`column`          |`5`                                                         |`column = '5'`                              |
|`column`          |`null`                                                      |`column IS NULL`                            |
|`column&IN`       |`'val1, val2, val3'`                                        |`column IN ('val1', 'val2', 'val3')`        |
|`column&IN`       |`array('val1', 'val2', 'val3')`                             |`column IN ('val1', 'val2', 'val3')`        |
|`column&NOT IN`   |`'val1, val2, val3'`                                        |`column NOT IN ('val1', 'val2', 'val3')`    |
|`column&NOT IN`   |`array('val1', 'val2', 'val3')`                             |`column NOT IN ('val1', 'val2', 'val3')`    |
|`sql_or`          |`array('col1 = 5', 'col2 = 8')`                             |`((col1 = 5 ) OR (col2 = 8))`               |
|`sql_or`          |`array(array('col1' => 5), array('col2' => 8, 'col3' => 7))`|`((col1 = 5) OR (col2 = 8 AND col3 = 7))`   |
|`sql_and`         |`array('col1 = 5', 'col2 = 8')`                             |`col1 = 5 AND col2 = 8`                     |
|`sql_and`         |`array(array('col1' => 5), array('col2' => 8, 'col3' => 7))`|`col1 = 5 AND col2 = 8 AND col3 = 7`        |
|`something&or_sql`|`array('col1 = 5', 'col2 = 8')`                             |`(col1 = 5 OR col2 = 8)`                    |
|`col&or`          |`array('val1', array('col2' => 5, 'col3' => 8))`            |`(col = 'val1' OR col2 = '5' OR col3 = '8')`|
|`col&or&>=`       |`array(7, array('col2' => 5, 'col3' => 8))`                 |`(col >= 7 OR col2 = '5' OR col3 = '8')`    |
|`col&match`       |`'something'`                                               |`MATCH (col) AGAINST ('something')`         |
|`col&between`     |`array(3)`                                                  |`"col" >= '3'`                              |
|`col&between`     |`array(1 => 7)`                                             |`"col" <= '7'`                              |
|`col&between`     |`array(3, 7)`                                               |`"col" BETWEEN '3' AND '7'`                 |
|`col&between`     |`'3 AND 7'`                                                 |`"col" BETWEEN 3 AND 7`                     |
|`col&soundex`     |`'val1'`                                                    |`SOUNDEX(col) = SOUNDEX('val1')`            |

## Env

`make install`

`make start`

`make stop`

## Unittest

`php ./vendor/phpunit/phpunit/phpunit -c ./tests/phpunit.xml --testdox --stderr --colors`

`make tests` - run all tests with migrations

`make run-tests` - run all tests without migrations

## Troubleshooting

### Common Issues

#### Connection Problems
```php
// Ensure proper PDO configuration
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);
```

#### Transaction Deadlocks
- Use shorter transactions
- Always handle exceptions in transactions
- Consider using `SELECT ... FOR UPDATE` for critical sections

#### Performance Tips
- Use prepared statements for repeated queries
- Consider using `massInsert()` for bulk operations
- Use appropriate indexes on search columns

### Supported Databases

- **MySQL/MariaDB**: Full support with MySQL-specific features
- **PostgreSQL**: Full support with PostgreSQL-specific features
- **SQL Server**: Supported via MSSQL driver

### Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass: `make tests`
5. Submit a pull request

### License

MIT License - see LICENSE file for details.
