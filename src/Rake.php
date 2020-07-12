<?php
namespace Ramphor\Rake;

use Ramphor\Rake\Abstracts\TemplateMethod;
use Ramphor\Rake\Abstracts\Processor;
use Ramphor\Rake\Abstracts\Tooth;
use Ramphor\Rake\Facades\Facade;
use Ramphor\Rake\App;
use Ramphor\Rake\Abstracts\Driver;
use Psr\Http\Client\ClientInterface;

use Ramphor\Rake\Exceptions\ResourceException;
use Ramphor\Rake\Exceptions\ProcessorException;

class Rake
{
    protected static $app;

    protected $id;
    protected $teeth;

    public function __construct(string $rakeId, Driver $driver = null, ClientInterface $client = null)
    {
        static::$app = App::instance();

        $this->setId($rakeId);
        if (!is_null($driver)) {
            static::$app->bind('db', $driver);
        }
        if (!is_null($client)) {
            static::$app->bind('http', $client);
        }

        Facade::setFacadeApplication(static::$app);
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
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
        if (empty($this->teeth)) {
            throw new ResourceException();
        }

        $results = [];
        foreach ($this->teeth as $tooth) {
            // Crawl data from the feeds of tooth
            $tooth->execute();

            $feedItems = $tooth->getItems();
            $processor = $tooth->getProcessor();

            foreach ($feedItems as $feedItem) {
                if (!$feedItem->isValid()) {
                    array_push($results, ProcessResult::createErrorResult(
                        sprintf('The feed item "%s" is invalid', $feedItem->guid),
                        true
                    ));
                    continue;
                }

                $processor->setFeedItem($feedItem);

                $result = $processor->execute();
                if ($feedItem->urlDbId) {
                    $result->setUrlDbId($feedItem->urlDbId);
                }

                // Store all results
                array_push($results, $result);
            }
        }

        $this->sync($results);
    }

    public function sync($results)
    {
    }
}
