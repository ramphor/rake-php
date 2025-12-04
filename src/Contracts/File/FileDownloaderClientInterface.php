<?php

namespace Rake\Contracts\File;

/**
 * File Downloader Client Interface
 * 
 * Contract cho việc download files từ URLs.
 * Implementations có thể sử dụng WordPress wp_remote_get, cURL, Guzzle, etc.
 */
interface FileDownloaderClientInterface
{
    /**
     * Download file từ URL và lưu vào đường dẫn chỉ định
     *
     * @param string $url URL của file cần download
     * @param string $destinationPath Đường dẫn lưu file
     * @param array $options Options cho download (timeout, headers, etc.)
     * @return array ['success' => bool, 'file_path' => string, 'error' => string|null, 'file_size' => int, 'mime_type' => string|null]
     */
    public function downloadFile(string $url, string $destinationPath, array $options = []): array;

    /**
     * Download file và trả về content (không lưu vào disk)
     *
     * @param string $url URL của file cần download
     * @param array $options Options cho download
     * @return array ['success' => bool, 'content' => string|null, 'error' => string|null, 'content_length' => int, 'mime_type' => string|null]
     */
    public function downloadContent(string $url, array $options = []): array;

    /**
     * Kiểm tra URL có accessible không (HEAD request)
     *
     * @param string $url
     * @return bool
     */
    public function isUrlAccessible(string $url): bool;

    /**
     * Lấy file info mà không download (HEAD request)
     *
     * @param string $url
     * @return array ['success' => bool, 'file_size' => int|null, 'mime_type' => string|null, 'last_modified' => string|null]
     */
    public function getFileInfo(string $url): array;
}

