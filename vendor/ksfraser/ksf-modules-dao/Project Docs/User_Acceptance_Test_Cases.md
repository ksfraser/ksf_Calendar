# User Acceptance Test Cases

## Overview
This document outlines the user acceptance test cases for the ksf_ModulesDAO library. These tests validate that the library meets business requirements and provides expected functionality for KS Fraser module developers.

## Test Environment Setup

### Prerequisites
- PHP 7.3 or higher installed
- Composer package manager available
- MySQL 5.7+ or MariaDB 10.0+ database server
- PHPUnit testing framework
- FrontAccounting 2.4+ (for FA-specific tests)

### Test Data Setup
```sql
-- Create test database
CREATE DATABASE ksf_dao_test;

-- Create test user with appropriate permissions
CREATE USER 'ksf_test'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON ksf_dao_test.* TO 'ksf_test'@'localhost';

-- Create test tables
USE ksf_dao_test;

CREATE TABLE test_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE test_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    active BOOLEAN DEFAULT TRUE
);

-- Insert test data
INSERT INTO test_users (username, email) VALUES
('testuser1', 'test1@example.com'),
('testuser2', 'test2@example.com');

INSERT INTO test_products (name, price, category_id) VALUES
('Test Product 1', 29.99, 1),
('Test Product 2', 49.99, 2);
```

## Test Case Categories

### Category 1: Basic Functionality Tests

#### UAT-1.1: Library Installation and Loading
**Test Case ID**: UAT-1.1
**Test Case Name**: Composer Installation and Autoloading
**Priority**: Critical
**Test Type**: Installation

**Preconditions**:
- Composer installed on test system
- Internet connection available for package download

**Test Steps**:
1. Create new PHP project directory
2. Run `composer require ksfraser/ksf-modules-dao`
3. Create test PHP file with namespace import
4. Execute test file

**Expected Results**:
- Package downloads successfully
- No installation errors
- Classes load without errors
- Autoloading functions correctly

**Acceptance Criteria**:
- Installation completes without errors
- All classes are accessible via autoloading
- No missing dependencies reported

#### UAT-1.2: Database Connection Establishment
**Test Case ID**: UAT-1.2
**Test Case Name**: Basic Database Connection
**Priority**: Critical
**Test Type**: Functional

**Preconditions**:
- Test database created and accessible
- Valid connection credentials available

**Test Steps**:
1. Create DatabaseAdapterFactory instance
2. Call `create()` method with valid MySQL credentials
3. Verify connection object returned
4. Execute simple SELECT query
5. Close connection

**Expected Results**:
- Connection established successfully
- Query executes and returns expected results
- No connection errors or timeouts
- Connection closes gracefully

**Acceptance Criteria**:
- Connection time < 5 seconds
- No PHP errors or warnings
- Query results match expected data

### Category 2: Adapter Functionality Tests

#### UAT-2.1: PDO Adapter Operations
**Test Case ID**: UAT-2.1
**Test Case Name**: PDO Adapter CRUD Operations
**Priority**: High
**Test Type**: Functional

**Preconditions**:
- PDO MySQL extension enabled
- Test database populated with data

**Test Steps**:
1. Establish PDO adapter connection
2. Execute SELECT query to retrieve test data
3. Execute INSERT to add new record
4. Execute UPDATE to modify existing record
5. Execute DELETE to remove record
6. Verify all operations completed successfully

**Expected Results**:
- All CRUD operations execute successfully
- Data integrity maintained
- Proper error handling for invalid operations
- Transaction rollback on failures

**Acceptance Criteria**:
- SELECT returns correct data format
- INSERT returns valid insert ID
- UPDATE/DELETE return correct affected row count
- All operations complete within performance thresholds

#### UAT-2.2: MySQLi Adapter Operations
**Test Case ID**: UAT-2.2
**Test Case Name**: MySQLi Adapter CRUD Operations
**Priority**: High
**Test Type**: Functional

**Preconditions**:
- MySQLi extension enabled
- Test database populated with data

**Test Steps**:
1. Establish MySQLi adapter connection
2. Execute SELECT query to retrieve test data
3. Execute INSERT to add new record
4. Execute UPDATE to modify existing record
5. Execute DELETE to remove record
6. Verify all operations completed successfully

