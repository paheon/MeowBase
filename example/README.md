# MeowBase Examples

This directory contains comprehensive examples demonstrating the functionality of various MeowBase classes and tools.

## Overview

MeowBase is a lightweight PHP framework that provides various functionalities including configuration management, caching, database operations, logging, and more. These examples are designed to help you understand and use each component effectively.

## Example Files

### Core Classes

#### 1. `classbase-example.php` - ClassBase Trait
Demonstrates the fundamental ClassBase trait that provides:
- Property access control (denyRead, denyWrite)
- Mass getter and setter operations
- Variable mapping
- Array element access by path
- Event system (register, trigger, unregister)
- Exception handling with caller context
- `isTrue()` method for boolean conversion
- `__debugInfo()` method for debugging

**Key Features:**
- Protected property access via magic methods
- Mass property operations
- Property protection mechanisms
- Event registration and triggering
- Exception handling configuration with custom exception classes
- Debug information support with `_getBaseDebugInfo()` for sub-classes

**Documentation:** See `classbase-readme.md` for detailed usage guide

#### 2. `config-example.php` - Configuration Management
Shows how to use the Config class for:
- Hierarchical configuration structure
- Path-based configuration access
- Reading and writing configuration values
- Loading configuration from files
- Custom configuration values

**Key Features:**
- Multi-level path access (e.g., `cache/adapterList/files/path`)
- Dynamic configuration updates
- Configuration file loading
- Default and custom settings

#### 3. `log-profiler-example.php` - Logging and Performance Profiling
Demonstrates:
- **SysLog**: System logging with different log levels
- **Profiler**: Performance measurement and reporting

**Key Features:**
- Multiple log levels (DEBUG, INFO, WARNING, ERROR)
- Stack tracking for debugging
- Log level management
- Performance profiling with groups
- Performance reports with timing information

### Cache and Database

#### 4. `cache-example.php` - Cache Management
Shows cache operations including:
- Cache hit/miss checking
- Storing and retrieving cached data
- Cache tags and invalidation
- Multiple cache adapters (files, memcached)

**Key Features:**
- Cache item management
- Tag-based cache invalidation
- Expiration time management
- Cache pool operations

#### 5. `cachedb-example.php` - Cached Database Operations
Demonstrates CacheDB which extends Medoo with:
- Cached SELECT queries
- Automatic cache invalidation on data changes
- Query logging
- Performance optimization

**Key Features:**
- Cached database queries
- Automatic cache clearing on INSERT/UPDATE/DELETE
- Query result caching
- SQL logging

### Data Structures

#### 6. `dtree-example.php` - Tree Structure Management
Shows the DTree class for managing hierarchical data:
- Creating tree structures
- Node operations (add, delete, copy, move, rename)
- Path-based node finding with `.` and `..` support
- Tree iteration (DFS and BFS)
- Serialization with HMAC verification

**Key Features:**
- Hierarchical data organization
- Node manipulation
- Path-based navigation with relative path support (`.`, `..`)
- Tree serialization/deserialization
- Iterator support with depth-first search (DFS) and breadth-first search (BFS)
- Debug information support

**Documentation:** See `dtree-readme.md` for detailed usage guide

#### 7. `csvdb-example.php` - CSV Database Operations
Demonstrates CsvDB for CSV file database operations:
- CRUD operations
- Advanced search with operators (=, >, <, >=, <=, ~, !~, IN, BETWEEN)
- AND/OR logical operators
- Queue operations (batch add/update/delete)
- Iterator support

**Key Features:**
- CSV file as database
- Complex query operations
- Batch operations via queue
- Iterator interface
- Sorting and filtering

### Utility Classes

#### 8. `file-url-mime-example.php` - File, URL, and MIME Utilities
Shows utility classes:
- **File**: File path operations, temporary file creation
- **Url**: URL building and manipulation
- **Mime**: MIME type detection and icon mapping

**Key Features:**
- File path building with substitution
- Temporary file management
- URL generation and modification
- MIME type detection
- Icon mapping

#### 9. `mailer-example.php` - Email Sending
Demonstrates the Mailer class for sending emails:
- Email validation
- Address management (To, CC, BCC, Reply-To)
- HTML and plain text emails
- Attachments (file, string, embedded images)
- Async email sending (spool mode)
- SMTP configuration

**Key Features:**
- Multiple sending modes (mail, SMTP, sendmail, qmail)
- Async email processing
- Attachment handling
- Email validation with DNS checking
- Logging support

### User Management

#### 10. `user-csv-example.php` - User Management (CSV Storage)
Shows comprehensive user management with CSV storage including:
- **UserCSV**: User CRUD operations, password hashing, status management
- **UserGroupCSV**: User group management, group membership
- **UserPermCSV**: User and group permission management

**Key Features:**
- User CRUD operations
- Password hashing with Password class
- User status management
- User groups and membership
- User and group permissions
- Permission checking

