# TÀI LIỆU THIẾT KẾ KỸ THUẬT RAKE CORE FRAMEWORK
**Phiên bản:** 1.0
**Ngày tạo:** 2025
**Tác giả:** Development Team

---

## MỤC LỤC

1. [Tổng quan Rake Core](#1-tổng-quan-rake-core)
2. [Kiến trúc Framework](#2-kiến-trúc-framework)
3. [Container (Dependency Injection)](#3-container-dependency-injection)
4. [Kernel System](#4-kernel-system)
5. [Bootstrapper System](#5-bootstrapper-system)
6. [Database Migration](#6-database-migration)
7. [Logging System](#7-logging-system)
8. [Facade Pattern](#8-facade-pattern)
9. [Development Guidelines](#9-development-guidelines)

---

## 1. TỔNG QUAN RAKE CORE

### 1.1 Mục tiêu
Rake Core là framework cốt lõi, độc lập với platform, cung cấp:
- Dependency Injection Container
- Kernel System cho application lifecycle
- Database Migration System
- Logging System với Monolog
- Facade Pattern implementation

### 1.2 Kiến trúc tổng thể
```
┌─────────────────────────────────────────────────────────────┐
│                    RAKE CORE FRAMEWORK                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   CONTAINER     │  │    KERNEL       │  │ BOOTSTRAPPER│ │
│  │                 │  │                 │  │             │ │
│  │ • Service Reg   │  │ • App Lifecycle │  │ • Service   │ │
│  │ • DI Container  │  │ • Bootstrappers │  │   Loading   │ │
│  │ • Resolution    │  │ • Configuration │  │ • Init      │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
│                                                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   MIGRATION     │  │    LOGGING      │  │   FACADE    │ │
│  │                 │  │                 │  │             │ │
│  │ • Schema Def    │  │ • Monolog Int   │  │ • Static    │ │
│  │ • SQL Generator │  │ • File Handler  │  │   Access    │ │
│  │ • Migration     │  │ • Rotating Logs │  │ • Logger    │ │
│  │   Tracking      │  │ • Console Out   │  │   Facade    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. KIẾN TRÚC FRAMEWORK

### 2.1 Core Components
```
rake/
├── src/
│   ├── Container/             # Dependency Injection
│   │   ├── Rake.php          # Main Container
│   │   └── ContainerInterface.php
│   ├── Kernel/                # Application Kernels
│   │   ├── AbstractKernel.php
│   │   ├── MigrationKernel.php
│   │   └── ConsoleKernel.php
│   ├── Bootstrapper/          # Service Bootstrappers
│   │   ├── Bootstrapper.php
│   │   └── CoreBootstrapper.php
│   ├── Facade/                # Facade Pattern
│   │   ├── Facade.php
│   │   └── Logger.php
│   ├── Migration/             # Database Migration
│   │   ├── MigrationManager.php
│   │   ├── SchemaGenerator.php
│   │   └── MigrationInterface.php
│   ├── Logging/               # Logging System
│   │   ├── LoggerManager.php
│   │   └── LoggerInterface.php
│   └── Database/              # Database Abstraction
│       ├── DatabaseAdapterInterface.php
│       └── DatabaseDriverInterface.php
├── schema_definitions/        # Database Schemas
│   ├── rake_configs.php
│   ├── rake_migrations.php
│   └── crawlflow_projects.php
└── composer.json
```

### 2.2 Package Dependencies
```json
{
    "name": "crawlflow/rake-core",
    "require": {
        "php": ">=8.1",
        "monolog/monolog": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Rake\\": "src/"
        }
    }
}
```

---

## 3. CONTAINER (DEPENDENCY INJECTION)

### 3.1 Container Implementation
```php
class Rake
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

    public function make(string $abstract)
    {
        if ($this->has($abstract)) {
            return $this->resolve($abstract);
        }

        throw new Exception("Service not found: {$abstract}");
    }

    public function has(string $abstract): bool
    {
        return isset($this->services[$abstract]) || isset($this->singletons[$abstract]);
    }

    public function instance(string $abstract, $instance): void
    {
        $this->resolved[$abstract] = $instance;
    }

    private function resolve(string $abstract)
    {
        // Check if already resolved
        if (isset($this->resolved[$abstract])) {
            return $this->resolved[$abstract];
        }

        $concrete = $this->getConcrete($abstract);
        $instance = $this->build($concrete);

        // Store singleton instances
        if (isset($this->singletons[$abstract])) {
            $this->resolved[$abstract] = $instance;
        }

        return $instance;
    }

    private function getConcrete(string $abstract)
    {
        if (isset($this->services[$abstract])) {
            return $this->services[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        throw new Exception("Service not found: {$abstract}");
    }

    private function build($concrete)
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (is_string($concrete)) {
            return new $concrete();
        }

        return $concrete;
    }
}
```

### 3.2 Service Registration Patterns
```php
// Singleton registration
$container->singleton(LoggerManager::class, LoggerManager::class);

// Interface binding
$container->bind(LoggerInterface::class, LoggerManager::class);

// Factory pattern
$container->bind(DatabaseAdapterInterface::class, function($container) {
    $config = $container->make('config');
    return new WordPressDatabaseAdapter($config);
});

// Instance registration
$container->instance('config', [
    'database' => [
        'prefix' => 'wp_',
        'charset' => 'utf8mb4',
    ],
    'logging' => [
        'path' => '/logs/',
        'level' => 'info',
    ],
]);
```

---

## 4. KERNEL SYSTEM

### 4.1 Abstract Kernel
```php
abstract class AbstractKernel
{
    protected Rake $app;
    protected array $config = [];

    public function __construct(Rake $app)
    {
        $this->app = $app;
        $this->config = $this->getConfig();
    }

    public function bootstrap(): void
    {
        $this->registerServices();
        $this->bootServices();
    }

    private function registerServices(): void
    {
        foreach ($this->getBootstrappers() as $bootstrapperClass) {
            $bootstrapper = new $bootstrapperClass();
            $bootstrapper->register($this->app);
        }
    }

    private function bootServices(): void
    {
        foreach ($this->getBootstrappers() as $bootstrapperClass) {
            $bootstrapper = new $bootstrapperClass();
            $bootstrapper->boot($this->app);
        }
    }

    abstract public function getBootstrappers(): array;
    abstract protected function getConfig(): array;
}
```

### 4.2 Migration Kernel
```php
class MigrationKernel extends AbstractKernel
{
    public function getBootstrappers(): array
    {
        return [
            CoreBootstrapper::class,
        ];
    }

    protected function getConfig(): array
    {
        return [
            'migration_schema_path' => __DIR__ . '/../schema_definitions/',
            'migration_table' => 'rake_migrations',
        ];
    }

    public function runMigrations(): void
    {
        $migrationManager = new MigrationManager($this->app);
        $migrationManager->runMigrations();
    }

    public function checkMigrationStatus(): array
    {
        $migrationManager = new MigrationManager($this->app);
        return $migrationManager->getMigrationStatus();
    }

    public function rollbackMigrations(): void
    {
        $migrationManager = new MigrationManager($this->app);
        $migrationManager->rollbackMigrations();
    }
}
```

### 4.3 Console Kernel
```php
class ConsoleKernel extends AbstractKernel
{
    public function getBootstrappers(): array
    {
        return [
            CoreBootstrapper::class,
        ];
    }

    protected function getConfig(): array
    {
        return [
            'console' => [
                'commands' => [
                    'migrate' => MigrateCommand::class,
                    'rollback' => RollbackCommand::class,
                    'status' => StatusCommand::class,
                ],
            ],
        ];
    }

    public function handle(array $argv): int
    {
        $command = $argv[1] ?? 'help';
        $commandClass = $this->config['console']['commands'][$command] ?? null;

        if (!$commandClass) {
            echo "Unknown command: {$command}\n";
            return 1;
        }

        $commandInstance = new $commandClass($this->app);
        return $commandInstance->handle(array_slice($argv, 2));
    }
}
```

---

## 5. BOOTSTRAPPER SYSTEM

### 5.1 Bootstrapper Interface
```php
abstract class Bootstrapper
{
    abstract public function register(Rake $container): void;
    abstract public function boot(Rake $container): void;
}
```

### 5.2 Core Bootstrapper
```php
class CoreBootstrapper extends Bootstrapper
{
    public function register(Rake $container): void
    {
        // Register core services
        $container->singleton(LoggerManager::class, LoggerManager::class);
        $container->singleton(DatabaseDriverManager::class, DatabaseDriverManager::class);

        // Register configuration
        $container->singleton('config', function() {
            return [
                'database' => [
                    'prefix' => 'wp_',
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_unicode_ci',
                ],
                'logging' => [
                    'path' => '/logs/',
                    'level' => 'info',
                    'max_files' => 30,
                ],
            ];
        });
    }

    public function boot(Rake $container): void
    {
        // Initialize logger manager
        $loggerManager = $container->make(LoggerManager::class);
        Logger::setManager($loggerManager);

        // Register database driver manager
        $driverManager = $container->make(DatabaseDriverManager::class);
        $driverManager->registerDriver('default', new DefaultDatabaseDriver());
        $driverManager->setDefaultDriver('default');
    }
}
```

---

## 6. DATABASE MIGRATION

### 6.1 Migration Manager
```php
class MigrationManager
{
    private Rake $app;
    private DatabaseAdapterInterface $adapter;
    private LoggerInterface $logger;

    public function __construct(Rake $app)
    {
        $this->app = $app;
        $this->adapter = $app->make(DatabaseAdapterInterface::class);
        $this->logger = $app->make(LoggerManager::class)->getLogger();
    }

    public function runMigrations(): void
    {
        $schemas = $this->getSchemaDefinitions();

        foreach ($schemas as $schema) {
            $this->createTable($schema);
        }
    }

    private function createTable(array $schema): void
    {
        $tableName = $schema['table'];

        if ($this->tableExists($tableName)) {
            $this->logger->info("Table already exists: {$tableName}");
            return;
        }

        $sql = $this->generateCreateTableSQL($schema);
        $this->adapter->query($sql);

        $this->logger->info("Table created: {$tableName}");
        $this->recordMigration($tableName);
    }

    private function generateCreateTableSQL(array $schema): string
    {
        $generator = new SchemaGenerator();
        return $generator->generateCreateTableSQL($schema);
    }

    private function tableExists(string $tableName): bool
    {
        $sql = "SHOW TABLES LIKE '{$tableName}'";
        $result = $this->adapter->getRow($sql);
        return $result !== null;
    }

    private function recordMigration(string $tableName): void
    {
        $data = [
            'migration_name' => $tableName,
            'executed_at' => date('Y-m-d H:i:s'),
            'status' => 'success',
        ];

        $this->adapter->insert('rake_migrations', $data);
    }

    public function getMigrationStatus(): array
    {
        $sql = "SELECT * FROM {$this->adapter->getPrefix()}rake_migrations ORDER BY executed_at DESC";
        return $this->adapter->getResults($sql);
    }

    private function getSchemaDefinitions(): array
    {
        $schemaPath = $this->app->make('config')['migration_schema_path'] ?? __DIR__ . '/../schema_definitions/';
        $schemas = [];

        foreach (glob($schemaPath . '*.php') as $file) {
            $schemas[] = require $file;
        }

        return $schemas;
    }
}
```

### 6.2 Schema Generator
```php
class SchemaGenerator
{
    public function generateCreateTableSQL(array $schema): string
    {
        $tableName = $schema['table'];
        $columns = $this->generateColumnsSQL($schema['columns']);
        $indexes = $this->generateIndexesSQL($schema['indexes'] ?? []);

        return "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            {$columns}
            {$indexes}
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }

    private function generateColumnsSQL(array $columns): string
    {
        $columnDefinitions = [];

        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = $this->generateColumnSQL($name, $definition);
        }

        return implode(",\n            ", $columnDefinitions);
    }

    private function generateColumnSQL(string $name, array $definition): string
    {
        $sql = "`{$name}` " . strtoupper($definition['type']);

        if (isset($definition['length'])) {
            $sql .= "({$definition['length']})";
        }

        if (isset($definition['unsigned']) && $definition['unsigned']) {
            $sql .= " UNSIGNED";
        }

        if (isset($definition['not_null']) && $definition['not_null']) {
            $sql .= " NOT NULL";
        } else {
            $sql .= " NULL";
        }

        if (isset($definition['auto_increment']) && $definition['auto_increment']) {
            $sql .= " AUTO_INCREMENT";
        }

        if (isset($definition['default'])) {
            $sql .= " DEFAULT {$definition['default']}";
        }

        if (isset($definition['on_update'])) {
            $sql .= " ON UPDATE {$definition['on_update']}";
        }

        return $sql;
    }

    private function generateIndexesSQL(array $indexes): string
    {
        if (empty($indexes)) {
            return '';
        }

        $indexDefinitions = [];

        foreach ($indexes as $name => $definition) {
            $columns = implode(', ', array_map(fn($col) => "`{$col}`", $definition['columns']));
            $unique = isset($definition['unique']) && $definition['unique'] ? 'UNIQUE ' : '';
            $indexDefinitions[] = "{$unique}KEY `{$name}` ({$columns})";
        }

        return ",\n            " . implode(",\n            ", $indexDefinitions);
    }
}
```

### 6.3 Schema Definitions
```php
// schema_definitions/rake_configs.php
return [
    'table' => 'rake_configs',
    'columns' => [
        'id' => [
            'type' => 'bigint',
            'unsigned' => true,
            'auto_increment' => true,
        ],
        'config_key' => [
            'type' => 'varchar',
            'length' => 255,
            'not_null' => true,
        ],
        'config_value' => [
            'type' => 'longtext',
            'nullable' => true,
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
            'on_update' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'primary_key' => ['id'],
    'indexes' => [
        'idx_config_key' => ['columns' => ['config_key'], 'unique' => true],
    ],
];
```

---

## 7. LOGGING SYSTEM

### 7.1 Logger Manager
```php
class LoggerManager
{
    private ?LoggerInterface $logger = null;
    private array $config = [];

    public function __construct()
    {
        $this->config = [
            'log_path' => '/logs/',
            'log_level' => 'info',
            'max_files' => 30,
            'file_size' => 10 * 1024 * 1024, // 10MB
        ];
    }

    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->initializeLogger();
        }

        return $this->logger;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    private function initializeLogger(): void
    {
        if (!is_dir($this->config['log_path'])) {
            mkdir($this->config['log_path'], 0755, true);
        }

        $logger = new Logger('rake');

        // File handler
        $fileHandler = new RotatingFileHandler(
            $this->config['log_path'] . 'rake.log',
            $this->config['max_files'],
            $this->getLogLevel()
        );

        // Console handler (for development)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $consoleHandler = new StreamHandler('php://stdout', $this->getLogLevel());
            $logger->pushHandler($consoleHandler);
        }

        $logger->pushHandler($fileHandler);

        $this->logger = $logger;
    }

    private function getLogLevel(): int
    {
        $levels = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
        ];

        return $levels[$this->config['log_level']] ?? Logger::INFO;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $logger = $this->getLogger();
        $logger->log($level, $message, $context);
    }
}
```

### 7.2 Logger Interface
```php
interface LoggerInterface
{
    public function emergency(string $message, array $context = []): void;
    public function alert(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function notice(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function log(string $level, string $message, array $context = []): void;
}
```

---

## 8. FACADE PATTERN

### 8.1 Facade Base Class
```php
abstract class Facade
{
    protected static Rake $container;

    public static function setContainer(Rake $container): void
    {
        self::$container = $container;
    }

    protected static function getFacadeAccessor(): string
    {
        throw new Exception('Facade must implement getFacadeAccessor method.');
    }

    protected static function resolveFacadeInstance(string $name)
    {
        if (!isset(self::$container)) {
            throw new Exception('Container not set for facade.');
        }

        return self::$container->make($name);
    }

    public static function __callStatic(string $method, array $arguments)
    {
        $instance = static::resolveFacadeInstance(static::getFacadeAccessor());

        return $instance->$method(...$arguments);
    }
}
```

### 8.2 Logger Facade
```php
class Logger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoggerManager::class;
    }

    // Convenience methods
    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        static::log('debug', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        static::log('critical', $message, $context);
    }
}
```

---

## 9. DEVELOPMENT GUIDELINES

### 9.1 Coding Standards
- **PSR-12 Compliance**: Strict adherence to PSR-12 coding standards
- **Type Declarations**: Use strict types and type hints
- **Documentation**: PHPDoc required for all public methods
- **Testing**: Unit tests required for all classes

### 9.2 File Organization
```
src/
├── Container/          # Dependency Injection
├── Kernel/             # Application Kernels
├── Bootstrapper/       # Service Bootstrappers
├── Facade/             # Facade Pattern
├── Migration/          # Database Migration
├── Logging/            # Logging System
└── Database/           # Database Abstraction
```

### 9.3 Naming Conventions
- **Classes**: PascalCase (e.g., `MigrationManager`)
- **Interfaces**: PascalCase with Interface suffix (e.g., `LoggerInterface`)
- **Methods**: camelCase (e.g., `runMigrations()`)
- **Properties**: camelCase (e.g., `$migrationManager`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DEFAULT_TIMEOUT`)

### 9.4 Testing Guidelines
```php
class MigrationManagerTest extends TestCase
{
    private MigrationManager $manager;
    private Rake $container;

    protected function setUp(): void
    {
        $this->container = new Rake();
        $this->manager = new MigrationManager($this->container);
    }

    public function testRunMigrations(): void
    {
        // Arrange
        $mockAdapter = $this->createMock(DatabaseAdapterInterface::class);
        $this->container->singleton(DatabaseAdapterInterface::class, $mockAdapter);

        // Act
        $this->manager->runMigrations();

        // Assert
        $this->assertTrue($this->migrationWasExecuted());
    }
}
```

---

## KẾT LUẬN

Rake Core Framework cung cấp nền tảng vững chắc cho các ứng dụng với:

### Điểm nổi bật:
1. **Dependency Injection Container**: Quản lý dependencies hiệu quả
2. **Kernel System**: Lifecycle management cho applications
3. **Database Migration**: Hệ thống migration tự động
4. **Logging System**: Monolog integration với facade
5. **Platform Independent**: Có thể sử dụng với bất kỳ platform nào

### Sử dụng:
```php
// Initialize framework
$app = new Rake();
$kernel = new MigrationKernel($app);
$kernel->bootstrap();

// Run migrations
$kernel->runMigrations();

// Use logger
Logger::info('Application started');
```

---

**Tài liệu này sẽ được cập nhật thường xuyên khi có thay đổi trong framework.**