**Expected Results**:
- All CRUD operations execute successfully
- Data integrity maintained
- Proper error handling for invalid operations
- Transaction rollback on failures

**Acceptance Criteria**:
- SELECT returns correct data format
- INSERT returns valid insert ID
- UPDATE/DELETE return correct affected row count
- All operations complete within performance thresholds

#### UAT-2.3: FrontAccounting Adapter Operations
**Test Case ID**: UAT-2.3
**Test Case Name**: FA Adapter Integration
**Priority**: High
**Test Type**: Integration

**Preconditions**:
- FrontAccounting installation available
- FA database functions accessible
- Test data in FA database

**Test Steps**:
1. Establish FA adapter connection
2. Execute SELECT query using FA functions
3. Execute INSERT using FA db_insert
4. Execute UPDATE using FA db_update
5. Execute DELETE using FA db_delete
6. Verify all operations completed successfully

**Expected Results**:
- All operations use FA database functions
- Data integrity maintained within FA context
- Proper error handling for FA-specific errors
- Operations integrate seamlessly with FA framework

**Acceptance Criteria**:
- FA database functions called correctly
- No conflicts with existing FA code
- Operations complete within FA performance expectations

### Category 3: Transaction Management Tests

#### UAT-3.1: Transaction Commit
**Test Case ID**: UAT-3.1
**Test Case Name**: Transaction Commit Functionality
**Priority**: High
**Test Type**: Functional

**Preconditions**:
- Database with transaction support
- Test data available for modification

**Test Steps**:
1. Begin transaction
2. Execute multiple INSERT/UPDATE operations
3. Commit transaction
4. Verify all changes persisted
5. Confirm transaction state

**Expected Results**:
- All operations within transaction committed
- Data changes visible to other connections
- Transaction log shows commit
- No partial updates remain

**Acceptance Criteria**:
- ACID properties maintained
- No data corruption
- Commit operation completes successfully

#### UAT-3.2: Transaction Rollback
**Test Case ID**: UAT-3.2
**Test Case Name**: Transaction Rollback Functionality
**Priority**: High
**Test Type**: Functional

**Preconditions**:
- Database with transaction support
- Test scenario that will cause failure

**Test Steps**:
1. Begin transaction
2. Execute initial operations successfully
3. Execute operation that will fail (constraint violation)
4. Verify automatic rollback
5. Confirm no changes persisted

**Expected Results**:
- Failed operation triggers rollback
- All changes within transaction reverted
- Database state unchanged
- Clear error message provided

**Acceptance Criteria**:
- No partial data modifications
- Database consistency maintained
- Proper error reporting

### Category 4: Error Handling Tests

#### UAT-4.1: Connection Error Handling
**Test Case ID**: UAT-4.1
**Test Case Name**: Invalid Connection Parameters
**Priority**: Medium
**Test Type**: Negative

**Preconditions**:
- Invalid database credentials prepared

**Test Steps**:
1. Attempt connection with invalid host
2. Attempt connection with invalid credentials
3. Attempt connection with invalid database name
4. Verify appropriate exceptions thrown

**Expected Results**:
- ConnectionException thrown for each invalid scenario
- Clear, descriptive error messages
- No system crashes or security leaks
- Connection attempts fail gracefully

**Acceptance Criteria**:
- Exceptions provide actionable error information
- No sensitive information exposed in errors
- System remains stable after failed connections

#### UAT-4.2: Query Error Handling
**Test Case ID**: UAT-4.2
**Test Case Name**: Invalid SQL Query Handling
**Priority**: Medium
**Test Type**: Negative

**Preconditions**:
- Valid database connection established

**Test Steps**:
1. Execute syntactically invalid SQL
2. Execute SQL with invalid table references
3. Execute SQL with constraint violations
4. Verify appropriate exceptions thrown

**Expected Results**:
- QueryException thrown for each error scenario
- Detailed error information provided
- Connection remains usable for subsequent queries
- No data corruption occurs

**Acceptance Criteria**:
- Error messages include SQL state and error details
- Connection state preserved
- Subsequent valid queries execute successfully

### Category 5: Performance Tests

#### UAT-5.1: Connection Pooling Performance
**Test Case ID**: UAT-5.1
**Test Case Name**: Multiple Connection Performance
**Priority**: Medium
**Test Type**: Performance

