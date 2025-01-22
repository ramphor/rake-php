<?php

namespace Ramphor\Rake\Facades;

use Ramphor\Rake\Abstracts\Crawler as AbstractCrawler;

/**
 * @method static void register(string $id, string $crawlerClass)
 * @method static AbstractCrawler create(string $id)
 * @method static AbstractCrawler get(string $id)
 * @method static bool has(string $id)
 * @method static array getCrawlers()
 * @method static void clear()
 * @method static void execute(string $id)
 * @method static void executeAll()
 * @method static void stop(string $id)
 * @method static void stopAll()
 * @method static bool isRunning(string $id)
 *
 * @see \Ramphor\Rake\Managers\CrawlerManager
 */
class Crawler extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'crawler';
    }
}
