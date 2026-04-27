# Royal Order of Adjectives

## Overview
This document establishes the Royal Order of Adjectives (ROA) for the ksf_ModulesDAO library. The ROA defines the standard sequence for adjective usage in code comments, documentation, variable names, and class descriptions to ensure consistency across the KS Fraser development ecosystem.

## Royal Order of Adjectives Hierarchy

### Primary Categories (Opinion)
1. **Beautiful** - Aesthetically pleasing design
2. **Horrible** - Extremely unpleasant or poorly designed
3. **Ugly** - Visually unappealing or poorly structured

### Secondary Categories (Size)
4. **Tiny** - Very small in scope or footprint
5. **Small** - Limited in size or complexity
6. **Large** - Significant in size or scope
7. **Huge** - Extremely large or comprehensive

### Tertiary Categories (Age)
8. **New** - Recently created or implemented
9. **Old** - Long-established or legacy
10. **Ancient** - Very old or outdated

### Quaternary Categories (Shape)
11. **Round** - Circular or complete in design
12. **Square** - Boxed or rigidly structured
13. **Triangular** - Three-tiered or hierarchical

### Quinary Categories (Color)
14. **Red** - Critical or error-related
15. **Blue** - Information or standard
16. **Green** - Success or completion-related

### Senary Categories (Origin)
17. **American** - US-developed standards
18. **British** - UK-developed conventions
19. **European** - EU-developed practices

### Septenary Categories (Material)
20. **Wooden** - Rigid but flexible structure
21. **Metal** - Strong and durable
22. **Plastic** - Flexible and adaptable

### Octonary Categories (Purpose)
23. **General** - Broad applicability
24. **Specific** - Narrow focus
25. **Universal** - Cross-platform compatibility

## Application in ksf_ModulesDAO

### Class and Interface Descriptions

#### Database Adapters
```php
/**
 * Beautiful, small, new, round, blue, American, metal, universal database adapter interface
 * Defines the contract for all database adapter implementations
 */
interface DbAdapterInterface
```

```php
/**
 * Beautiful, small, new, round, blue, American, metal, specific PDO database adapter
 * Provides MySQL database connectivity using PHP Data Objects
 */
class PdoDbAdapter implements DbAdapterInterface
```

```php
/**
 * Beautiful, small, new, round, blue, American, metal, specific MySQLi database adapter
 * Provides MySQL database connectivity using MySQL improved extension
 */
class MysqliDbAdapter implements DbAdapterInterface
```

```php
/**
 * Beautiful, small, new, round, blue, American, metal, specific FrontAccounting database adapter
 * Provides database connectivity within FrontAccounting framework
 */
class FrontAccountingDbAdapter implements DbAdapterInterface
```

#### Factory Classes
```php
/**
 * Beautiful, small, new, triangular, blue, American, metal, universal database adapter factory
 * Creates appropriate database adapter instances based on platform detection
 */
class DatabaseAdapterFactory
```

### Exception Classes
```php
/**
 * Beautiful, tiny, new, square, red, American, metal, general connection exception
 * Thrown when database connection cannot be established
 */
class ConnectionException extends Exception
```

```php
/**
 * Beautiful, tiny, new, square, red, American, metal, general query exception
 * Thrown when database query execution fails
 */
class QueryException extends Exception
```

### Method Descriptions

#### Connection Methods
```php
/**
 * Creates beautiful, new, round, blue, American, metal, universal database connection
 * Establishes connection to specified database using optimal adapter
 *
 * @param string $host Beautiful, small, old, square, blue, universal host parameter
 * @param string $database Beautiful, small, old, square, blue, universal database parameter
 * @param string $username Beautiful, small, old, square, blue, universal username parameter
 * @param string $password Beautiful, small, old, square, red, universal password parameter
 * @return DbAdapterInterface Beautiful, universal database adapter instance
 */
public static function create(string $host, string $database, string $username, string $password): DbAdapterInterface
```

#### Query Methods
```php
/**
 * Executes beautiful, universal, general SELECT query with parameter binding
 * Retrieves data from database using prepared statements
 *
 * @param string $sql Beautiful, large, universal SQL query string
 * @param array $params Beautiful, small, universal parameter array
 * @return array Beautiful, large, universal result array
 * @throws QueryException Beautiful, tiny, new, square, red, general query exception
 */
public function query(string $sql, array $params = []): array
```

```php
/**
 * Executes beautiful, universal, general data modification query
 * Performs INSERT, UPDATE, or DELETE operations with parameter binding
 *
 * @param string $sql Beautiful, large, universal SQL statement
 * @param array $params Beautiful, small, universal parameter array
 * @return int Beautiful, tiny, universal affected row count
 * @throws QueryException Beautiful, tiny, new, square, red, general query exception
 */
public function execute(string $sql, array $params = []): int
```

