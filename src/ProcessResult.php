<?php
namespace Ramphor\Rake;

class ProcessResult
{
    protected $guid;
    protected $isSuccess;
    protected $errors;

    protected $newGuid;
    protected $newType;

    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    public static function createSuccessResult($guid, $newGuid, $newType): self
    {
        $result = new static($guid);
        $result->setNewGuid($newGuid);
        $result->setNewType($newType);

        return $result->setResultType(true);
    }

    public static function createErrorResult($errorMessage): self
    {
        $result = new static($guid);
        $result->addErrorMessage($errorMessage);

        return $result->setResultType(false);
    }

    public function setResultType(bool $isSuccess): self
    {
        $this->resultType = $isSuccess;
        return $this;
    }

    public function isSucess()
    {
        return $this->isSuccess;
    }

    public function setNewGuid($newGuid)
    {
        $this->newGuid = $newGuid;
    }

    public function setNewType($newType)
    {
        $this->newType = $newType;
    }

    public function addErrorMessage($errorMessage)
    {
        array_push($this->errors, $errorMessage);
    }

    // Get first error message
    public function getErrorMessage()
    {
        if (count($this->errors) <= 0) {
            return '';
        }
        return $this->errors[0];
    }

    public function getErrorMessages()
    {
        return $this->errors;
    }
}
