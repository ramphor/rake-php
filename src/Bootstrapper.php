<?php

namespace Rake;

use Rake\Manager\DatabaseDriverManager;

class Bootstrapper
{
    /**
     * Đăng ký các manager và service vào container
     *
     * @param Rake $app
     */
    public static function register(Rake $app)
    {
        // Helpers
        static::registerFunctions();


        // Register singletons
        $app->singleton(DatabaseDriverManager::class, function () {
            return new DatabaseDriverManager();
        });
        $app->singleton(Rake::class, function () use ($app) {
            return $app;
        });
        // Có thể đăng ký thêm các manager/service khác tại đây
    }

    /**
     * Đăng ký các hàm helper toàn cục
     */
    public static function registerFunctions()
    {
        if (!function_exists('rake')) {
            /**
             * Hàm helper trả về instance duy nhất của Rake
             *
             * @return Rake
             */
            function rake()
            {
                return Rake::getInstance();
            }
        }
    }
}
