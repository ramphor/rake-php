<?php

namespace Ramphor\Rake\Abstracts;

use Ramphor\Rake\Constracts\Driver as DriverConstract;

abstract class Driver implements DriverConstract
{
    public function table($name)
    {
        return sprintf('%s%s', $this->prefix(), $name);
    }

    protected function mapResults($results, $classToMap)
    {
        $mappedResults = [];
        foreach ($results as $result) {
            $mappedResult = new $classToMap();

            $reflection = new \ReflectionClass($classToMap);
            foreach ($reflection->getProperties() as $property) {
                $originVisibility = $property->isPublic();
                if (!$originVisibility) {
                    $property->setAccessible(true);
                }

                $propertyName = $property->getName();
                $underscoredPropertyName = strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $propertyName));

                if (isset($result->{$propertyName})) {
                    $property->setValue($mappedResult, $result->{$propertyName});
                    continue;
                } elseif (isset($result->{$underscoredPropertyName})) {
                    $property->setValue($mappedResult, $result->{$underscoredPropertyName});
                    continue;
                } elseif (in_array($propertyName, ['ID'])) {
                    if (isset($result->id)) {
                        $property->setValue($mappedResult, $result->id);
                        continue;
                    }
                }
                if (!$originVisibility) {
                    $property->setAccessible(false);
                }
            }
            $mappedResults[] = $mappedResult;
        }
        return $mappedResults;
    }
}
