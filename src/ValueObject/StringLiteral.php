<?php

namespace Jtrw\DAO\ValueObject;

class StringLiteral implements ValueObject
{
    /**
     * @var string
     */
    protected string $value;
    
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
    
    /**
     * @return string
     */
    public function toNative(): string
    {
        return $this->value;
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toNative();
    }
}