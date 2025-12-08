<?php
return [
    'table' => 'crawlflow_executions',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'project_id' => [
            'type' => 'bigint',
            'comment' => 'Reference to rake_tooths.id (project)',
        ],
        'status' => [
            'type' => 'string',
            'length' => 20,
            'default' => 'completed',
            'comment' => 'completed, failed, running',
        ],
        'repository_count' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of items in repository',
        ],
        'extracted_count' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of items extracted',
        ],
        'processed_count' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of items processed',
        ],
        'error_count' => [
            'type' => 'int',
            'default' => 0,
            'comment' => 'Number of errors',
        ],
        'execution_time' => [
            'type' => 'decimal',
            'length' => '10,3',
            'default' => 0.000,
            'comment' => 'Execution time in seconds',
        ],
        'executed_at' => [
            'type' => 'datetime',
            'comment' => 'Timestamp when execution started',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['project_id']],
        ['fields' => ['status']],
        ['fields' => ['executed_at']],
    ],
    'foreign_keys' => [
        [
            'columns' => ['project_id'],
            'references' => ['table' => 'rake_tooths', 'columns' => ['id']],
            'on_delete' => 'CASCADE',
            'on_update' => 'CASCADE',
        ],
    ],
    'version' => '1.0.0',
];

