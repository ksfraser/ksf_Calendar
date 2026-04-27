# Use Case Document

## Overview
This document describes the primary use cases for the ksf_ModulesDAO library, focusing on how developers and systems interact with the database abstraction layer in various scenarios.

## Actors

### Primary Actors
- **Module Developer**: KS Fraser developer creating new modules
- **System Administrator**: Configures database connections and environments
- **Application Framework**: Automated systems using the DAO library
- **Testing Framework**: Automated test suites validating functionality

### Secondary Actors
- **Database Server**: MySQL, PostgreSQL, or other database systems
- **FrontAccounting System**: FA environment providing database functions
- **Composer**: PHP dependency management system
- **PHPUnit**: Testing framework for validation

## Use Case Specifications

### Use Case 1: Basic Database Connection
**Actor**: Module Developer
**Preconditions**:
- ksf_ModulesDAO library is installed via Composer
- Database server is running and accessible
- Connection credentials are available

**Main Flow**:
1. Developer includes ksf_ModulesDAO in module dependencies
2. Developer calls `DatabaseAdapterFactory::create()` with appropriate parameters
3. Factory detects available database extensions and platform
4. Factory instantiates appropriate adapter (PDO, MySQLi, or FA)
5. Adapter establishes database connection
6. Adapter returns configured connection object

**Postconditions**:
- Active database connection ready for operations
- Connection parameters validated and stored

**Alternative Flows**:
- **A1: Platform Detection Fails**
  1. Factory falls back to PDO adapter
  2. System logs platform detection warning
  3. Connection proceeds with reduced optimization

- **A2: Connection Parameters Invalid**
  1. Adapter throws `ConnectionException`
  2. Developer receives clear error message
  3. System suggests parameter validation

### Use Case 2: Data Retrieval Operations
**Actor**: Application Framework
**Preconditions**:
- Active database connection established
- SQL query prepared with parameter placeholders

**Main Flow**:
1. Application calls `adapter->query($sql, $params)`
2. Adapter validates SQL syntax and parameters
3. Adapter executes query using platform-specific methods
4. Adapter processes result set into standardized array format
5. Adapter returns result array to application

**Postconditions**:
- Query results available in consistent array format
- Database connection remains active for further operations

**Alternative Flows**:
- **A1: Query Syntax Error**
  1. Adapter throws `QueryException` with detailed error
  2. Application logs error for debugging
  3. Transaction rolled back if active

- **A2: Large Result Set**
  1. Adapter streams results to prevent memory exhaustion
  2. Application processes results in chunks
  3. Memory usage monitored and controlled

### Use Case 3: Data Modification Operations
**Actor**: Module Developer
**Preconditions**:
- Active database connection established
- SQL modification statement prepared
- Transaction context established (optional)

**Main Flow**:
1. Developer calls `adapter->execute($sql, $params)`
2. Adapter validates statement and binds parameters
3. Adapter executes statement using platform-specific methods
4. Adapter returns execution status and affected row count
5. Transaction committed if auto-commit enabled

**Postconditions**:
- Database modifications persisted
- Affected row count available for validation
- Last insert ID available for INSERT operations

**Alternative Flows**:
- **A1: Constraint Violation**
  1. Adapter throws `QueryException` with constraint details
  2. Application handles constraint violation appropriately
  3. Transaction rolled back to maintain consistency

- **A2: Deadlock Detection**
  1. Adapter detects deadlock condition
  2. Automatic retry with exponential backoff
  3. Transaction isolation level adjusted if necessary

### Use Case 4: Transaction Management
**Actor**: Application Framework
**Preconditions**:
- Active database connection established
- Platform supports transactions

**Main Flow**:
1. Application calls `adapter->beginTransaction()`
2. Adapter initiates transaction using platform methods
3. Application executes multiple operations
4. Application calls `adapter->commit()` on success
5. Transaction committed and changes persisted

**Postconditions**:
- All operations within transaction committed atomically
- Database consistency maintained

