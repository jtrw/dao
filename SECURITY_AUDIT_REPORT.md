# Звіт про аудит безпеки - DAO Library

**Дата аудиту:** 2025-11-05
**Версія:** 1.0
**Перевірено:** PHP DAO Library (jtrw/dao)

---

## Загальна інформація

**Тип проекту:** PHP бібліотека для роботи з базами даних
**Технології:** PHP >= 7.4, PDO, MySQL, PostgreSQL, MS SQL
**Залежності:** phpunit/phpunit, squizlabs/php_codesniffer

---

## Executive Summary

В результаті комплексної перевірки безпеки було знайдено **9 вразливостей**:
- **1 критична** вразливість (витік чутливих даних)
- **1 висока** вразливість (слабкі credentials)
- **4 середні** вразливості (SQL injection ризики)
- **3 рекомендації** по покращенню безпеки

**Загальна оцінка безпеки:** ⚠️ ПОТРЕБУЄ НЕГАЙНОЇ УВАГИ

---

## 🔴 КРИТИЧНІ ВРАЗЛИВОСТІ

### CVE-001: .env файл закоммічений в Git репозиторій
**Рівень загрози:** 🔴 CRITICAL
**CVSS Score:** 9.1 (Critical)
**CWE:** CWE-312 (Cleartext Storage of Sensitive Information)

#### Опис
Файл `.env` містить чутливі дані (паролі БД, секретні ключі) і присутній в git репозиторії з 2022 року, доступний у всій історії коммітів.

#### Розташування
- **Файл:** `.env`
- **Перший коміт:** 2022-05-14 (commit e2c78d57)
- **Останній коміт:** 2023-09-07 (commit 257b2ffc)

#### Знайдені чутливі дані
```env
APP_SECRET=appsecret
MYSQL_ROOT_PASSWORD=rootpw
MYSQL_USER=dao_user
MYSQL_PASSWORD=rootpw
```

#### Вплив
- ✅ **Конфіденційність:** HIGH - Всі credentials доступні публічно
- ✅ **Цілісність:** MEDIUM - Можливий несанкціонований доступ до БД
- ✅ **Доступність:** MEDIUM - Можливі атаки на інфраструктуру

#### Експлуатація
```bash
# Будь-хто може отримати credentials з історії
git clone https://github.com/jtrw/dao
git log --all --full-history -- .env
git show <commit-hash>:.env
```

#### Рекомендації щодо виправлення
**Пріоритет:** ⚡ ТЕРМІНОВО (0-1 день)

1. **Негайні дії:**
   ```bash
   # Додати .env до gitignore
   echo ".env" >> .gitignore

   # Видалити .env з індексу
   git rm --cached .env
   ```

2. **Очистити git історію:**
   ```bash
   # Використати BFG Repo-Cleaner
   bfg --delete-files .env
   git reflog expire --expire=now --all
   git gc --prune=now --aggressive

   # АБО використати git-filter-repo
   git filter-repo --path .env --invert-paths
   ```

3. **Змінити всі credentials:**
   - Згенерувати нові паролі для MySQL/PostgreSQL
   - Створити новий APP_SECRET
   - Оновити production сервери

4. **Створити шаблон:**
   ```bash
   # Створити .env.example
   cp .env .env.example
   # Замінити всі значення на placeholders
   sed -i 's/=.*/=changeme/g' .env.example
   git add .env.example
   ```

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

## 🟠 ВИСОКІ ВРАЗЛИВОСТІ

### CVE-002: Слабкі credentials за замовчуванням
**Рівень загрози:** 🟠 HIGH
**CVSS Score:** 7.5 (High)
**CWE:** CWE-521 (Weak Password Requirements)

#### Опис
Використання слабких, легко вгадуваних паролів в конфігураційних файлах.

#### Розташування
- **Файл:** `.env:2,16,19`
- **Файл:** `docker-compose.yml:52-53`

