# Rake Framework Architecture

## Overview

Rake Framework là một PHP framework được thiết kế để xử lý data migration và web crawling. Framework này được xây dựng trên nguyên tắc SOLID và sử dụng các design patterns phổ biến.

## Core Principles

### 1. Single Responsibility Principle (SRP)
- Mỗi class chỉ có một trách nhiệm duy nhất
- Separation of concerns rõ ràng

### 2. Dependency Injection (DI)
- Sử dụng Illuminate Container
- Loose coupling giữa các components

### 3. Facade Pattern
- Cung cấp interface đơn giản cho complex subsystems
- Static access methods

### 4. Lazy Loading
- Components chỉ được khởi tạo khi cần thiết
- Tối ưu performance

## Architecture Layers

```
┌─────────────────────────────────────┐
│           Application Layer         │
│  (WordPress Plugin, CLI Commands)  │
├─────────────────────────────────────┤
│           Service Layer             │
│  (MigrationService, LoggerService) │
├─────────────────────────────────────┤
│           Manager Layer             │
│  (DatabaseDriverManager, LoggerManager) │
├─────────────────────────────────────┤
│           Adapter Layer             │
│  (WordPressDatabaseAdapter, etc.)  │
├─────────────────────────────────────┤
│           Driver Layer              │
│  (WordPressDatabaseDriver, etc.)   │
├─────────────────────────────────────┤
│           Core Layer                │
│  (Rake Container, Bootstrapper)    │
└─────────────────────────────────────┘
```

## Core Components

### 1. Rake Container
```php
class Rake extends Container
{
    // Singleton pattern
    // Service registration
    // Configuration management
}
```

**Responsibilities:**
- Dependency injection container
- Service lifecycle management
- Configuration storage
- Framework bootstrap

### 2. Bootstrapper
```php
class Bootstrapper
{
    // Service registration
    // Helper functions
    // Framework initialization
}
```

**Responsibilities:**
- Register core services
- Initialize framework components
- Register global helper functions
- Handle framework bootstrap

### 3. Manager Classes
```php
class DatabaseDriverManager
class LoggerManager
class MigrationManager
```

**Responsibilities:**
- Manage specific domain components
- Provide high-level APIs
- Handle component lifecycle
- Coordinate between adapters

### 4. Adapter Classes
```php
class WordPressDatabaseAdapter
class HttpClientAdapter
```

**Responsibilities:**
- Bridge between framework and external systems
- Handle system-specific implementations
- Provide consistent interfaces
- Error handling and fallbacks

### 5. Driver Classes
```php
class WordPressDatabaseDriver
class CurlHttpDriver
```

**Responsibilities:**
- Low-level system interactions
- Raw data operations
- Performance optimization
- Error handling

## Service Registration Flow

```
1. Rake::getInstance()
   ↓
2. Bootstrapper::register()
   ↓
3. Register Core Services
   ├── DatabaseDriverManager
   ├── LoggerManager
   └── Rake::class
   ↓
4. Register Helper Functions
   ├── rake()
   ├── rake_logger()
   └── rake_database()
   ↓
5. Framework Ready
```

## Dependency Injection Container

### Service Registration
```php
// Singleton registration
$app->singleton(LoggerManager::class, function() {
    return LoggerManager::getInstance();
});

// Interface binding
$app->bind(DatabaseAdapterInterface::class, WordPressDatabaseAdapter::class);
```

### Service Resolution
```php
// Automatic resolution
$logger = $app->make(LoggerManager::class);

// Interface resolution
$adapter = $app->make(DatabaseAdapterInterface::class);
```

## Facade System

### Logger Facade
```php
class Logger extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LoggerManager::class;
    }
}
```

### Usage
```php
Logger::info('Message');
Logger::error('Error message');
Logger::debug('Debug info');
```

## Configuration Management

