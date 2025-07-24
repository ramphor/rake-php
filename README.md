# CRAWFLOW & RAKE FRAMEWORK ECOSYSTEM
**Phiên bản:** 3.0
**Ngày tạo:** 2025
**Tác giả:** Development Team

---

## 📋 MỤC LỤC

1. [Tổng quan hệ thống](#tổng-quan-hệ-thống)
2. [Kiến trúc 3 Packages](#kiến-trúc-3-packages)
3. [Triết lý thiết kế](#triết-lý-thiết-kế)
4. [Flow hoạt động](#flow-hoạt-động)
5. [Tài liệu kỹ thuật](#tài-liệu-kỹ-thuật)
6. [Hướng dẫn sử dụng](#hướng-dẫn-sử-dụng)
7. [Development Guidelines](#development-guidelines)

---

## 🎯 TỔNG QUAN HỆ THỐNG

### Mục tiêu dự án
CrawlFlow là hệ thống crawling và xử lý dữ liệu mạnh mẽ được xây dựng trên Rake Framework 2.0, cung cấp:

- **Modular Architecture**: Kiến trúc module hóa với 3 packages độc lập
- **Flow-based Design**: Mọi thứ phải thấy được luồng xử lý và có thể visualize
- **WordPress Integration**: Tích hợp hoàn chỉnh với WordPress
- **Database Migration**: Hệ thống migration tự động
- **Visual Composer**: React-based visual flow composer cho database schemas
- **Event-driven**: Hệ thống event-driven với logging toàn diện

### Công nghệ sử dụng
- **PHP 8.1+**: Ngôn ngữ chính
- **WordPress**: Platform hosting
- **React + XYFlow**: Visual flow composer
- **Monolog**: Logging library
- **Composer**: Dependency management
- **PSR-4**: Autoloading standard
- **PSR-3**: Logger interface

---

## 🏗️ KIẾN TRÚC 3 PACKAGES

### 1. **Rake Core Framework** (`rake/`)
Framework cốt lõi, độc lập với platform:

```
rake/
├── src/
│   ├── Container/             # Dependency Injection
│   ├── Kernel/                # Application Kernels
│   ├── Bootstrapper/          # Service Bootstrappers
│   ├── Facade/                # Facade Pattern
│   ├── Migration/             # Database Migration
│   ├── Logging/               # Logging System
│   └── Database/              # Database Abstraction
├── schema_definitions/        # Database Schemas
└── composer.json
```

**Chức năng chính:**
- Dependency Injection Container
- Kernel System cho application lifecycle
- Database Migration System
- Logging System với Monolog
- Facade Pattern implementation

### 2. **Rake WordPress Adapter** (`rake-wordpress-adapter/`)
Adapter để tích hợp với WordPress:

```
rake-wordpress-adapter/
├── src/
│   ├── Database/              # WP Database Adapter
│   ├── Hooks/                 # WP Hooks Integration
│   ├── Admin/                 # WP Admin Integration
│   ├── Security/              # WP Security Layer
│   └── Cache/                 # WP Cache Integration
└── composer.json
```

**Chức năng chính:**
- WordPress Database Integration
- WordPress Hooks Integration
- WordPress Admin Integration
- Security Layer
- Cache Integration

### 3. **CrawlFlow Plugin** (`wp-crawlflow/`)
Plugin WordPress sử dụng Rake Framework:

```
wp-crawlflow/
├── src/
│   ├── Admin/                 # Admin Controllers
│   ├── Kernel/                # Plugin Kernels
│   ├── Bootstrapper/          # Plugin Bootstrappers
│   ├── ServiceProvider/       # Service Providers
│   └── Assets/                # Frontend Assets
├── assets/
│   ├── css/                   # Stylesheets
│   └── js/                    # JavaScript
└── composer.json
```

**Chức năng chính:**
- Dashboard quản lý projects
- Visual flow composer với React + XYFlow
- Project management
- Migration integration
- Logging và analytics

---

## 🎨 TRIẾT LÝ THIẾT KẾ

### Flow-based Architecture
CrawlFlow được xây dựng theo triết lý **"Flow-based Architecture"** - mọi thứ phải thấy được luồng xử lý và có thể visualize:

```
┌─────────────────────────────────────────────────────────────┐
│                    CRAWFLOW ECOSYSTEM                      │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │   RAKE CORE     │  │ RAKE WORDPRESS  │  │  CRAWFLOW   │ │
│  │   FRAMEWORK     │  │    ADAPTER      │  │   PLUGIN    │ │
│  │                 │  │                 │  │             │ │
│  │ • Container     │  │ • WP Database   │  │ • Dashboard │ │
│  │ • Kernel        │  │ • WP Hooks      │  │ • Projects  │ │
│  │ • Bootstrapper  │  │ • WP Admin      │  │ • Migration │ │
│  │ • Facade        │  │ • WP Security   │  │ • Logging   │ │
│  │ • Migration     │  │ • WP Cache      │  │ • Analytics │ │
│  │ • Logging       │  │ • WP Config     │  │             │ │
│  └─────────────────┘  └─────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Design Patterns Applied
- **Dependency Injection**: Container pattern cho quản lý dependencies
- **Factory Pattern**: Tooth Factory, HTTP Client Manager, Database Driver Manager
- **Builder Pattern**: Feed Item Builder
- **Chain of Responsibility**: Processor Chain
- **Strategy Pattern**: Parser, Reception, Client, Driver selection
- **Adapter Pattern**: HTTP Client, Database Driver
- **Command Pattern**: Queue/Worker system
- **State Pattern**: Feed Item, Resource, Tooth states
- **Decorator Pattern**: Processor/Feed Item wrapping
- **Observer/Event Bus**: Event-driven architecture

---

## 🔄 FLOW HOẠT ĐỘNG

### Plugin Activation Flow
```
┌─────────────────┐
│ Plugin Activated│
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Initialize Rake │
│    Container    │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Bootstrap Core  │
│   Services      │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Register WP     │
│   Adapters      │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Run Migrations  │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Register Admin  │
│    Hooks        │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Plugin Ready    │
└─────────────────┘
```

### Request Handling Flow
```
┌─────────────────┐
│ WordPress Admin │
│    Request      │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Detect Screen   │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Load Screen     │
│     Data        │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Render Template │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Return Response │
└─────────────────┘
```

### Migration Flow
```
┌─────────────────┐
│ Migration       │
│   Request       │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Load Schema     │
│  Definitions    │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Generate SQL    │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Execute SQL     │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Update Migration│
│    History      │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│ Return Result   │
└─────────────────┘
```

---

## 📚 TÀI LIỆU KỸ THUẬT

### Tài liệu chi tiết cho từng package:

#### 1. **Rake Core Framework**
📖 [`rake/docs/technical-documentation.md`](docs/technical-documentation.md)

**Nội dung:**
- Container (Dependency Injection)
- Kernel System
- Bootstrapper System
- Database Migration
- Logging System
- Facade Pattern
- Development Guidelines

#### 2. **Rake WordPress Adapter**
📖 [`rake-wordpress-adapter/docs/technical-documentation.md`](../rake-wordpress-adapter/docs/technical-documentation.md)

**Nội dung:**
- WordPress Database Integration
- WordPress Hooks Integration
- WordPress Admin Integration
- Security Layer
- Cache Integration
- Development Guidelines

#### 3. **CrawlFlow Plugin**
📖 [`wp-crawlflow/docs/technical-documentation.md`](../wp-crawlflow/docs/technical-documentation.md)

**Nội dung:**
- Dashboard System
- Project Management
- Migration Integration
- Logging System
- Frontend Assets (React + XYFlow)
- Development Guidelines

---

## 🚀 HƯỚNG DẪN SỬ DỤNG

### Cài đặt và khởi tạo

#### 1. Clone repositories
```bash
git clone https://github.com/crawlflow/rake.git
git clone https://github.com/crawlflow/rake-wordpress-adapter.git
git clone https://github.com/crawlflow/wp-crawlflow.git
```

#### 2. Cài đặt dependencies
```bash
# Rake Core
cd rake
composer install

# Rake WordPress Adapter
cd ../rake-wordpress-adapter
composer install

# CrawlFlow Plugin
cd ../wp-crawlflow
composer install
```

#### 3. Kích hoạt plugin trong WordPress
```php
// Trong WordPress admin
// Plugins > CrawlFlow > Activate
```

### Sử dụng cơ bản

#### Khởi tạo Rake Container
```php
use Rake\Rake;

$app = new Rake();
$kernel = new CrawlFlowDashboardKernel($app);
$kernel->bootstrap();
```

#### Chạy migrations
```php
$migrationKernel = new CrawlFlowMigrationKernel($app);
$migrationKernel->runMigrations();
```

#### Sử dụng Logger
```php
use Rake\Facade\Logger;

Logger::info('Application started');
Logger::error('An error occurred');
```

#### Tạo project mới
```php
use CrawlFlow\Admin\ProjectService;

$projectService = new ProjectService();
$projectId = $projectService->createProject([
    'name' => 'My Project',
    'description' => 'Project description',
    'config' => json_encode($config),
    'status' => 'active'
]);
```

### Visual Flow Composer

Truy cập vào WordPress Admin > CrawlFlow > Projects > "Add New Project" để sử dụng visual flow composer:

- **React-based**: Sử dụng React 18
- **XYFlow**: Visual flow editor
- **Schema Definitions**: Pre-defined database schemas
- **Real-time Preview**: Xem trước data structure
- **Auto-save**: Tự động lưu khi thay đổi

---

## 🛠️ DEVELOPMENT GUIDELINES

### Coding Standards

#### PSR-12 Compliance
```php
<?php

declare(strict_types=1);

namespace CrawlFlow\Admin;

use Rake\Rake;
use Rake\Facade\Logger;

class ProjectService
{
    private DatabaseAdapterInterface $db;

    public function __construct()
    {
        $this->db = new WordPressDatabaseAdapter();
    }

    public function createProject(array $data): int
    {
        Logger::info('Creating project', $data);

        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        return $this->db->insert('crawlflow_projects', $data);
    }
}
```

#### WordPress Integration
```php
// Always use WordPress functions with backslash prefix
$result = \wp_verify_nonce($nonce, $action);

// Use WordPress security functions
$sanitized = \sanitize_text_field($input);

// Check capabilities before actions
if (\current_user_can('manage_options')) {
    // Perform admin action
}
```

### Testing Guidelines

#### Unit Testing
```php
class ProjectServiceTest extends TestCase
{
    private ProjectService $service;
    private Rake $container;

    protected function setUp(): void
    {
        $this->container = new Rake();
        $this->service = new ProjectService();
    }

    public function testCreateProject(): void
    {
        // Arrange
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
        ];

        // Act
        $projectId = $this->service->createProject($projectData);

        // Assert
        $this->assertGreaterThan(0, $projectId);
    }
}
```

#### Integration Testing
```php
class WordPressIntegrationTest extends TestCase
{
    public function testDatabaseAdapter(): void
    {
        // Arrange
        $adapter = new WordPressDatabaseAdapter();

        // Act
        $result = $adapter->query('SELECT 1');

        // Assert
        $this->assertTrue($result);
    }
}
```

### Error Handling
```php
class CrawlFlowException extends Exception
{
    public function __construct(string $message, array $context = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("CrawlFlow error: {$message}", $code, $previous);
    }
}

// Usage
try {
    $projectService = new ProjectService();
    $result = $projectService->createProject($data);
} catch (CrawlFlowException $e) {
    Logger::error('Project creation failed: ' . $e->getMessage());
}
```

---

## 📊 DATABASE SCHEMA

### Core Tables

#### rake_configs
```sql
CREATE TABLE `wp_rake_configs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `config_key` varchar(255) NOT NULL,
    `config_value` longtext,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### rake_migrations
```sql
CREATE TABLE `wp_rake_migrations` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `migration_name` varchar(255) NOT NULL,
    `executed_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `status` enum('success','failed') DEFAULT 'success',
    `error_message` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### crawlflow_projects
```sql
CREATE TABLE `wp_crawlflow_projects` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `config` longtext,
    `status` enum('active','inactive','archived') DEFAULT 'active',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### crawlflow_logs
```sql
CREATE TABLE `wp_crawlflow_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `level` enum('debug','info','warning','error','critical') NOT NULL,
    `message` text NOT NULL,
    `context` longtext,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔗 PACKAGE DEPENDENCIES

### Dependency Graph
```
┌─────────────────┐    depends on    ┌─────────────────┐
│   CRAWFLOW      │ ────────────────▶ │ RAKE WORDPRESS  │
│   PLUGIN        │                  │    ADAPTER      │
└─────────────────┘                  └─────────────────┘
                                              │
                                              │ depends on
                                              ▼
                                    ┌─────────────────┐
                                    │   RAKE CORE     │
                                    │   FRAMEWORK     │
                                    └─────────────────┘
```

### Composer Dependencies

#### CrawlFlow Plugin
```json
{
    "name": "crawlflow/wp-crawlflow",
    "require": {
        "php": ">=8.1",
        "crawlflow/rake-wordpress-adapter": "^1.0",
        "monolog/monolog": "^3.0"
    }
}
```

#### Rake WordPress Adapter
```json
{
    "name": "crawlflow/rake-wordpress-adapter",
    "require": {
        "php": ">=8.1",
        "crawlflow/rake-core": "^1.0"
    }
}
```

#### Rake Core Framework
```json
{
    "name": "crawlflow/rake-core",
    "require": {
        "php": ">=8.1",
        "monolog/monolog": "^3.0"
    }
}
```

---

## 🎯 KẾT LUẬN

CrawlFlow & Rake Framework Ecosystem cung cấp giải pháp toàn diện cho:

### Điểm nổi bật:
1. **Modular Architecture**: 3 packages độc lập với trách nhiệm rõ ràng
2. **Flow-based Design**: Mọi thứ có thể visualize và track
3. **WordPress Integration**: Tích hợp hoàn chỉnh với WordPress
4. **Visual Composer**: React-based visual flow editor
5. **Event-driven**: Hệ thống event-driven với logging toàn diện
6. **Cross-platform**: Rake Core độc lập với platform

### Triết lý:
**"Flow-based Architecture"** - mọi thứ phải thấy được luồng xử lý và có thể visualize, phù hợp với tên gọi "CrawlFlow".

### Tương lai:
- Mở rộng visual composer
- Thêm nhiều data sources
- Tích hợp với các platform khác
- Performance optimization
- Advanced analytics

---

**Tài liệu này sẽ được cập nhật thường xuyên khi có thay đổi trong hệ thống.**