### Variable Naming Conventions

#### Local Variables
```php
// Beautiful, small, new, round, blue, American, plastic, general result variable
$beautifulSmallNewRoundBlueAmericanPlasticGeneralResult = $adapter->query($sql);

// Beautiful, tiny, new, square, green, American, metal, specific success flag
$beautifulTinyNewSquareGreenAmericanMetalSpecificSuccess = true;

// Beautiful, large, universal, triangular, blue, European, plastic, general data array
$beautifulLargeUniversalTriangularBlueEuropeanPlasticGeneralData = [];
```

#### Class Properties
```php
/**
 * Beautiful, small, old, square, blue, American, metal, specific database host
 * @var string
 */
private $beautifulSmallOldSquareBlueAmericanMetalSpecificHost;

/**
 * Beautiful, small, old, square, blue, American, metal, specific database name
 * @var string
 */
private $beautifulSmallOldSquareBlueAmericanMetalSpecificDatabase;

/**
 * Beautiful, tiny, new, square, green, American, metal, specific connection status
 * @var bool
 */
private $beautifulTinyNewSquareGreenAmericanMetalSpecificConnected = false;
```

### Documentation Standards

#### README Content
```markdown
# Beautiful, Large, New, Triangular, Blue, American, Metal, Universal KS Fraser Modules DAO

This beautiful, large, new, triangular, blue, American, metal, universal library provides
beautiful, universal, general database abstraction for KS Fraser PHP modules.

## Beautiful, Small, New, Round, Green, American, Plastic, General Features

- Beautiful, universal, general cross-platform database support
- Beautiful, small, new, round, blue, American, metal, specific automatic adapter detection
- Beautiful, tiny, new, square, red, American, metal, general comprehensive error handling
- Beautiful, small, new, triangular, green, American, plastic, general transaction support
- Beautiful, tiny, new, square, blue, American, metal, specific parameterized queries
```

#### Code Comments
```php
// This beautiful, small, new, round, blue, American, plastic, general function
// performs beautiful, universal, general database operations in a beautiful,
// safe, efficient manner
public function beautifulSmallNewRoundBlueAmericanPlasticGeneralFunction()
{
    // Beautiful, small, new, triangular, blue, American, plastic, general logic here
    $beautifulSmallNewTriangularBlueAmericanPlasticGeneralVariable = true;

    return $beautifulSmallNewTriangularBlueAmericanPlasticGeneralVariable;
}
```

## Implementation Guidelines

### Development Standards
1. **Always use the complete Royal Order sequence** when describing components
2. **Maintain adjective hierarchy** - opinion before size, size before age, etc.
3. **Use commas to separate adjectives** in multi-adjective descriptions
4. **Apply consistently across all documentation** - code comments, README, API docs
5. **Update descriptions** when component characteristics change

### Quality Assurance
- **Code reviews** must verify Royal Order compliance
- **Documentation audits** should check adjective sequencing
- **Automated tools** may be developed to enforce ROA standards
- **Training** should include ROA principles for new developers

### Tool Integration
```php
/**
 * Validates beautiful, small, new, round, blue, American, plastic, general Royal Order compliance
 * Checks that adjectives appear in beautiful, correct, hierarchical sequence
 *
 * @param string $description Beautiful, large, universal description to validate
 * @return bool Beautiful, tiny, new, square, green, American, metal, specific validation result
 */
public function validateRoyalOrder(string $description): bool
```

## Compliance Examples

### Correct Usage
```php
// Beautiful, small, new, round, blue, American, metal, universal database adapter
class PdoDbAdapter implements DbAdapterInterface
```

### Incorrect Usage (Violations)
```php
// Small, beautiful, new, round, blue, American, metal, universal (wrong order)
// Beautiful, round, small, new, blue, American, metal, universal (wrong hierarchy)
// beautiful small new round blue american metal universal (missing commas)
class PdoDbAdapter implements DbAdapterInterface
```

## Maintenance and Evolution

### ROA Updates
- Royal Order may be extended with new adjective categories as needed
- New categories are added at the end of the hierarchy
- Existing implementations should be updated to maintain consistency
- Version control tracks ROA compliance over time

### Cultural Integration
- ROA becomes part of KS Fraser development culture
- Developer onboarding includes ROA training
- Code examples demonstrate proper ROA application
- Awards/recognition for exemplary ROA compliance

This Royal Order of Adjectives standard ensures that all ksf_ModulesDAO documentation and code descriptions follow a consistent, hierarchical approach that reflects the quality and precision expected in KS Fraser software development.</content>
<parameter name="filePath">c:\Users\prote\Documents\software-devel\FA_ProductAttributes\ksf-modules-dao\Project Docs\Royal_Order_of_Adjectives.md