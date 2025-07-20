# Design Patterns in Rake Framework

## Overview

Rake Framework sử dụng các design patterns để tạo ra một kiến trúc clean, maintainable và extensible.

## 1. Singleton Pattern

### Purpose
Đảm bảo chỉ có một instance của class trong toàn bộ application.

### Implementation
```php
class Rake extends Container
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
```

### Usage
```php
$rake = Rake::getInstance();
```

### Benefits
- Centralized state management
- Resource efficiency
- Global access point

## 2. Dependency Injection Pattern

### Purpose
Giảm coupling giữa các components và tăng testability.

### Implementation
```php
class MigrationService
{
    private $adapter;
    private $logger;

    public function __construct(
        DatabaseAdapterInterface $adapter,
        LoggerInterface $logger
    ) {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }
}
```

### Container Registration
```php
$app->singleton(DatabaseAdapterInterface::class, function() {
    return new WordPressDatabaseAdapter();
});

$app->singleton(LoggerInterface::class, function() {
    return new LoggerManager();
});
```

### Benefits
- Loose coupling
- Easy testing
- Flexible configuration

## 3. Facade Pattern

### Purpose
Cung cấp interface đơn giản cho complex subsystems.

### Implementation
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
Logger::error('Error');
```

### Benefits
- Simple interface
- Hide complexity
- Static access

## 4. Adapter Pattern

### Purpose
Cho phép incompatible interfaces làm việc cùng nhau.

### Implementation
```php
interface DatabaseAdapterInterface
{
    public function query(string $sql): array;
    public function execute(string $sql): bool;
}

class WordPressDatabaseAdapter implements DatabaseAdapterInterface
{
    private $driver;

    public function __construct(WordPressDatabaseDriver $driver)
    {
        $this->driver = $driver;
    }

    public function query(string $sql): array
    {
        return $this->driver->query($sql);
    }
}
```

### Benefits
- System integration
- Interface consistency
- Legacy system support

## 5. Manager Pattern

### Purpose
Quản lý lifecycle và coordination của related objects.

### Implementation
```php
class DatabaseDriverManager
{
    private $drivers = [];

    public function register(string $name, DatabaseDriverInterface $driver)
    {
        $this->drivers[$name] = $driver;
    }

    public function get(string $name): DatabaseDriverInterface
    {
        return $this->drivers[$name] ?? throw new Exception("Driver not found");
    }
}
```

### Benefits
- Centralized management
- Lifecycle control
- Resource coordination

## 6. Factory Pattern

### Purpose
Tạo objects mà không expose creation logic.

### Implementation
```php
class DatabaseDriverFactory
{
    public static function create(string $type): DatabaseDriverInterface
    {
        switch ($type) {
            case 'wordpress':
                return new WordPressDatabaseDriver();
            case 'mysql':
                return new MySQLDatabaseDriver();
            default:
                throw new Exception("Unknown driver type");
        }
    }
}
```

### Benefits
- Encapsulated creation
- Flexible object creation
- Easy testing

## 7. Observer Pattern

### Purpose
Notify multiple objects về state changes.

### Implementation
```php
interface Observer
{
    public function update(string $event, array $data): void;
}

class LoggerObserver implements Observer
{
    public function update(string $event, array $data): void
    {
        Logger::info("Event: {$event}", $data);
    }
}

class EventDispatcher
{
    private $observers = [];

    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(string $event, array $data): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }
}
```

### Benefits
- Loose coupling
- Event-driven architecture
- Extensible notifications

## 8. Strategy Pattern

### Purpose
Define family of algorithms và make them interchangeable.

### Implementation
```php
interface MigrationStrategy
{
    public function migrate(array $schema): bool;
}

class CreateTableStrategy implements MigrationStrategy
{
    public function migrate(array $schema): bool
    {
        // Implementation for creating tables
    }
}

class AlterTableStrategy implements MigrationStrategy
{
    public function migrate(array $schema): bool
    {
        // Implementation for altering tables
    }
}

class MigrationManager
{
    private $strategies = [];

    public function addStrategy(string $name, MigrationStrategy $strategy)
    {
        $this->strategies[$name] = $strategy;
    }

    public function migrate(string $strategy, array $schema): bool
    {
        return $this->strategies[$strategy]->migrate($schema);
    }
}
```

### Benefits
- Algorithm flexibility
- Runtime strategy selection
- Easy testing

## 9. Template Method Pattern

### Purpose
Define skeleton của algorithm và let subclasses override steps.

### Implementation
```php
abstract class BaseMigration
{
    final public function run(): bool
    {
        $this->validateSchema();
        $this->prepareMigration();
        $result = $this->executeMigration();
        $this->cleanup();
        return $result;
    }

    abstract protected function validateSchema(): void;
    abstract protected function prepareMigration(): void;
    abstract protected function executeMigration(): bool;
    abstract protected function cleanup(): void;
}

class CreateTableMigration extends BaseMigration
{
    protected function validateSchema(): void
    {
        // Validate table schema
    }

    protected function prepareMigration(): void
    {
        // Prepare for table creation
    }

    protected function executeMigration(): bool
    {
        // Create table
        return true;
    }

    protected function cleanup(): void
    {
        // Cleanup after creation
    }
}
```

### Benefits
- Code reuse
- Consistent algorithm structure
- Flexible implementation

## 10. Builder Pattern

### Purpose
Construct complex objects step by step.

### Implementation
```php
class QueryBuilder
{
    private $query = '';
    private $params = [];

    public function select(string $columns): self
    {
        $this->query .= "SELECT {$columns}";
        return $this;
    }

    public function from(string $table): self
    {
        $this->query .= " FROM {$table}";
        return $this;
    }

    public function where(string $condition, array $params = []): self
    {
        $this->query .= " WHERE {$condition}";
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function build(): array
    {
        return [
            'sql' => $this->query,
            'params' => $this->params
        ];
    }
}

// Usage
$query = (new QueryBuilder())
    ->select('*')
    ->from('users')
    ->where('status = ?', ['active'])
    ->build();
```

### Benefits
- Complex object construction
- Fluent interface
- Immutable objects

## Pattern Integration

### Service Container Integration
```php
// Register patterns in container
$app->singleton(DatabaseDriverManager::class, function() {
    $manager = new DatabaseDriverManager();
    $manager->register('wordpress', new WordPressDatabaseDriver());
    return $manager;
});

$app->singleton(EventDispatcher::class, function() {
    $dispatcher = new EventDispatcher();
    $dispatcher->attach(new LoggerObserver());
    return $dispatcher;
});
```

### Facade Integration
```php
class Database extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DatabaseDriverManager::class;
    }
}

// Usage
Database::query('SELECT * FROM users');
```

## Best Practices

### 1. Pattern Selection
- Choose patterns based on problem requirements
- Don't over-engineer simple solutions
- Consider performance implications

### 2. Implementation Guidelines
- Keep implementations simple
- Document pattern usage
- Test pattern implementations

### 3. Integration Guidelines
- Use container for pattern registration
- Provide facade access where appropriate
- Maintain loose coupling

### 4. Testing Patterns
- Mock dependencies for testing
- Test pattern interactions
- Verify pattern behavior

## Conclusion

Design patterns trong Rake Framework:
- Tăng code maintainability
- Giảm coupling giữa components
- Cung cấp flexible architecture
- Enable easy testing
- Support extensibility