#### Знайдені проблеми
1. **MySQL/MariaDB:**
   - Root пароль: `rootpw` (7 символів, словникова атака)
   - User пароль: `rootpw` (ідентичний root паролю)

2. **PostgreSQL:**
   - Захардкоджено в docker-compose.yml
   - User: `postgres_user`
   - Password: `postgres_pass` (14 символів, але передбачуваний)

3. **Application Secret:**
   - `APP_SECRET=appsecret` (занадто простий, словникова атака)

#### Вплив
- Brute-force атаки тривіальні
- Credentials можна вгадати за кілька спроб
- Horizontal privilege escalation

#### Рекомендації щодо виправлення
**Пріоритет:** ⚡ ВИСОКИЙ (1-3 дні)

1. **Згенерувати сильні паролі:**
   ```bash
   # Генерація випадкових паролів
   openssl rand -base64 32  # Для APP_SECRET
   openssl rand -base64 24  # Для DB passwords
   ```

2. **Оновити .env:**
   ```env
   APP_SECRET=$(openssl rand -base64 32)
   MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24)
   MYSQL_PASSWORD=$(openssl rand -base64 24)
   ```

3. **Винести з docker-compose.yml:**
   ```yaml
   # Замість hardcoded значень
   environment:
     POSTGRES_USER: ${POSTGRESQL_USER}
     POSTGRES_PASSWORD: ${POSTGRESQL_PASSWORD}
   ```

4. **Для production:**
   - Використовувати secrets management (HashiCorp Vault, AWS Secrets Manager)
   - Ротація паролів кожні 90 днів
   - Різні credentials для кожного environment

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

## 🟡 СЕРЕДНІ ВРАЗЛИВОСТІ

### CVE-003: Потенційний SQL Injection через numeric keys
**Рівень загрози:** 🟡 MEDIUM
**CVSS Score:** 6.5 (Medium)
**CWE:** CWE-89 (SQL Injection)

#### Опис
В методі `getSqlCondition()` якщо ключ масиву числовий, значення використовується як SQL без валідації.

#### Розташування
- **Файл:** `src/ObjectAdapter.php:308`
- **Метод:** `getSqlCondition()`

#### Код з вразливістю
```php
foreach ($obj as $key => $item) {
    // XXX: if numeric then we get sql condition statement
    if (is_numeric($key)) {
        $conditionResult = $item;  // ← Прямий SQL без перевірки!
    } else {
        $conditionResult = $this->_getConditionResult($key, $item);
    }
```

#### Приклад експлуатації
```php
// Зловмисник може передати:
$condition = [
    0 => "1=1 OR '1'='1",  // Bypass authentication
    1 => "id=1; DROP TABLE users--"  // SQL injection
];

$db->select("SELECT * FROM users", $condition);
// Генерується: SELECT * FROM users WHERE 1=1 OR '1'='1 AND id=1; DROP TABLE users--
```

#### Вплив
- Обхід аутентифікації
- Витік даних
- Можливість видалення таблиць
- Модифікація даних

#### Рекомендації щодо виправлення
**Пріоритет:** 🔶 СЕРЕДНІЙ (3-7 днів)

1. **Додати валідацію:**
```php
private $allowedOperators = ['AND', 'OR', 'NOT', 'IN', 'BETWEEN', 'LIKE'];

foreach ($obj as $key => $item) {
    if (is_numeric($key)) {
        // Валідувати SQL statement
        if (!$this->isValidSqlCondition($item)) {
            throw new DatabaseException("Invalid SQL condition");
        }
        $conditionResult = $item;
    }
}

private function isValidSqlCondition(string $sql): bool {
    // Whitelist approach - дозволити тільки безпечні оператори
    // Заборонити небезпечні ключові слова
    $dangerousKeywords = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'EXEC', 'EXECUTE'];

    foreach ($dangerousKeywords as $keyword) {
        if (stripos($sql, $keyword) !== false) {
            return false;
        }
    }

    return true;
}
```

