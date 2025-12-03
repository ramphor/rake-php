<?php

namespace Rake\Contracts\Processor;

use Rake\Contracts\Entities\ParsedDataItemInterface;

/**
 * Interface for Processor
 * Processes data and returns processed result
 * 
 * Chain of Responsibility Pattern:
 * - Returns ParsedDataItemInterface (ExtractedDataItem) to continue chain
 * - Returns NullDataItem if processor rejected/failed (chain can continue)
 * - Null Object Pattern: Never returns null, always returns a ParsedDataItemInterface
 */
interface ProcessorInterface
{
    /**
     * Process data item
     * 
     * @param ParsedDataItemInterface $item Input data item
     * @return ParsedDataItemInterface Processed item or NullDataItem if rejected/failed
     */
    public function process(ParsedDataItemInterface $item): ParsedDataItemInterface;
}