<?php

namespace Rake\Manager\Database;

/**
 * DatabaseDriverManager: Registry quản lý các Database Driver adapter (wpdb, Eloquent, ...)
 */
class DatabaseDriverManager
{
    private $drivers = [];
    private $defaultDriver;

    /**
     * Đăng ký một driver mới.
     *
     * @param string $name
     * @param mixed $driver
     */
    public function registerDriver(string $name, $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    /**
     * Lấy driver theo tên.
     *
     * @param string $name
     * @return mixed|null
     */
    public function getDriver(?string $name = null)
    {
        if ($name === null) {
            return $this->getDefaultDriver();
        }
        return $this->drivers[$name] ?? null;
    }

    /**
     * Xóa driver theo tên.
     *
     * @param string $name
     */
    public function removeDriver(string $name): void
    {
        unset($this->drivers[$name]);
    }

    /**
     * Đặt driver mặc định.
     *
     * @param string $name
     */
    public function setDefaultDriver(string $name): void
    {
        if (isset($this->drivers[$name])) {
            $this->defaultDriver = $name;
        }
    }

    /**
     * Lấy driver mặc định.
     *
     * @return mixed|null
     */
    public function getDefaultDriver()
    {
        return $this->getDriver($this->defaultDriver);
    }
}