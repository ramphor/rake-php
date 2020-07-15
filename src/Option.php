<?php
namespace Ramphor\Rake;

class Option
{
    protected static $autoTransferFiles = true;

    public static function autoTranferFiles($enable = true)
    {
        self::$autoTransferFiles = (bool) $enable;
    }

    public static function isAutoTransferFiles()
    {
        return self::$autoTransferFiles;
    }
}