2. **Використовувати асоціативні масиви:**
```php
// Замість numeric keys
$condition = [
    0 => "status = 'active'"  // ❌ Небезпечно
];

// Використовувати
$condition = [
    'status' => 'active'  // ✅ Безпечно
];
```

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

### CVE-004: Відсутність prepared statements з параметрами
**Рівень загрози:** 🟡 MEDIUM
**CVSS Score:** 5.8 (Medium)
**CWE:** CWE-89 (SQL Injection)

#### Опис
Код використовує `PDO::prepare()` але не використовує parameter binding. Всі SQL запити будуються через string concatenation, що менш безпечно ніж параметризовані запити.

#### Розташування
- **Файл:** `src/ObjectPDOAdapter.php:58-82`
- **Метод:** `_execute()`

#### Код з проблемою
```php
private function _execute(string $sql): PDOStatement
{
    try {
        $query = $this->db->prepare($sql);  // SQL вже побудований повністю
    } catch (PDOException $exp) {
        throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
    }

    try {
        $res = $query->execute();  // Виконання БЕЗ параметрів
        if (!$res) {
            $info = $query->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
        }
    } catch (PDOException $exp) {
        throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
    }

    return $query;
}
```

#### Проблеми поточного підходу
1. SQL будується через `quote()` замість parameter binding
2. Неможливо використати prepared statement кеш
3. Більший ризик помилок при екрануванні
4. Складніше виявити SQL injection через code review

#### Порівняння
```php
// ❌ Поточний підхід (менш безпечний)
$sql = "SELECT * FROM users WHERE id = " . $db->quote($id);
$query = $db->prepare($sql);
$query->execute();

// ✅ Правильний підхід
$sql = "SELECT * FROM users WHERE id = :id";
$query = $db->prepare($sql);
$query->execute(['id' => $id]);
```

#### Вплив
- Підвищений ризик SQL injection при помилках
- Неможливість використати query caching
- Гірша продуктивність
- Складніший аудит безпеки

#### Рекомендації щодо виправлення
**Пріоритет:** 🔶 СЕРЕДНІЙ (1-2 тижні)

**Це потребує значного рефакторингу. Рекомендовані кроки:**

1. **Додати нову сигнатуру методу:**
```php
private function _execute(string $sql, array $params = []): PDOStatement
{
    try {
        $query = $this->db->prepare($sql);
    } catch (PDOException $exp) {
        throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
    }

    try {
        $res = $query->execute($params);  // ← Параметри окремо
        if (!$res) {
            $info = $query->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
        }
    } catch (PDOException $exp) {
        throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
    }

    return $query;
}
```

2. **Оновити методи для передачі параметрів:**
```php
public function getRow(string $sql, array $params = []): ValueObjectInterface
{
    $query = $this->_execute($sql, $params);

    $result = $query->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        $result = [];
    }

    return new ArrayLiteral($result);
}
```

3. **Рефакторити SQL builder для генерації параметрів:**
```php
// Замість
public function getSqlCondition(?array $obj = null): array

// Зробити
public function getSqlCondition(?array $obj = null): array
{
    return [
        'sql' => $sqlParts,
        'params' => $boundParams
    ];
}
```

4. **Поетапна міграція:**
   - Phase 1: Додати підтримку параметрів (backward compatible)
   - Phase 2: Оновити всі виклики
   - Phase 3: Deprecated старий API
   - Phase 4: Видалити deprecated код

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

### CVE-005: Небезпечна конкатенація в BETWEEN умові
**Рівень загрози:** 🟡 MEDIUM
**CVSS Score:** 5.3 (Medium)
**CWE:** CWE-89 (SQL Injection)

#### Опис
В методі `_getBetweenCondition()` існує гілка коду де `$item` використовується без екранування.

