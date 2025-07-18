php
<?php

return [
    // Thông tin chung cho dự án crawl/migrate
    'project' => [
        'name'        => 'Bookix Migration',
        'description' => 'Di chuyển dữ liệu sản phẩm từ WordPress sang hệ thống mới',
        'env'         => 'production', // hoặc 'development'
    ],

    // Định nghĩa các Tooth (mỗi Tooth là một dự án crawl/migrate riêng biệt)
    'teeth' => [
        [
            'id'    => 'bookix-products',
            'label' => 'Bookix Products',
            'feeds' => [
                [
                    'type' => 'url',
                    'source' => 'https://bookix.vn/sitemap-products.xml',
                    'parser' => 'xml',
                ],
                [
                    'type' => 'csv',
                    'source' => '/data/products.csv',
                    'parser' => 'csv',
                ],
            ],
            'processors' => [
                'normalize',
                'deduplicate',
                'save_to_db',
                'export_epub',
            ],
            'resource_types' => ['product', 'image'],
            'options' => [
                'batch_size' => 100,
                'retry'      => 3,
            ],
        ],
        // Có thể thêm nhiều Tooth khác...
    ],

    // Cấu hình database (cho các driver adapter)
    'database' => [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'bookix',
        'user'     => 'root',
        'password' => 'secret',
        'charset'  => 'utf8mb4',
        // Có thể mở rộng cho các loại driver khác (pgsql, sqlite, v.v.)
    ],

    // Cấu hình HTTP client (cho crawl, download file, v.v.)
    'http_client' => [
        'adapter'  => 'guzzle',
        'timeout'  => 10,
        'retries'  => 2,
        'headers'  => [
            'User-Agent' => 'BookixBot/1.0',
        ],
    ],

    // Các preset, mapping, rule, v.v. (nếu có)
    'presets' => [
        'product_mapping' => [
            'title'       => 'name',
            'description' => 'desc',
            'price'       => 'cost',
            // ...
        ],
    ],

    // Cấu hình worker, queue, logging, v.v.
    'worker' => [
        'concurrent' => 2,
        'log_level'  => 'info',
        'queue'      => 'default',
    ],
];
