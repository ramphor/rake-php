# Rake Bootstrapper System

## Tổng quan

Hệ thống Bootstrapper trong Rake Framework cho phép quản lý việc khởi tạo các services một cách có tổ chức và mở rộng.

## Kiến trúc

### 1. AbstractKernel
- **Vị trí**: `rake/src/Kernel/AbstractKernel.php`
- **Chức năng**: Base class cho tất cả kernels
- **Quản lý**: Default bootstrappers và custom bootstrappers

### 2. Default Bootstrappers (Rake Core)
Các bootstrapper mặc định được đăng ký tự động:

```php
$defaultBootstrappers = [
    \Rake\Bootstrapper\CoreBootstrapper::class,      // Core services
    \Rake\Bootstrapper\DatabaseBootstrapper::class,   // Database services
    \Rake\Bootstrapper\LoggingBootstrapper::class,    // Logging services
    \Rake\Bootstrapper\HttpBootstrapper::class,       // HTTP services
    \Rake\Bootstrapper\EventBootstrapper::class,      // Event services
];
```

### 3. Custom Bootstrappers
Các bootstrapper tùy chỉnh được đăng ký bởi các ứng dụng con.

## Cách sử dụng

### 1. Tạo Kernel cho ứng dụng con

```php
<?php

namespace MyApp\Kernel;

use Rake\Kernel\AbstractKernel;
use MyApp\Bootstrapper\MyAppBootstrapper;

class MyAppKernel extends AbstractKernel
{
    protected function registerBootstrappers(): void
    {
        // Thêm custom bootstrapper
        $this->addCustomBootstrapper(MyAppBootstrapper::class);
    }
}
```

### 2. Tạo Bootstrapper

```php
<?php

namespace MyApp\Bootstrapper;

use Rake\Bootstrapper\BootstrapperInterface;
use Rake\Rake;

class MyAppBootstrapper implements BootstrapperInterface
{
    public function bootstrap(Rake $app): void
    {
        // Đăng ký services
        $app->singleton(MyService::class, function () {
            return new MyService();
        });

        // Log initialization
        $this->logInitialization($app);
    }

    protected function logInitialization(Rake $app): void
    {
        try {
            $logger = $app->make(\Rake\Manager\LoggerManager::class);
            $logger->info('MyApp services initialized');
        } catch (\Exception $e) {
            // Fallback logging
        }
    }
}
```

### 3. Sử dụng Kernel

```php
// Khởi tạo kernel
$kernel = new MyAppKernel();

// Cấu hình
$kernel->setConfig([
    'app' => [
        'name' => 'MyApp',
        'version' => '1.0.0'
    ]
]);

// Boot kernel
$kernel->boot();

// Sử dụng
$app = $kernel->getApp();
$service = $app->make(MyService::class);
```

## CrawlFlow Implementation

### 1. CrawlFlowKernel

```php
class CrawlFlowKernel extends AbstractKernel
{
    protected function registerBootstrappers(): void
    {
        // Chỉ thêm custom bootstrapper
        $this->addCustomBootstrapper(CrawlFlowBootstrapper::class);
    }
}
```

### 2. Thêm bootstrapper nâng cao

```php
// Trong CrawlFlowKernel
public function initialize(): self
{
    $this->configure();

    // Thêm bootstrapper nâng cao nếu cần
    if (get_option('crawlflow_advanced_mode', false)) {
        $this->addAdditionalBootstrapper(CrawlFlowAdvancedBootstrapper::class);
    }

    $this->boot();
    return $this;
}
```

## Quản lý Bootstrapper

### 1. Kiểm tra loại bootstrapper

```php
$kernel = new CrawlFlowKernel();

// Kiểm tra default bootstrappers
$isDefault = $kernel->isDefaultBootstrapper(\Rake\Bootstrapper\CoreBootstrapper::class);

// Kiểm tra custom bootstrappers
$isCustom = $kernel->isCustomBootstrapper(\CrawlFlow\Bootstrapper\CrawlFlowBootstrapper::class);
```

### 2. Lấy thông tin bootstrapper

```php
$info = $kernel->getBootstrapperInfo();

// Kết quả:
[
    'default_count' => 5,
    'custom_count' => 1,
    'total_count' => 6,
    'default_bootstrappers' => [...],
    'custom_bootstrappers' => [...]
]
```

### 3. Status monitoring

```php
$status = $kernel->getStatus();

// Kết quả:
[
    'booted' => true,
    'bootstrappers' => [
        'all' => [...],
        'default' => [...],
        'custom' => [...]
    ],
    'config' => [...],
    'app_status' => [...]
]
```

## Best Practices

### 1. Không override default bootstrappers
```php
// ❌ Sai - Không làm thế này
$this->addBootstrapper(CoreBootstrapper::class); // Override default

// ✅ Đúng - Sử dụng custom bootstrapper
$this->addCustomBootstrapper(MyCustomBootstrapper::class);
```

### 2. Log initialization
```php
protected function logInitialization(Rake $app): void
{
    try {
        $logger = $app->make(\Rake\Manager\LoggerManager::class);
        $logger->info('MyApp services initialized', [
            'services' => ['Service1', 'Service2']
        ]);
    } catch (\Exception $e) {
        // Fallback logging
        error_log('MyApp: Failed to log initialization');
    }
}
```

### 3. Error handling
```php
public function bootstrap(Rake $app): void
{
    try {
        $this->registerServices($app);
        $this->logInitialization($app);
    } catch (\Exception $e) {
        // Log error và tiếp tục
        error_log('Bootstrapper failed: ' . $e->getMessage());
    }
}
```

## Lợi ích

1. **Modular**: Mỗi module có bootstrapper riêng
2. **Extensible**: Dễ thêm bootstrappers mới
3. **Non-overriding**: Default bootstrappers không bị override
4. **Organized**: Services được đăng ký có tổ chức
5. **Testable**: Dễ test từng bootstrapper
6. **Maintainable**: Code sạch và dễ maintain