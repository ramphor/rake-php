<?php

namespace Ramphor\Rake\Types;

final class PagedType
{
    private $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function QUERY(): self
    {
        return new self('query');
    }

    public static function PATH(): self
    {
        return new self('path');
    }

    public function getValue(): string
    {
        return $this->type;
    }

    public function equals(PagedType $other): bool
    {
        return $this->type === $other->getValue();
    }
}