### Configuration Structure
```php
$config = [
    'database' => [
        'driver' => 'wordpress',
        'prefix' => $wpdb->prefix,
    ],
    'logging' => [
        'level' => 'info',
        'file' => 'crawlflow.log',
    ],
    'crawling' => [
        'max_concurrent' => 5,
        'request_delay' => 1,
    ],
];
```

### Configuration Access
```php
// Set configuration
$rake->setConfig($config);

// Get configuration
$dbConfig = $rake->getConfig('database');
$logLevel = $rake->getConfig('logging.level');
```

## Event System (Future)

### Event Registration
```php
$rake->listen('migration.completed', function($data) {
    Logger::info('Migration completed', $data);
});
```

### Event Dispatching
```php
$rake->dispatch('migration.started', [
    'table' => 'users',
    'version' => '1.0.0'
]);
```

## Error Handling Strategy

### 1. Graceful Degradation
- Fallback mechanisms for critical services
- Error logging without breaking execution
- User-friendly error messages

### 2. Error Levels
```php
// Critical - Stop execution
Logger::critical('Database connection failed');

// Error - Log and continue
Logger::error('Migration failed', ['table' => 'users']);

// Warning - Potential issues
Logger::warning('Deprecated method used');

// Info - Normal operations
Logger::info('Migration completed');
```

### 3. Error Recovery
```php
try {
    $migration->run();
} catch (DatabaseException $e) {
    Logger::error('Database error', ['exception' => $e->getMessage()]);
    // Fallback to alternative method
} catch (Exception $e) {
    Logger::critical('Unexpected error', ['exception' => $e->getMessage()]);
    throw $e;
}
```

## Performance Considerations

### 1. Lazy Loading
- Services only initialized when needed
- Memory efficient for large applications
- Faster startup time

### 2. Caching Strategy
- Service instance caching
- Configuration caching
- Query result caching

### 3. Resource Management
- Connection pooling for databases
- Memory usage monitoring
- Garbage collection optimization

## Security Considerations

### 1. Input Validation
- All external inputs validated
- SQL injection prevention
- XSS protection

### 2. Access Control
- Service access restrictions
- Configuration protection
- Logging security

### 3. Data Protection
- Sensitive data encryption
- Secure logging practices
- Audit trail maintenance

## Testing Strategy

### 1. Unit Testing
- Individual component testing
- Mock dependencies
- Isolated test environments

### 2. Integration Testing
- Service interaction testing
- Database integration testing
- Framework integration testing

### 3. End-to-End Testing
- Complete workflow testing
- Real-world scenario testing
- Performance testing

## Extension Points

### 1. Custom Adapters
```php
class CustomDatabaseAdapter implements DatabaseAdapterInterface
{
    // Implementation
}
```

### 2. Custom Managers
```php
class CustomManager extends BaseManager
{
    // Custom functionality
}
```

### 3. Custom Facades
```php
class CustomFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CustomManager::class;
    }
}
```

## Migration Path

### Phase 1: Core Framework
- [x] Container implementation
- [x] Service registration
- [x] Basic facades
- [x] Configuration management

### Phase 2: Database Layer
- [x] Database driver manager
- [x] WordPress adapter
- [x] Migration system
- [ ] Query builder

### Phase 3: Logging System
- [x] Logger manager
- [x] Monolog integration
- [x] Facade implementation
- [ ] Log rotation

### Phase 4: HTTP Layer
- [ ] HTTP client manager
- [ ] Request/Response handling
- [ ] Middleware system
- [ ] Rate limiting

### Phase 5: Crawling System
- [ ] Crawler manager
- [ ] URL management
- [ ] Content parsing
- [ ] Data extraction

### Phase 6: Queue System
- [ ] Queue manager
- [ ] Job processing
- [ ] Background tasks
- [ ] Job scheduling

## Conclusion

Rake Framework được thiết kế để:
- Dễ dàng mở rộng và maintain
- Performance cao với lazy loading
- Security tốt với proper error handling
- Testing friendly với dependency injection
- WordPress integration seamless
- Modern PHP practices với SOLID principles