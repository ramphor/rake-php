<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Processor;
use Ramphor\Rake\Exceptions\ResourceException;
use Ramphor\Rake\Exceptions\ProcessorException;

class Rake
{
    protected $adapter;
    protected $feed;
    protected $processorClassName;

    public function __construct(Adapter $adapter = null, Feed $feed = null, string $processorClassName = '')
    {
        if (!is_null($adapter)) {
            $this->setAdapter($adapter);
        }
        if (!is_null($feed)) {
            $this->setFeed($adapter);
        }
        if (!empty($processorClassName)) {
            $this->setProcessorClass($processorClassName);
        }
    }

    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function setFeed(Feed $feed)
    {
        $this->feed = $feed;
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
        if (empty($this->feed) || empty($this->adapter) || empty($this->processorClassName)) {
            throw new ResourceException();
        }

        $feedItems = $this->feed->getItems();
        $processorClassName = $this->processorClassName;
        foreach ($feedItems as $feedItem) {
            $processor = new $processorClassName($feedItem);
            if (!($processor instanceof Processor)) {
                throw new ProcessorException();
            }
            if ($processor->validateFeedItem()) {
                $result = $processor->excute();
            } else {
                $processor->writeLog("Feed item is not valid", $feedItem, $processor::LOG_WARNING);
            }
        }
    }
}