**Alternative Flows**:
- **A1: Operation Failure**
  1. Exception thrown during operation sequence
  2. Adapter automatically calls `rollback()`
  3. All changes within transaction reverted
  4. Application notified of transaction failure

- **A2: Platform Without Transaction Support**
  1. Adapter provides no-op transaction methods
  2. Operations execute individually
  3. System logs transaction limitation warning

### Use Case 5: Cross-Platform Deployment
**Actor**: System Administrator
**Preconditions**:
- Multiple deployment environments configured
- Different database platforms available

**Main Flow**:
1. Administrator configures environment-specific settings
2. Application detects deployment platform
3. Factory selects appropriate adapter automatically
4. Application operates identically across platforms
5. Platform-specific optimizations applied transparently

**Postconditions**:
- Application works consistently across all supported platforms
- Performance optimized for each platform

**Alternative Flows**:
- **A1: Manual Adapter Selection**
  1. Administrator specifies adapter type explicitly
  2. Factory bypasses automatic detection
  3. Application uses specified adapter regardless of platform

- **A2: Platform Feature Unavailable**
  1. Adapter detects missing platform features
  2. Graceful degradation to basic functionality
  3. System logs feature limitation warnings

### Use Case 6: Error Handling and Recovery
**Actor**: Application Framework
**Preconditions**:
- Database operation in progress
- Error condition encountered

**Main Flow**:
1. Database operation throws exception
2. Adapter catches platform-specific errors
3. Adapter normalizes error into standard exception type
4. Application receives consistent error information
5. Application implements appropriate error recovery

**Postconditions**:
- Error handled consistently regardless of platform
- Application can implement platform-independent error handling

**Alternative Flows**:
- **A1: Connection Loss**
  1. Adapter detects connection failure
  2. Automatic reconnection attempt
  3. Operation retried with same parameters
  4. Failure reported if reconnection unsuccessful

- **A2: Timeout Condition**
  1. Adapter detects query timeout
  2. Operation cancelled gracefully
  3. Connection validated and reset if necessary
  4. Timeout exception thrown with context

### Use Case 7: Testing and Validation
**Actor**: Testing Framework
**Preconditions**:
- Test environment configured
- Test database available

**Main Flow**:
1. Test framework creates adapter instance
2. Test executes database operations
3. Test validates results against expected outcomes
4. Test verifies error conditions handled properly
5. Test reports pass/fail status

**Postconditions**:
- Code functionality validated across platforms
- Regression issues identified and resolved

**Alternative Flows**:
- **A1: Mock Database Testing**
  1. Test framework uses mock adapter
  2. Database operations simulated
  3. Test validates logic without actual database
  4. Performance tests run without database load

- **A2: Integration Testing**
  1. Test framework uses real database
  2. Full application stack tested
  3. Database state validated between tests
  4. Test data cleaned up after execution

## Use Case Relationships

### Includes Relationships
- **UC1** includes **UC5** (platform detection)
- **UC2** includes **UC6** (error handling)
- **UC3** includes **UC6** (error handling)
- **UC4** includes **UC6** (error handling)

### Extends Relationships
- **UC6** extends all database operation use cases
- **UC7** extends all use cases for validation purposes

## Non-Functional Requirements Traceability

### Performance Requirements
- **UC1**: Connection establishment < 100ms
- **UC2**: Query execution < 10ms for simple queries
- **UC4**: Transaction commit < 50ms

### Reliability Requirements
- **UC6**: Comprehensive error recovery mechanisms
- **UC4**: ACID transaction support where available
- **UC5**: Graceful platform feature degradation

### Security Requirements
- **UC1**: Secure credential handling
- **UC2**: SQL injection prevention through parameterization
- **UC3**: Input validation and sanitization

This use case document provides a comprehensive view of how the ksf_ModulesDAO library serves different stakeholders and handles various operational scenarios while maintaining consistent behavior across different database platforms.</content>
<parameter name="filePath">c:\Users\prote\Documents\software-devel\FA_ProductAttributes\ksf-modules-dao\Project Docs\Use_Case_Document.md