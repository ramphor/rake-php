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
    protected $teeth;
    protected $processorClassName;

    public function __construct(
        string $rakeId,
        AbstractDriver $driver = null,
        AbstractTooth $tooth = null,
        string $processorClassName = ''
    ) {
        $this->rakeId = $rakeId;

        if (!is_null($driver)) {
            $this->setDriver($driver);
        }
        if (!is_null($tooth)) {
            $this->setTooth($driver);
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

    public function addTooth(AbstractTooth $tooth)
    {
        if (isset($this->teeth[$tooth->getId()])) {
            throw new \Exception(sprintf('Tooth "%s" is already exists', $tooth->getId()));
        }
        $this->teeth[$tooth->getId()] = $tooth;
    }

    public function getProcessorClass()
    {
        return $this->processorClassName;
    }

    public function execute()
    {
        if (empty($this->teeth) || empty($this->driver) || empty($this->processorClassName)) {
            throw new ResourceException();
        }

        foreach ($this->teeth as $tooth) {
            // Run feeds before get feed items
            $feeds = $tooth->getFeeds();
            if (count($feeds) > 0) {
                foreach ($feeds as $feed) {
                    $feed->execute();
                }
            }

            $toothItems          = $tooth->getItems();
            $processorClassName = $this->processorClassName;

            foreach ($toothItems as $toothItem) {
                $processor = new $processorClassName($toothItem);
                if (!($processor instanceof AbstractProcessor)) {
                    throw new ProcessorException();
                }
                if ($processor->validateFeedItem()) {
                    $result = $processor->execute();
                } else {
                    $processor->writeLog("Tooth item is not valid", $toothItem, $processor::LOG_WARNING);
                }
            }

            // Close the feed stream
            $tooth->closeStream();
        }
    }
}
