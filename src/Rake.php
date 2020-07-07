<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\TemplateMethod;
use Ramphor\Rake\Abstracts\Processor;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Abstracts\Driver;
use Ramphor\Rake\Abstracts\Http\Client;

use Ramphor\Rake\Exceptions\ResourceException;
use Ramphor\Rake\Exceptions\ProcessorException;

class Rake extends TemplateMethod
{
    protected $id;
    protected $teeth;

    public function __construct(
        string $rakeId,
        Driver $driver = null,
        Client $httpClient = null
    ) {
        $this->setId($rakeId);
        if (!is_null($driver)) {
            $this->setDriver($driver);
        }
        if (!is_null($httpClient)) {
            $this->setHttpClient($httpClient);
        }
    }

    public function addTooth(Tooth $tooth)
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
            // Crawl data from the feeds of tooth
            $tooth->execute();

            $feedItems = $tooth->getItems();
            $processor = $tooth->getProcessor();

            foreach ($feedItems as $feedItem) {
                if (!$feedItem->isValid()) {
                    continue;
                }

                $processor->setFeedItem($feedItem);

                if ($processor->isSuccess()) {
                    $result = $processor->execute();
                }
            }
        }
    }
}
