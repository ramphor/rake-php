<?php
namespace Ramphor\Rake;

class ProcessResult
{
    protected $isSuccess;
    protected $errors;

    protected $newGUID;
    protected $newType;

    public static function createSuccessResult($newGUID, $newType): self
    {
        $result = new static();
        $result->setNewGUID($newGUID);
        $result->setNewType($newType);

        return $result->setResultType(true);
    }

    public static function createErrorResult($errorMessage): self
    {
        $result = new static();
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

    public function setNewGUID($newGUID)
    {
        $this->newGUID = $newGUID;
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
