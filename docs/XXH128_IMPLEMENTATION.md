# XXH128 Implementation trong Rake 2.0

## Tổng quan

Rake 2.0 sử dụng **XXH128** làm thuật toán checksum duy nhất cho file integrity và deduplication. Đây là lựa chọn tối ưu cho framework với các ưu điểm sau:

## Tại sao chọn XXH128?

### ✅ **Hiệu suất cao**
- **XXHash** là một trong những thuật toán hash nhanh nhất
- **XXH128** cung cấp tốc độ cực cao với collision resistance tốt
- Nhanh hơn MD5, SHA1, SHA256 nhiều lần

### ✅ **Độ dài cố định**
- **32 ký tự hex** (128-bit output)
- Dễ dàng lưu trữ và so sánh
- Không cần padding hay encoding phức tạp

### ✅ **PHP 8.1+ Native Support**
- Built-in support trong PHP 8.1+
- Không cần extension bổ sung
- Tương thích tốt với WordPress environment

### ✅ **Collision Resistance**
- 128-bit output đủ mạnh cho deduplication
- Collision probability cực thấp
- Phù hợp cho file integrity checking

## Implementation

### 1. Schema Definition

```php
// rake/schema_definitions/file_checksums.php
// XXH128 là thuật toán duy nhất, không cần lưu algorithm field
// Schema chỉ cần: resource_id, checksum, app_new_type, app_new_guid, created_at
```

### 2. FileChecksumManager

```php
class FileChecksumManager
{
    private const CHECKSUM_LENGTH = 32; // XXH128 produces 32 hex characters

    public function createChecksum(string $content): string
    {
        return hash('xxh128', $content);
    }
}
```

### 3. FileIntegrityManager

Quản lý file download, storage và deduplication:

```php
$integrityManager = new FileIntegrityManager($databaseAdapter);

$result = $integrityManager->downloadAndStoreFile($url, $resourceId);
// Returns: ['success' => true, 'duplicate' => false, 'checksum' => 'abc123...']
```

## Database Schema

### rake_file_checksums Table

```sql
CREATE TABLE rake_file_checksums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    checksum VARCHAR(128) NOT NULL,  -- XXH128 produces 32 hex chars
    app_new_type VARCHAR(64) NULL,   -- Target system type (e.g., wordpress_post)
    app_new_guid VARCHAR(128) NULL,  -- Target system ID (e.g., post_id)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (resource_id),
    INDEX (checksum),
    INDEX (app_new_type),
    INDEX (app_new_guid),
    FOREIGN KEY (resource_id) REFERENCES rake_resources(id)
);
```

## Usage Examples

### 1. Tạo checksum cho content

```php
$checksumManager = new FileChecksumManager($databaseAdapter);
$checksum = $checksumManager->createChecksum($content);
// Returns: "a1b2c3d4e5f6..." (32 characters)
```

### 2. Download và store file

```php
$integrityManager = new FileIntegrityManager($databaseAdapter);

$result = $integrityManager->downloadAndStoreFile(
    'https://example.com/image.jpg',
    123
);

if ($result['success']) {
    if ($result['duplicate']) {
        echo "File đã tồn tại, sử dụng existing file";
    } else {
        echo "File mới được lưu: " . $result['file_path'];
    }
}
```

### 3. Kiểm tra integrity

```php
$isValid = $integrityManager->verifyFileIntegrity($resourceId);
echo $isValid ? "File integrity OK" : "File corrupted";
```

### 4. Thống kê

```php
$stats = $integrityManager->getIntegrityStatistics();
echo "Integrity rate: " . $stats['integrity_rate'] . "%";
```

## Performance Benchmarks

### XXH128 vs Other Algorithms

| Algorithm | Speed (MB/s) | Output Size | Collision Resistance |
|-----------|-------------|-------------|---------------------|
| XXH128    | ~5000       | 32 chars    | Very High           |
| MD5       | ~500        | 32 chars    | Medium              |
| SHA1      | ~300        | 40 chars    | High                |
| SHA256    | ~200        | 64 chars    | Very High           |

### Memory Usage

- XXH128: ~128 bytes per checksum
- Efficient storage và indexing
- Minimal memory footprint

## Best Practices

### 1. Consistent Algorithm
- Luôn sử dụng XXH128 cho tất cả checksum
- Không mix các thuật toán khác nhau
- Đảm bảo consistency trong toàn bộ framework

### 2. Error Handling
```php
try {
    $checksum = $checksumManager->createChecksum($content);
} catch (RuntimeException $e) {
    // Handle hash function not available
    log_error("Hash function not available: " . $e->getMessage());
}
```

### 3. Validation
```php
if (!$checksumManager->validateChecksum($checksum)) {
    throw new InvalidArgumentException("Invalid checksum format");
}
```

### 4. Deduplication Strategy
```php
if ($checksumManager->checksumExists($checksum)) {
    // Use existing file, don't download again
    $existingResourceId = $checksumManager->getResourceIdByChecksum($checksum);
}

// Update app info after successful import
$checksumManager->updateAppInfo($resourceId, 'wordpress_post', '123');
```

## Migration từ các thuật toán khác

Nếu cần migrate từ MD5/SHA1:

```php
// Legacy checksum validation
$legacyChecksum = md5($content);
$newChecksum = hash('xxh128', $content);

// Update database
$databaseAdapter->update(
    'rake_file_checksums',
    ['checksum' => $newChecksum, 'algorithm' => 'xxh128'],
    ['checksum' => $legacyChecksum]
);
```

## Testing

Chạy test script:

```bash
cd rake
php tests/test_checksum.php
```

Test script sẽ kiểm tra:
- ✅ Checksum generation
- ✅ File integrity verification
- ✅ Database operations
- ✅ Performance benchmarks
- ✅ Collision resistance

## Kết luận

XXH128 là lựa chọn tối ưu cho Rake 2.0 với:
- **Hiệu suất cao** cho large-scale crawling
- **Độ tin cậy cao** cho file integrity
- **Đơn giản** trong implementation và maintenance
- **Tương thích tốt** với PHP 8.1+ và WordPress

Framework sẽ đạt được hiệu suất tối ưu và độ tin cậy cao cho file management system.