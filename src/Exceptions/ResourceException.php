<?php
namespace Ramphor\Rake\Exceptions;

use Exception;

class ResourceException extends Exception
{
    protected $message = "Resource exception: The Driver or Feed is empty";
}
