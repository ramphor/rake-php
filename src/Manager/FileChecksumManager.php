<?php

namespace Rake\Manager;

use Rake\Adapter\Database\DatabaseAdapterInterface;

/**
 * File Checksum Manager
 * Quản lý checksum cho file integrity và deduplication
 * Sử dụng XXH128 cho đồng nhất và hiệu suất cao
 */
class FileChecksumManager
{
    /**
     * @var DatabaseAdapterInterface
     */
    private $databaseAdapter;

    /**
     * @var int
     */
    private const CHECKSUM_LENGTH = 32; // XXH128 produces 32 hex characters

    public function __construct(DatabaseAdapterInterface $databaseAdapter)
    {
        $this->databaseAdapter = $databaseAdapter;
    }

    /**
     * Tạo checksum cho content
     *
     * @param string $content
     * @return string
     */
    public function createChecksum(string $content): string
    {
        if (!function_exists('hash')) {
            throw new \RuntimeException('Hash functions not available');
        }

        return hash('xxh128', $content);
    }

    /**
     * Tạo checksum cho file
     *
     * @param string $filePath
     * @return string
     * @throws \RuntimeException
     */
    public function createFileChecksum(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read file: {$filePath}");
        }

        return $this->createChecksum($content);
    }

        /**
     * Lưu checksum vào database
     *
     * @param int $resourceId
     * @param string $checksum
     * @param string|null $appNewType
     * @param string|null $appNewGuid
     * @return bool
     */
    public function saveChecksum(int $resourceId, string $checksum, ?string $appNewType = null, ?string $appNewGuid = null): bool
    {
        $table = $this->getTableName();

        $data = [
            'resource_id' => $resourceId,
            'checksum' => $checksum,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($appNewType !== null) {
            $data['app_new_type'] = $appNewType;
        }

        if ($appNewGuid !== null) {
            $data['app_new_guid'] = $appNewGuid;
        }

        return $this->databaseAdapter->insert($table, $data);
    }

    /**
     * Kiểm tra xem checksum đã tồn tại chưa
     *
     * @param string $checksum
     * @return bool
     */
    public function checksumExists(string $checksum): bool
    {
        $table = $this->getTableName();

        $result = $this->databaseAdapter->select(
            $table,
            ['id'],
            ['checksum' => $checksum],
            1
        );

        return !empty($result);
    }

    /**
     * Lấy resource ID theo checksum
     *
     * @param string $checksum
     * @return int|null
     */
    public function getResourceIdByChecksum(string $checksum): ?int
    {
        $table = $this->getTableName();

        $result = $this->databaseAdapter->select(
            $table,
            ['resource_id'],
            ['checksum' => $checksum],
            1
        );

        return $result[0]['resource_id'] ?? null;
    }

    /**
     * Kiểm tra integrity của file
     *
     * @param int $resourceId
     * @param string $filePath
     * @return bool
     */
    public function verifyFileIntegrity(int $resourceId, string $filePath): bool
    {
        try {
            $currentChecksum = $this->createFileChecksum($filePath);
            $storedChecksum = $this->getChecksumByResourceId($resourceId);

            return $currentChecksum === $storedChecksum;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lấy checksum theo resource ID
     *
     * @param int $resourceId
     * @return string|null
     */
    public function getChecksumByResourceId(int $resourceId): ?string
    {
        $table = $this->getTableName();

        $result = $this->databaseAdapter->select(
            $table,
            ['checksum'],
            ['resource_id' => $resourceId],
            1
        );

        return $result[0]['checksum'] ?? null;
    }

    /**
     * Xóa checksum theo resource ID
     *
     * @param int $resourceId
     * @return bool
     */
    public function deleteChecksum(int $resourceId): bool
    {
        $table = $this->getTableName();

        return $this->databaseAdapter->delete(
            $table,
            ['resource_id' => $resourceId]
        );
    }

    /**
     * Lấy tên bảng
     *
     * @return string
     */
    private function getTableName(): string
    {
        return 'rake_file_checksums';
    }

    /**
     * Validate checksum format
     *
     * @param string $checksum
     * @return bool
     */
    public function validateChecksum(string $checksum): bool
    {
        return strlen($checksum) === self::CHECKSUM_LENGTH &&
               ctype_xdigit($checksum);
    }

        /**
     * Lấy thống kê checksum
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $table = $this->getTableName();

        $total = $this->databaseAdapter->count($table);

        return [
            'total_checksums' => $total,
            'algorithm' => 'xxh128',
            'checksum_length' => self::CHECKSUM_LENGTH
        ];
    }

    /**
     * Cập nhật app_new_type và app_new_guid cho checksum
     *
     * @param int $resourceId
     * @param string $appNewType
     * @param string $appNewGuid
     * @return bool
     */
    public function updateAppInfo(int $resourceId, string $appNewType, string $appNewGuid): bool
    {
        $table = $this->getTableName();

        return $this->databaseAdapter->update(
            $table,
            [
                'app_new_type' => $appNewType,
                'app_new_guid' => $appNewGuid
            ],
            ['resource_id' => $resourceId]
        );
    }

    /**
     * Lấy thông tin app theo resource ID
     *
     * @param int $resourceId
     * @return array|null
     */
    public function getAppInfo(int $resourceId): ?array
    {
        $table = $this->getTableName();

        $result = $this->databaseAdapter->select(
            $table,
            ['app_new_type', 'app_new_guid'],
            ['resource_id' => $resourceId],
            1
        );

        if (empty($result)) {
            return null;
        }

        return [
            'app_new_type' => $result[0]['app_new_type'],
            'app_new_guid' => $result[0]['app_new_guid']
        ];
    }

    /**
     * Lấy resource ID theo app_new_guid
     *
     * @param string $appNewGuid
     * @param string|null $appNewType
     * @return int|null
     */
    public function getResourceIdByAppGuid(string $appNewGuid, ?string $appNewType = null): ?int
    {
        $table = $this->getTableName();

        $where = ['app_new_guid' => $appNewGuid];
        if ($appNewType !== null) {
            $where['app_new_type'] = $appNewType;
        }

        $result = $this->databaseAdapter->select(
            $table,
            ['resource_id'],
            $where,
            1
        );

        return $result[0]['resource_id'] ?? null;
    }

    /**
     * Lấy danh sách checksum theo app_new_type
     *
     * @param string $appNewType
     * @return array
     */
    public function getChecksumsByAppType(string $appNewType): array
    {
        $table = $this->getTableName();

        return $this->databaseAdapter->select(
            $table,
            ['resource_id', 'checksum', 'app_new_guid'],
            ['app_new_type' => $appNewType]
        );
    }
}