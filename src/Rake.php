<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\AbstractProcessor;
use Ramphor\Rake\Abstracts\AbstractTooth;
use Ramphor\Rake\Abstracts\AbstractDriver;

use Ramphor\Rake\Exceptions\ResourceException;
use Ramphor\Rake\Exceptions\ProcessorException;

class Rake
{
    protected $rakeId;
    protected $driver;
    protected $feeds;
    protected $processorClassName;

    public function __construct(
        string $rakeId,
        AbstractDriver $driver = null,
        AbstractTooth $feed = null,
        string $processorClassName = ''
    ) {
        $this->rakeId = $rakeId;

        if (!is_null($driver)) {
            $this->setDriver($driver);
        }
        if (!is_null($feed)) {
            $this->setTooth($driver);
        }
        if (!empty($processorClassName)) {
            $this->setProcessorClass($processorClassName);
        }
    }

    public function getRakeId()
    {
        return $this->rakeId;
    }

    public function setDriver(AbstractDriver $driver)
    {
        $this->driver = $driver;
    }

    public function addTooth(AbstractTooth $feed)
    {
        if (isset($this->feeds[$feed->getId()])) {
            throw new \Exception(sprintf('Tooth "%s" is already exists', $feed->getId()));
        }
        $this->feeds[$feed->getId()] = $feed;
    }

    public function setProcessorClass(string $processorClassName)
    {
        if (empty($processorClassName)) {
            throw new ResourceException("Processor must be have value");
        }
        if (!class_exists($processorClassName)) {
            throw new ResourceException("Processor must be a class name");
        }
        $this->processorClassName = $processorClassName;
    }

    public function getProcessorClass()
    {
        return $this->processorClassName;
    }

    public function execute()
    {
        if (empty($this->feeds) || empty($this->driver) || empty($this->processorClassName)) {
            throw new ResourceException();
        }

        foreach ($this->feeds as $feed) {
            // Run preprocessors before get feed items
            $preprocessors = $feed->getPreprocessors();
            if (count($preprocessors) > 0) {
                foreach ($preprocessors as $preprocessor) {
                    $preprocessor->execute();
                }
            }

            $feedItems          = $feed->getItems();
            $processorClassName = $this->processorClassName;

            foreach ($feedItems as $feedItem) {
                $processor = new $processorClassName($feedItem);
                if (!($processor instanceof AbstractProcessor)) {
                    throw new ProcessorException();
                }
                if ($processor->validateFeedItem()) {
                    $result = $processor->execute();
                } else {
                    $processor->writeLog("Tooth item is not valid", $feedItem, $processor::LOG_WARNING);
                }
            }

            // Close the feed stream
            $feed->closeStream();
        }
    }
}
