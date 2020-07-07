<?php
namespace Ramphor\Rake;

class ProcessResult
{
    protected $guid;
    protected $resultType;
    protected $errors;
    protected $urlDbId;

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

    public function getGuid()
    {
        return $this->guid;
    }

    public function setResultType(bool $isSuccess): self
    {
        $this->resultType = $isSuccess;
        return $this;
    }

    public function isSucess()
    {
        return $this->resultType;
    }

    public function setNewGuid($newGuid)
    {
        $this->newGuid = $newGuid;
    }

    public function getNewGuid()
    {
        return $this->newGuid;
    }

    public function setNewType($newType)
    {
        $this->newType = $newType;
    }

    public function getNewType()
    {
        return $this->newType;
    }

    public function setUrlDbId($urlId)
    {
        $this->urlDbId = $urlId;
    }

    public function getUrlDbId()
    {
        return $this->urlDbId;
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