#### Розташування
- **Файл:** `src/ObjectAdapter.php:594`
- **Метод:** `_getBetweenCondition()`

#### Код з вразливістю
```php
private function _getBetweenCondition(array $buffer, $item): string
{
    $columnName = $this->quoteColumnName($buffer[0]);

    if (is_array($item)) {
        // ... безпечна обробка масиву
    } else {
        // ❌ НЕБЕЗПЕЧНО: $item використовується напряму!
        $condition = $columnName." BETWEEN ".$item;
    }

    return $condition;
}
```

#### Приклад експлуатації
```php
// Атакуючий передає:
$search = [
    'date&BETWEEN' => "2023-01-01' AND '2023-12-31' OR '1'='1"
];

// Генерується:
// WHERE `date` BETWEEN 2023-01-01' AND '2023-12-31' OR '1'='1
```

#### Вплив
- SQL injection в BETWEEN умовах
- Обхід фільтрів
- Несанкціонований доступ до даних

#### Рекомендації щодо виправлення
**Пріоритет:** 🔶 СЕРЕДНІЙ (3-7 днів)

```php
private function _getBetweenCondition(array $buffer, $item): string
{
    $columnName = $this->quoteColumnName($buffer[0]);

    if (is_array($item)) {
        if (count($item) == 1) {
            if (array_key_exists(0, $item)) {
                $operation = ' >= ';
                $value = $item[0];
            } else if (array_key_exists(1, $item)) {
                $operation = ' <= ';
                $value = $item[1];
            } else {
                throw new DatabaseException("Syntax error into BETWEEN condition");
            }

            $condition = $columnName.$operation.$this->quote($value);
        } else {
            $condition = $columnName." BETWEEN ".$this->quote($item[0]).
                static::SQL_AND.$this->quote($item[1]);
        }

    } else {
        // ✅ ВИПРАВЛЕНО: Валідація та парсинг
        if (!is_string($item)) {
            throw new DatabaseException("BETWEEN condition must be string or array");
        }

        // Парсити "value1 AND value2"
        $parts = preg_split('/\s+AND\s+/i', $item);
        if (count($parts) !== 2) {
            throw new DatabaseException("Invalid BETWEEN condition format");
        }

        $condition = $columnName." BETWEEN ".$this->quote(trim($parts[0])).
            static::SQL_AND.$this->quote(trim($parts[1]));
    }

    return $condition;
}
```

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

### CVE-006: Пряма конкатенація параметрів пагінації
**Рівень загрози:** 🟡 LOW-MEDIUM
**CVSS Score:** 4.2 (Medium)
**CWE:** CWE-89 (SQL Injection)

#### Опис
Метод `getSplitOnPages()` використовує пряму конкатенацію параметрів `$page` та `$col` в SQL запит.

#### Розташування
- **Файл:** `src/Driver/MysqlObjectDriver.php:60-79`
- **Метод:** `getSplitOnPages()`

#### Код з проблемою
```php
public function getSplitOnPages(DataAccessObjectInterface $object, string $query, int $col, int $page): array
{
    $result = [];
    if ($page !== 0) {
        $page -= 1;
    }

    if (!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
        $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
    }

    // ❌ Пряма конкатенація
    $query .= " LIMIT ".($page * $col).", ".$col;

    $result['rows']    = $object->getAll($query)->toNative();
    $result['cnt']     = $object->getOne('SELECT FOUND_ROWS()')->toNative();
    $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

    return $result;
}
```

#### Аналіз ризику
**Позитивні фактори:**
- ✅ Параметри типізовані як `int` (PHP 7.4+)
- ✅ Type juggling запобігає injection рядків
- ✅ `$page - 1` виконує int приведення

**Потенційні проблеми:**
- ⚠️ Якщо викликається з ненадійним джерелом
- ⚠️ Можливе integer overflow (дуже великі числа)
- ⚠️ Немає валідації діапазонів

