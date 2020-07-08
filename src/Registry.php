<?php
namespace Ramphor\Rake;

final class Registry {
    protected static $instance;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public static function __callStatic($name, $args) {
    }
}
