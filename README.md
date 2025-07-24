# RAKE CORE FRAMEWORK
**Phiên bản:** 3.0
**Ngày tạo:** 2025
**Tác giả:** Development Team

---

## 📋 MỤC LỤC

1. [Tổng quan Rake Core](#tổng-quan-rake-core)
2. [Triết lý thiết kế](#triết-lý-thiết-kế)
3. [Kiến trúc Framework](#kiến-trúc-framework)
4. [Các thành phần cốt lõi](#các-thành-phần-cốt-lõi)
5. [Tài liệu kỹ thuật](#tài-liệu-kỹ-thuật)
6. [Development Guidelines](#development-guidelines)

---

## 🎯 TỔNG QUAN RAKE CORE

### Mục tiêu dự án
Rake Core là **framework tổng quát** cho việc xây dựng ứng dụng, không phụ thuộc vào platform cụ thể nào. Framework này cung cấp:

- **Platform Agnostic**: Không phụ thuộc vào WordPress, Laravel, hay platform cụ thể nào
- **Modular Architecture**: Kiến trúc module hóa với các thành phần độc lập
- **Flow-based Design**: Mọi thứ phải thấy được luồng xử lý và có thể visualize
- **Dependency Injection**: Container pattern cho quản lý dependencies
- **Event-driven**: Hệ thống event-driven với logging toàn diện
- **Extensible**: Dễ dàng mở rộng và tùy chỉnh

### Vai trò trong hệ sinh thái
```
┌─────────────────────────────────────────────────────────────┐
│                    RAKE ECOSYSTEM                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────┐ │
│  │   RAKE CORE     │    │   RAKE WORDPRESS│    │ RAKE    │ │
│  │   FRAMEWORK     │◄───┤    ADAPTER      │    │ OTHER   │ │
│  │                 │    │                 │    │ ADAPTERS│ │
│  │ • Container     │    │ • WP Database   │    │ • Laravel│ │
│  │ • Kernel        │    │ • WP Hooks      │    │ • Symfony│ │
│  │ • Bootstrapper  │    │ • WP Admin      │    │ • Custom │ │
│  │ • Facade        │    │ • WP Security   │    │ • Platform│ │
│  │ • Migration     │    │ • WP Cache      │    │ • Specific│ │
│  │ • Logging       │    │ • WP Config     │    │ • Adapters│ │
│  └─────────────────┘    └─────────────────┘    └─────────┘ │
│                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────┐ │
│  │   CRAWFLOW      │    │   CRAWFLOW CLI  │    │ CRAWFLOW│ │
│  │   PLUGIN        │    │    TOOL         │    │  CORE   │ │
│  │                 │    │                 │    │         │ │
│  │ • WordPress UI  │    │ • Command Line  │    │ • Engine│ │
│  │ • Visual Editor │    │ • Batch Process │    │ • API   │ │
│  │ • Admin Panel   │    │ • Scripts       │    │ • Core  │ │
│  └─────────────────┘    └─────────────────┘    └─────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Công nghệ sử dụng
- **PHP 8.1+**: Ngôn ngữ chính
- **PSR-4**: Autoloading standard
- **PSR-3**: Logger interface
- **PSR-11**: Container interface
- **Composer**: Dependency management
- **Monolog**: Logging library

---

## 🎨 TRIẾT LÝ THIẾT KẾ

### Platform Agnostic Philosophy
Rake Core được thiết kế theo triết lý **"Platform Agnostic"** - không phụ thuộc vào platform cụ thể nào:

```
┌─────────────────────────────────────────────────────────────┐
│                    RAKE CORE FRAMEWORK                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   CONTAINER     │  │     KERNEL      │  │ BOOTSTRAPPER│ │
│  │                 │  │                 │  │             │ │
│  │ • DI Container  │  │ • App Lifecycle │  │ • Service   │ │
│  │ • Service Locator│  │ • Request Handle│  │ • Provider  │ │
│  │ • Dependency Mgmt│  │ • Response Gen  │  │ • Bootstrap │ │
│  │ • Singleton Mgmt│  │ • Error Handle  │  │ • Register  │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │    FACADE       │  │   MIGRATION     │  │   LOGGING   │ │
│  │                 │  │                 │  │             │ │
│  │ • Static Access │  │ • Schema Mgmt   │  │ • PSR-3     │ │
│  │ • Service Proxy │  │ • Version Track │  │ • Monolog   │ │
│  │ • Easy API      │  │ • Auto Migrate  │  │ • Daily Logs│ │
│  │ • Clean Interface│  │ • Rollback      │  │ • CLI Output│ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Design Patterns Applied
- **Dependency Injection**: Container pattern cho quản lý dependencies
- **Service Locator**: Truy cập services thông qua container
- **Factory Pattern**: Service factory, component factory
- **Builder Pattern**: Configuration builder, service builder
- **Chain of Responsibility**: Middleware chain, processor chain
- **Strategy Pattern**: Service strategy, handler strategy
- **Adapter Pattern**: Platform adapter, service adapter
- **Command Pattern**: Console commands, queue commands
- **State Pattern**: Application state, service state
- **Decorator Pattern**: Service decorator, middleware decorator
- **Observer/Event Bus**: Event-driven architecture

### Flow-based Architecture
Rake Core được xây dựng theo triết lý **"Flow-based Architecture"** - mọi thứ phải thấy được luồng xử lý:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   REQUEST       │───▶│   KERNEL        │───▶│   RESPONSE      │
│                 │    │                 │    │                 │
│ • Input Data    │    │ • Process       │    │ • Output Data   │
│ • Parameters    │    │ • Transform     │    │ • Status Code   │
│ • Headers       │    │ • Validate      │    │ • Headers       │
│ • Context       │    │ • Execute       │    │ • Body          │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   VALIDATION    │    │   EXECUTION     │    │   LOGGING       │ │
│                 │    │                 │    │                 │ │
│ • Input Check   │    │ • Service Call  │    │ • Request Log   │ │
│ • Schema Valid  │    │ • Business Logic│    │ • Response Log  │ │
│ • Security Check│    │ • Data Process  │    │ • Error Log     │ │
│ • Permission    │    │ • State Change  │    │ • Performance   │ │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

---

## 🏗️ KIẾN TRÚC FRAMEWORK

### Package Structure
```
rake/
├── src/
│   ├── Container/             # Dependency Injection
│   │   ├── Container.php      # Main container
│   │   ├── ContainerInterface.php
│   │   └── ServiceProvider.php
│   ├── Kernel/                # Application Kernels
│   │   ├── AbstractKernel.php
│   │   ├── ConsoleKernel.php
│   │   ├── HttpKernel.php
│   │   └── KernelInterface.php
│   ├── Bootstrapper/          # Service Bootstrappers
│   │   ├── BootstrapperInterface.php
│   │   ├── CoreBootstrapper.php
│   │   └── ServiceBootstrapper.php
│   ├── Facade/                # Facade Pattern
│   │   ├── Facade.php
│   │   ├── Logger.php
│   │   └── Database.php
│   ├── Migration/             # Database Migration
│   │   ├── MigrationManager.php
│   │   ├── SchemaGenerator.php
│   │   └── MigrationInterface.php
│   ├── Logging/               # Logging System
│   │   ├── LoggerManager.php
│   │   ├── LogInterface.php
│   │   └── LogFormatter.php
│   └── Database/              # Database Abstraction
│       ├── DatabaseAdapterInterface.php
│       ├── DatabaseConfig.php
│       └── DatabaseDriverManager.php
├── schema_definitions/        # Database Schemas
├── composer.json
└── README.md
```

### Core Components

#### 1. **Container (Dependency Injection)**
```php
// Main container for dependency management
class Container
{
    private array $services = [];
    private array $singletons = [];
    private array $resolved = [];

    public function bind(string $abstract, $concrete): void;
    public function singleton(string $abstract, $concrete): void;
    public function make(string $abstract): mixed;
    public function has(string $abstract): bool;
    public function resolve($concrete): mixed;
}
```

#### 2. **Kernel (Application Lifecycle)**
```php
// Abstract kernel for application lifecycle
abstract class AbstractKernel
{
    protected Container $container;
    protected array $bootstrappers = [];

    abstract public function bootstrap(): void;
    abstract public function handle($request): mixed;
    abstract public function terminate($request, $response): void;
}
```

#### 3. **Bootstrapper (Service Registration)**
```php
// Service bootstrapper interface
interface BootstrapperInterface
{
    public function bootstrap(Container $container): void;
    public function register(Container $container): void;
    public function boot(Container $container): void;
}
```

#### 4. **Facade (Static Access)**
```php
// Base facade class
abstract class Facade
{
    protected static Container $container;

    public static function setContainer(Container $container): void;
    public static function getFacadeAccessor(): string;
    public static function __callStatic(string $method, array $arguments): mixed;
}
```

#### 5. **Migration (Database Management)**
```php
// Migration manager
class MigrationManager
{
    private DatabaseAdapterInterface $adapter;
    private SchemaGenerator $generator;

    public function runMigrations(): array;
    public function rollbackMigrations(): array;
    public function getMigrationStatus(): array;
    public function createMigration(string $name): string;
}
```

#### 6. **Logging (PSR-3 Implementation)**
```php
// Logger manager
class LoggerManager
{
    private LoggerInterface $logger;
    private array $config;

    public function log($level, string $message, array $context = []): void;
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}
```

---

## 🔧 CÁC THÀNH PHẦN CỐT LÕI

### 1. **Dependency Injection Container**

#### Container Interface
```php
interface ContainerInterface
{
    public function bind(string $abstract, $concrete): void;
    public function singleton(string $abstract, $concrete): void;
    public function make(string $abstract): mixed;
    public function has(string $abstract): bool;
    public function resolve($concrete): mixed;
    public function call($callback, array $parameters = []): mixed;
}
```

#### Service Registration
```php
// Bind interface to implementation
$container->bind(DatabaseInterface::class, MySQLDatabase::class);

// Bind singleton
$container->singleton(LoggerInterface::class, Logger::class);

// Bind with closure
$container->bind('config', function() {
    return new Config(['debug' => true]);
});
```

#### Service Resolution
```php
// Resolve service
$logger = $container->make(LoggerInterface::class);

// Resolve with parameters
$service = $container->make(Service::class, ['param' => 'value']);

// Call method with dependency injection
$result = $container->call([$service, 'method'], ['param' => 'value']);
```

### 2. **Kernel System**

#### Abstract Kernel
```php
abstract class AbstractKernel
{
    protected Container $container;
    protected array $bootstrappers = [];
    protected array $middleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->bootstrap();
    }

    abstract public function bootstrap(): void;
    abstract public function handle($request): mixed;
    abstract public function terminate($request, $response): void;

    protected function runBootstrappers(): void
    {
        foreach ($this->bootstrappers as $bootstrapper) {
            $this->container->make($bootstrapper)->bootstrap($this->container);
        }
    }
}
```

#### Console Kernel
```php
class ConsoleKernel extends AbstractKernel
{
    protected array $commands = [];

    public function handle($input, $output = null): int
    {
        // Handle console command
        $command = $this->resolveCommand($input);
        return $command->execute($input, $output);
    }

    protected function bootstrap(): void
    {
        $this->runBootstrappers();
        $this->registerCommands();
    }
}
```

#### HTTP Kernel
```php
class HttpKernel extends AbstractKernel
{
    protected array $middleware = [];

    public function handle($request): Response
    {
        // Handle HTTP request
        $response = $this->sendRequestThroughRouter($request);
        return $this->prepareResponse($response);
    }

    protected function bootstrap(): void
    {
        $this->runBootstrappers();
        $this->registerMiddleware();
    }
}
```

### 3. **Bootstrapper System**

#### Bootstrapper Interface
```php
interface BootstrapperInterface
{
    public function bootstrap(Container $container): void;
    public function register(Container $container): void;
    public function boot(Container $container): void;
}
```

#### Core Bootstrapper
```php
class CoreBootstrapper implements BootstrapperInterface
{
    public function register(Container $container): void
    {
        // Register core services
        $container->singleton(ContainerInterface::class, Container::class);
        $container->singleton(LoggerInterface::class, Logger::class);
        $container->singleton(DatabaseAdapterInterface::class, DatabaseAdapter::class);
    }

    public function boot(Container $container): void
    {
        // Boot core services
        $logger = $container->make(LoggerInterface::class);
        $logger->info('Core services booted');
    }

    public function bootstrap(Container $container): void
    {
        $this->register($container);
        $this->boot($container);
    }
}
```

### 4. **Facade System**

#### Base Facade
```php
abstract class Facade
{
    protected static Container $container;

    public static function setContainer(Container $container): void
    {
        static::$container = $container;
    }

    public static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        $instance = static::$container->make(static::getFacadeAccessor());
        return $instance->$method(...$arguments);
    }
}
```

#### Logger Facade
```php
class Logger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoggerInterface::class;
    }
}

// Usage
Logger::info('Application started');
Logger::error('An error occurred', ['context' => 'data']);
```

### 5. **Migration System**

#### Migration Manager
```php
class MigrationManager
{
    private DatabaseAdapterInterface $adapter;
    private SchemaGenerator $generator;
    private array $migrations = [];

    public function runMigrations(): array
    {
        $results = [];

        foreach ($this->migrations as $migration) {
            try {
                $this->runMigration($migration);
                $results[] = ['migration' => $migration, 'status' => 'success'];
            } catch (Exception $e) {
                $results[] = ['migration' => $migration, 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function rollbackMigrations(): array
    {
        // Rollback migrations
        return [];
    }

    public function getMigrationStatus(): array
    {
        // Get migration status
        return [];
    }
}
```

#### Schema Generator
```php
class SchemaGenerator
{
    private DatabaseAdapterInterface $adapter;

    public function createTable(string $table, array $schema): bool
    {
        $sql = $this->generateCreateTableSQL($table, $schema);
        return $this->adapter->query($sql);
    }

    public function dropTable(string $table): bool
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        return $this->adapter->query($sql);
    }

    private function generateCreateTableSQL(string $table, array $schema): string
    {
        // Generate CREATE TABLE SQL
        return "CREATE TABLE {$table} (...)";
    }
}
```

### 6. **Logging System**

#### Logger Manager
```php
class LoggerManager implements LoggerInterface
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeLogger();
    }

    public function log($level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
```

---

## 📚 TÀI LIỆU KỸ THUẬT

### Tài liệu chi tiết
📖 [`docs/technical-documentation.md`](docs/technical-documentation.md)

**Nội dung:**
- Container (Dependency Injection)
- Kernel System
- Bootstrapper System
- Database Migration
- Logging System
- Facade Pattern
- Development Guidelines

### Code Examples

#### Container Usage
```php
// Create container
$container = new Container();

// Register services
$container->singleton(LoggerInterface::class, Logger::class);
$container->bind(DatabaseInterface::class, MySQLDatabase::class);

// Resolve services
$logger = $container->make(LoggerInterface::class);
$database = $container->make(DatabaseInterface::class);

// Use services
$logger->info('Service resolved successfully');
```

#### Kernel Usage
```php
// Create kernel
$container = new Container();
$kernel = new ConsoleKernel($container);

// Handle command
$input = new ArgvInput();
$output = new ConsoleOutput();
$exitCode = $kernel->handle($input, $output);
```

#### Facade Usage
```php
// Set container
Facade::setContainer($container);

// Use facades
Logger::info('Application started');
Database::query('SELECT * FROM users');
```

#### Migration Usage
```php
// Create migration manager
$adapter = new DatabaseAdapter();
$manager = new MigrationManager($adapter);

// Run migrations
$results = $manager->runMigrations();

// Check status
$status = $manager->getMigrationStatus();
```

---

## 🛠️ DEVELOPMENT GUIDELINES

### Coding Standards

#### PSR-12 Compliance
```php
<?php

declare(strict_types=1);

namespace Rake\Container;

use Rake\Contracts\ContainerInterface;

class Container implements ContainerInterface
{
    private array $services = [];
    private array $singletons = [];
    private array $resolved = [];

    public function bind(string $abstract, $concrete): void
    {
        $this->services[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void
    {
        $this->singletons[$abstract] = $concrete;
    }

    public function make(string $abstract): mixed
    {
        if ($this->has($abstract)) {
            return $this->resolve($this->services[$abstract]);
        }

        throw new RuntimeException("Service {$abstract} not found.");
    }

    public function has(string $abstract): bool
    {
        return isset($this->services[$abstract]);
    }
}
```

#### Interface Segregation
```php
// Define specific interfaces
interface LoggerInterface
{
    public function log($level, string $message, array $context = []): void;
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
}

interface DatabaseAdapterInterface
{
    public function query(string $sql): bool;
    public function getResults(string $sql): array;
    public function getRow(string $sql): ?array;
    public function getVar(string $sql): mixed;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, array $where): int;
    public function delete(string $table, array $where): int;
    public function getPrefix(): string;
    public function escape(string $value): string;
}
```

### Testing Guidelines

#### Unit Testing
```php
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindService(): void
    {
        // Arrange
        $this->container->bind('test', TestService::class);

        // Act
        $service = $this->container->make('test');

        // Assert
        $this->assertInstanceOf(TestService::class, $service);
    }

    public function testSingletonService(): void
    {
        // Arrange
        $this->container->singleton('singleton', SingletonService::class);

        // Act
        $service1 = $this->container->make('singleton');
        $service2 = $this->container->make('singleton');

        // Assert
        $this->assertSame($service1, $service2);
    }
}
```

#### Integration Testing
```php
class KernelIntegrationTest extends TestCase
{
    public function testConsoleKernel(): void
    {
        // Arrange
        $container = new Container();
        $kernel = new ConsoleKernel($container);

        // Act
        $input = new ArgvInput(['command' => 'test']);
        $output = new ConsoleOutput();
        $exitCode = $kernel->handle($input, $output);

        // Assert
        $this->assertEquals(0, $exitCode);
    }
}
```

### Error Handling
```php
class RakeException extends Exception
{
    public function __construct(string $message, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Rake error: {$message}", $code, $previous);
    }
}

// Usage
try {
    $container = new Container();
    $service = $container->make('non-existent-service');
} catch (RakeException $e) {
    Logger::error('Service resolution failed: ' . $e->getMessage());
}
```

---

## 🔧 CONFIGURATION

### Basic Configuration
```php
// Container configuration
$container = new Container();

// Register core services
$container->singleton(LoggerInterface::class, Logger::class);
$container->bind(DatabaseAdapterInterface::class, DatabaseAdapter::class);

// Register configuration
$container->bind('config', function() {
    return [
        'debug' => true,
        'log_level' => 'info',
        'database' => [
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => ''
        ]
    ];
});
```

### Advanced Configuration
```php
// Service provider pattern
class AppServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Register services
        $container->singleton(LoggerInterface::class, Logger::class);
        $container->bind(DatabaseAdapterInterface::class, DatabaseAdapter::class);
    }

    public function boot(Container $container): void
    {
        // Boot services
        $logger = $container->make(LoggerInterface::class);
        $logger->info('Application services booted');
    }
}

// Register service provider
$container = new Container();
$container->register(new AppServiceProvider());
```

---

## 🚨 TROUBLESHOOTING

### Common Issues

#### Error: `Service not found`
**Solution:**
```php
// Ensure service is registered
$container->bind('service', ServiceClass::class);

// Check if service exists
if ($container->has('service')) {
    $service = $container->make('service');
}
```

#### Error: `Circular dependency detected`
**Solution:**
```php
// Use lazy loading
$container->bind('service', function() {
    return new ServiceClass();
});

// Or use interface
$container->bind(ServiceInterface::class, ServiceClass::class);
```

#### Error: `Facade not initialized`
**Solution:**
```php
// Set container for facades
Facade::setContainer($container);

// Then use facades
Logger::info('Application started');
```

### Debug Mode
```php
// Enable debug mode
$container->bind('debug', true);

// Check container state
$services = $container->getServices();
$singletons = $container->getSingletons();
```

---

## 📊 PERFORMANCE

### Optimizations
- **Lazy loading**: Services only instantiated when needed
- **Singleton pattern**: Single instance for expensive services
- **Caching**: Resolved services cached
- **Memory management**: Efficient memory usage

### Best Practices
```php
// Use singletons for expensive services
$container->singleton(LoggerInterface::class, Logger::class);
$container->singleton(DatabaseAdapterInterface::class, DatabaseAdapter::class);

// Use interfaces for flexibility
$container->bind(ServiceInterface::class, ServiceClass::class);

// Use lazy loading for complex dependencies
$container->bind('complex-service', function() {
    return new ComplexService(new Dependency1(), new Dependency2());
});
```

---

## 🎯 KẾT LUẬN

Rake Core Framework cung cấp nền tảng tổng quát cho việc xây dựng ứng dụng với:

### Điểm nổi bật:
1. **Platform Agnostic**: Không phụ thuộc vào platform cụ thể nào
2. **Modular Architecture**: Kiến trúc module hóa với các thành phần độc lập
3. **Flow-based Design**: Mọi thứ phải thấy được luồng xử lý
4. **Dependency Injection**: Container pattern cho quản lý dependencies
5. **Event-driven**: Hệ thống event-driven với logging toàn diện
6. **Extensible**: Dễ dàng mở rộng và tùy chỉnh

### Triết lý:
**"Platform Agnostic"** - framework tổng quát, không phụ thuộc vào platform cụ thể nào, chỉ tập trung vào lý thuyết, tư tưởng và triết lý thiết kế.

### Tương lai:
- Mở rộng adapter system
- Thêm nhiều platform adapters
- Performance optimization
- Advanced features
- Community contributions

---

**Tài liệu này sẽ được cập nhật thường xuyên khi có thay đổi trong framework.**