#### Потенційна експлуатація
```php
// PHP дозволяє float як int:
$page = 1.5e9;  // Великий float
$col = 2.5e9;
// $page * $col може викликати overflow
```

#### Вплив
- LOW: Через type hints важко експлуатувати
- Можливий DoS через великі LIMIT значення
- Resource exhaustion

#### Рекомендації щодо виправлення
**Пріоритет:** 🟢 НИЗЬКИЙ (1-2 тижні)

```php
public function getSplitOnPages(
    DataAccessObjectInterface $object,
    string $query,
    int $col,
    int $page
): array {
    // Валідація параметрів
    if ($col <= 0) {
        throw new DatabaseException("Invalid column count: must be positive");
    }

    if ($col > 1000) {
        throw new DatabaseException("Column count too large: maximum 1000");
    }

    if ($page < 0) {
        throw new DatabaseException("Invalid page number: must be non-negative");
    }

    $result = [];
    if ($page !== 0) {
        $page -= 1;
    }

    if (!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
        $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
    }

    // Перевірка overflow
    $offset = $page * $col;
    if ($offset < 0) {
        throw new DatabaseException("Pagination overflow detected");
    }

    // ✅ Використати sprintf для ясності
    $query .= sprintf(" LIMIT %d, %d", $offset, $col);

    $result['rows']    = $object->getAll($query)->toNative();
    $result['cnt']     = $object->getOne('SELECT FOUND_ROWS()')->toNative();
    $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

    return $result;
}
```

**Альтернатива (краще):**
```php
// Використати prepared statement
$stmt = $pdo->prepare($query . " LIMIT :offset, :limit");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $col, PDO::PARAM_INT);
$stmt->execute();
```

#### Статус
- [ ] Виправлено
- [ ] В роботі
- [ ] Заплановано

---

## 🔵 РЕКОМЕНДАЦІЇ ПО ПОКРАЩЕННЮ

### REC-001: Оновлення PHP версії
**Пріоритет:** 🟢 СЕРЕДНІЙ

#### Поточний стан
- Вимагається: `PHP >= 7.4`
- PHP 7.4 EOL: 28 листопада 2022
- Більше не отримує security updates

#### Відомі вразливості PHP 7.4
- CVE-2022-31631 (PDO)
- CVE-2022-31630 (imageloadfont)
- CVE-2022-31629 (Path traversal)

#### Рекомендації
1. **Оновити до PHP 8.1+:**
   ```json
   {
     "require": {
       "php": ">=8.1"
     }
   }
   ```

2. **Переваги PHP 8.1+:**
   - Active security support
   - Enums
   - Readonly properties
   - Fibers
   - Кращий JIT compiler

3. **План міграції:**
   - Тестування на PHP 8.1
   - Виправлення deprecated warnings
   - Update CI/CD
   - Оновити composer.json

---

### REC-002: Додати .env.example
**Пріоритет:** 🟢 ВИСОКИЙ

#### Опис
Створити template файл з placeholder значеннями для нових розробників.

#### Реалізація
```bash
# Створити .env.example
cat > .env.example << 'EOF'
APP_ENV=dev
APP_SECRET=CHANGE_ME_TO_RANDOM_SECRET
APP_VERSION=1.0.0
APP_DEBUG=0
PHP_VER=8.1

###> mysql ###
MYSQL_ROOT_PASSWORD=CHANGE_ME
MYSQL_DATABASE=dao
MYSQL_USER=dao_user
MYSQL_PASSWORD=CHANGE_ME
MYSQL_HOST=dao_mariadb
MYSQL_PORT=3306
###< mysql ###

###> postgresql ###
POSTGRESQL_DB=dao
POSTGRESQL_HOST=dao_postgres
POSTGRESQL_USER=CHANGE_ME
POSTGRESQL_PASSWORD=CHANGE_ME
###< postgresql ###
EOF

# Додати інструкції в README
cat >> README.md << 'EOF'

## Configuration

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Generate secure credentials:
   ```bash
   # Generate APP_SECRET
   openssl rand -base64 32

   # Generate DB passwords
   openssl rand -base64 24
   ```

3. Update `.env` with your credentials
EOF
```

