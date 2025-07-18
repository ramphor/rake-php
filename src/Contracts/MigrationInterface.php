<?php

namespace Rake\Contracts;

/**
 * Interface for database migration classes.
 * Each migration must implement up() and down() methods.
 */
interface MigrationInterface
{
    /**
     * Run the migrations (apply changes).
     *
     * @return void
     */
    public function up();

    /**
     * Reverse the migrations (rollback changes).
     *
     * @return void
     */
    public function down();
}