<?php
return [
    'table' => 'rake_event_status',
    'engine' => 'InnoDB',
    'collation' => 'utf8mb4_unicode_ci',
    'fields' => [
        'id' => [
            'type' => 'bigint',
            'auto_increment' => true,
            'primary' => true,
        ],
        'event_name' => [
            'type' => 'string',
            'length' => 191,
            'comment' => 'Cron event name',
        ],
        'process_id' => [
            'type' => 'bigint',
            'comment' => 'OS process id running the event',
        ],
        'status' => [
            'type' => 'string',
            'length' => 20,
            'comment' => 'pending, running, complete, error, cancel',
        ],
        'created_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
        'updated_at' => [
            'type' => 'datetime',
            'default' => 'CURRENT_TIMESTAMP',
        ],
    ],
    'indexes' => [
        ['fields' => ['event_name', 'status'], 'name' => 'idx_event_status'],
        ['fields' => ['process_id'], 'name' => 'idx_process_id'],
    ],
    'version' => '1.0.0',
];