---

### REC-003: Додати GitHub Security Scanning
**Пріоритет:** 🟢 СЕРЕДНІЙ

#### Опис
Налаштувати автоматичне сканування коду на вразливості.

#### Реалізація

**1. CodeQL Analysis:**
```yaml
# .github/workflows/codeql-analysis.yml
name: "CodeQL"

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]
  schedule:
    - cron: '0 0 * * 1'

jobs:
  analyze:
    name: Analyze
    runs-on: ubuntu-latest
    permissions:
      actions: read
      contents: read
      security-events: write

    strategy:
      matrix:
        language: [ 'php' ]

    steps:
    - name: Checkout repository
      uses: actions/checkout@v3

    - name: Initialize CodeQL
      uses: github/codeql-action/init@v2
      with:
        languages: ${{ matrix.language }}

    - name: Perform CodeQL Analysis
      uses: github/codeql-action/analyze@v2
```

**2. Dependency Scanning:**
```yaml
# .github/workflows/dependency-review.yml
name: 'Dependency Review'
on: [pull_request]

jobs:
  dependency-review:
    runs-on: ubuntu-latest
    steps:
      - name: 'Checkout Repository'
        uses: actions/checkout@v3
      - name: 'Dependency Review'
        uses: actions/dependency-review-action@v3
```

**3. PHPStan Security Rules:**
```bash
composer require --dev phpstan/phpstan
composer require --dev phpstan/phpstan-strict-rules

# phpstan.neon
parameters:
  level: 8
  paths:
    - src
  strictRules:
    booleansInConditions: true
```

---

## Додаткові рекомендації

### Security Best Practices

1. **Input Validation:**
   - Валідувати всі вхідні дані
   - Використовувати whitelist approach
   - Type hints для всіх параметрів

2. **Error Handling:**
   - Не показувати SQL queries в production
   - Логувати помилки безпечно
   - Використовувати structured logging

3. **Database Security:**
   - Principle of least privilege для DB users
   - Окремі credentials для read/write
   - Regular security audits

4. **Development Workflow:**
   - Mandatory code review
   - Security testing в CI/CD
   - Regular dependency updates

---

## Пріоритети виправлення

### Фаза 1: ТЕРМІНОВО (0-3 дні)
1. ✅ Видалити .env з git та змінити credentials (CVE-001)
2. ✅ Оновити всі паролі (CVE-002)
3. ✅ Додати .env.example

### Фаза 2: ВИСОКИЙ ПРІОРИТЕТ (1-2 тижні)
1. Виправити SQL injection через numeric keys (CVE-003)
2. Виправити BETWEEN vulnerability (CVE-005)
3. Додати валідацію в getSplitOnPages (CVE-006)

### Фаза 3: СЕРЕДНІЙ ПРІОРИТЕТ (2-4 тижні)
1. Рефакторити на prepared statements (CVE-004)
2. Оновити PHP версію (REC-001)
3. Додати security scanning (REC-003)

### Фаза 4: ДОВГОТРИВАЛЕ (1-3 місяці)
1. Комплексний security audit
2. Penetration testing
3. Security documentation

---

## Контакти та підтримка

**Для звітування про вразливості:**
- Email: brdnlsrg@gmail.com
- GitHub Issues: https://github.com/jtrw/dao/issues (для non-critical)
- Private disclosure: contact maintainers directly

**Security Response Time:**
- Critical: 24-48 hours
- High: 3-7 days
- Medium: 1-2 weeks
- Low: Best effort

---

## Версії звіту

- **v1.0** (2025-11-05): Початковий security audit

---

**Цей звіт згенеровано автоматичним аналізом коду. Рекомендується періодично проводити аудит безпеки.**
