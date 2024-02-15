# Data Access Object

[![Phpunit](https://github.com/jtrw/dao/workflows/Docker%20Image%20CI/badge.svg)](https://github.com/jtrw/dao/actions)
[![Codecov](https://codecov.io/gh/jtrw/dao/branch/master/graph/badge.svg?token=FYMTSQDQP5)](https://codecov.io/gh/jtrw/dao)
[![Latest Stable Version](http://poser.pugx.org/jtrw/dao/v)](https://packagist.org/packages/jtrw/dao)
[![Total Downloads](http://poser.pugx.org/jtrw/dao/downloads)](https://packagist.org/packages/jtrw/dao)
[![Latest Unstable Version](http://poser.pugx.org/jtrw/dao/v/unstable)](https://packagist.org/packages/jtrw/dao)
[![License](http://poser.pugx.org/jtrw/dao/license)](https://packagist.org/packages/jtrw/dao)
[![PHP Version Require](http://poser.pugx.org/jtrw/dao/require/php)](https://packagist.org/packages/jtrw/dao)

Data Access Object is tiny wrapper on php PDO. There was add more comfortable methods usage conditions in select query.


PDO Usage
===================

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
    throw new Exception('Database connection error);
}

$db = DataAccessObject::factory($db);
```

## Search
```sql
$search = [
       'columnName'     => 5,
       'columnName2&IN' => [1, 2, 3, 4]
       'columnName3&<'  => 7,
       'columnName4&>=' => 3
     ];

$sql = "SELECT * FROM users";
$db->select($sql, $search, [], DataAccessObjectInterface::FETCH_ALL)->toNative();
```



### Conditions

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
