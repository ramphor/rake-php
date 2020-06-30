<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\TemplateMethod;
use Ramphor\Rake\Abstracts\AbstractProcessor;
use Ramphor\Rake\Abstracts\AbstractTooth;
use Ramphor\Rake\Abstracts\AbstractDriver;
use Ramphor\Rake\Abstracts\AbstractHttpClient;

use Ramphor\Rake\Exceptions\ResourceException;
use Ramphor\Rake\Exceptions\ProcessorException;

class Rake extends TemplateMethod
{
    protected $id;
    protected $teeth;

    public function __construct(
        string $rakeId,
        AbstractDriver $driver = null,
        AbstractHttpClient $httpClient = null
    ) {
        $this->setId($rakeId);
        if (!is_null($driver)) {
            $this->setDriver($driver);
        }
        if (!is_null($httpClient)) {
            $this->setHttpClient($httpClient);
        }
    }

    public function addTooth(AbstractTooth $tooth)
    {
        if (isset($this->teeth[$tooth->getId()])) {
            throw new \Exception(sprintf('Tooth "%s" is already exists', $tooth->getId()));
        }
        $this->teeth[$tooth->getId()] = $tooth;
    }

    public function execute()
    {
        if (empty($this->teeth) || empty($this->driver)) {
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

            $feedItems = $tooth->getItems();
            foreach ($feedItems as $feedItem) {
                $processor = $tooth->createProcessor($feedItem);

                if ($processor->validateFeedItem()) {
                    $result = $processor->execute();
                } else {
                    $processor->writeLog("Tooth item is not valid", $feedItem, $processor::LOG_WARNING);
                }
            }

            // Close the feed stream
            $tooth->closeStream();
        }
    }
}