#### 11. `userdb-example.php` - User Management (Database Storage)
Demonstrates user management with database storage including:
- **UserDB**: User CRUD operations with database tables
- **UserGroupDB**: Database-backed user group management
- **UserPermDB**: Database-backed permission management

**Key Features:**
- Database table creation
- User CRUD operations
- Group management with relational tables
- Permission management with database storage
- Integration with CacheDB

#### 12. `usermanager-example.php` - Complete User Management System
Demonstrates the integrated UserManager for both CSV and database storage:
- User login/logout with both storage types
- Session management
- Permission checking (user + group permissions)
- Password management
- Authentication workflows

**Key Features:**
- Complete authentication system
- Support for both CSV and database storage
- Permission inheritance from groups
- Session lifecycle management
- Password encryption
- Force login option
- Integrated permission checking

### Autoload Configuration

#### 13. `autoload-example.php` - Simple Dynamic Autoload Usage
Demonstrates basic dynamic autoload configuration:
- Adding new namespace mappings
- Using relative paths
- Loading autoload rules from config

#### 14. `autoload-usage.php` - Advanced Autoload Configuration
Shows advanced autoload features:
- PSR-4 and PSR-0 mapping
- Multiple paths for same namespace
- Custom autoload functions
- Conditional autoload based on environment
- Class map management

## Running Examples

### Prerequisites

1. Install dependencies:
```bash
composer install
```

2. Configure your environment:
   - Copy `etc/config-example.php` to `etc/config.php`
   - Update configuration as needed
   - For database examples, configure database settings in `config.php`

### Running Individual Examples

Each example can be run independently:

```bash
# CLI mode
php sample/classbase-example.php

# Or via web server
http://your-domain/sample/classbase-example.php
```

### Running All Examples

The original `test.php` file contains comprehensive tests for all features. You can run it to see all functionality:

```bash
php test.php
```

## Example Structure

Each example file follows this structure:

1. **Header**: File description and metadata
2. **Setup**: Require autoloader and determine output format (CLI/Web)
3. **Examples**: Multiple numbered examples demonstrating different features
4. **Output**: Clear, formatted output showing results

## Common Patterns

### Output Format Detection
All examples detect whether they're running in CLI or Web mode:

```php
$isWeb = !Paheon\MeowBase\Tools\PHP::isCLI();
$br = $isWeb ? "<br>\n" : "\n";
```

### Error Handling
Examples demonstrate proper error handling:

```php
if ($result !== false) {
    echo "Success: ".$result.$br;
} else {
    echo "Error: ".$object->lastError.$br;
}
```

### Debug Information
Examples show how to use `__debugInfo()`:

```php
$debugInfo = $object->__debugInfo();
print_r($debugInfo);
```

## Best Practices

1. **Configuration**: Always use Config class for application settings
2. **Logging**: Use SysLog for all logging operations
3. **Caching**: Use Cache for frequently accessed data
4. **Error Handling**: Check `lastError` property after operations
5. **Performance**: Use Profiler to measure performance bottlenecks
6. **Security**: Use Password class for password hashing
7. **Data Storage**: Choose between CSV (CsvDB) and SQL (CacheDB) based on needs

## Example Files Summary

| Category | Example File | Description |
|----------|-------------|-------------|
| **Core** | classbase-example.php | ClassBase trait with property control, events, mass operations |
| | config-example.php | Configuration management with path-based access |
| | log-profiler-example.php | System logging and performance profiling |
| **Cache & Database** | cache-example.php | Cache operations with tags and invalidation |
| | cachedb-example.php | Cached database queries with automatic invalidation |
| **Data Structures** | dtree-example.php | Tree structure management with navigation |
| | csvdb-example.php | CSV file database with advanced search |
| **Utilities** | file-url-mime-example.php | File, URL, and MIME type utilities |
| | mailer-example.php | Email sending with attachments and async mode |
| **User Management** | user-csv-example.php | User management with CSV storage (User/Group/Perm) |
| | userdb-example.php | User management with database storage |
| | usermanager-example.php | Integrated user authentication system |
| **Autoload** | autoload-example.php | Basic dynamic autoload configuration |
| | autoload-usage.php | Advanced autoload features |

## Additional Resources

- **Main README**: See `README.md` in the root directory for framework overview
- **Test File**: See `test.php` in the root directory for comprehensive tests
- **Autoload Guide**: See `autoload-readme.md` for dynamic autoload configuration
- **DTree Guide**: See `dtree-readme.md` for DTree and DTreeIterator usage
- **ClassBase Guide**: See `classbase-readme.md` for ClassBase trait usage
- **ChangeLog**: See `ChangeLog` for version history and updates

## Notes

- Examples are designed to be educational and may need modification for production use
- Some examples require specific configuration (e.g., database, SMTP settings)
- File paths and URLs in examples may need adjustment for your environment
- Email examples require proper SMTP configuration to actually send emails

## Contributing

When adding new examples:
1. Follow the existing example structure
2. Include comprehensive comments
3. Show both success and error cases
4. Test in both CLI and Web modes
5. Update this README with the new example

## License

MIT License - See LICENSE file for details