**Preconditions**:
- Test database server with sufficient resources
- Multiple connection test script prepared

**Test Steps**:
1. Establish 10 concurrent connections
2. Execute queries simultaneously
3. Measure connection establishment time
4. Measure query execution time
5. Close all connections

**Expected Results**:
- All connections established within 2 seconds total
- Queries execute within expected time limits
- No connection pool exhaustion
- Memory usage remains within limits

**Acceptance Criteria**:
- Average connection time < 200ms
- No memory leaks detected
- System remains responsive under load

#### UAT-5.2: Large Dataset Handling
**Test Case ID**: UAT-5.2
**Test Case Name**: Large Result Set Processing
**Priority**: Medium
**Test Type**: Performance

**Preconditions**:
- Test database with large dataset (10,000+ records)

**Test Steps**:
1. Execute SELECT query returning large result set
2. Process results in chunks
3. Monitor memory usage
4. Measure processing time
5. Verify all data processed correctly

**Expected Results**:
- Large result sets processed without memory exhaustion
- Memory usage remains stable
- Processing completes within reasonable time
- All data integrity maintained

**Acceptance Criteria**:
- Memory usage < 100MB for 10K records
- Processing time scales linearly with data size
- No data loss or corruption

### Category 6: Cross-Platform Compatibility Tests

#### UAT-6.1: Platform Auto-Detection
**Test Case ID**: UAT-6.1
**Test Case Name**: Automatic Adapter Selection
**Priority**: High
**Test Type**: Compatibility

**Preconditions**:
- Multiple PHP environments with different extensions
- Same test database accessible from all environments

**Test Steps**:
1. Deploy code to environment with PDO only
2. Deploy code to environment with MySQLi only
3. Deploy code to FA environment
4. Execute identical operations in each environment
5. Verify consistent results across platforms

**Expected Results**:
- Correct adapter selected for each environment
- Operations produce identical results
- Performance optimized for each platform
- No platform-specific errors

**Acceptance Criteria**:
- Factory selects appropriate adapter automatically
- Results format consistent across adapters
- Platform-specific optimizations applied

#### UAT-6.2: Feature Parity Validation
**Test Case ID**: UAT-6.2
**Test Case Name**: Cross-Platform Feature Consistency
**Priority**: High
**Test Type**: Compatibility

**Preconditions**:
- All supported database platforms available
- Comprehensive test suite prepared

**Test Steps**:
1. Execute full test suite on MySQL with PDO
2. Execute full test suite on MySQL with MySQLi
3. Execute full test suite on FA environment
4. Compare results across all platforms
5. Verify feature availability and behavior consistency

**Expected Results**:
- All features available on all platforms
- Behavior consistent within platform limitations
- Clear documentation of platform differences
- Graceful degradation where features unavailable

**Acceptance Criteria**:
- Core functionality works on all platforms
- Platform limitations clearly documented
- No unexpected behavior differences

## Test Execution Guidelines

### Test Execution Order
1. Execute installation tests first (UAT-1.x)
2. Execute basic functionality tests (UAT-2.x)
3. Execute transaction tests (UAT-3.x)
4. Execute error handling tests (UAT-4.x)
5. Execute performance tests (UAT-5.x)
6. Execute compatibility tests (UAT-6.x)

### Test Data Management
- Use separate test database for all tests
- Refresh test data before each test run
- Clean up test data after test completion
- Avoid modifying production data

### Performance Benchmarks
- Connection establishment: < 1 second
- Simple query execution: < 100ms
- Complex query execution: < 1 second
- Large dataset processing: < 30 seconds for 10K records
- Memory usage: < 50MB for typical operations

### Reporting Requirements
- Test case execution status (Pass/Fail)
- Execution time for performance tests
- Error messages and stack traces for failures
- Environment details (PHP version, extensions, database version)
- Performance metrics and resource usage

This comprehensive test suite ensures the ksf_ModulesDAO library meets all functional and non-functional requirements while providing reliable database abstraction across multiple platforms.</content>
<parameter name="filePath">c:\Users\prote\Documents\software-devel\FA_ProductAttributes\ksf-modules-dao\Project Docs\User_Acceptance_Test_Cases.md