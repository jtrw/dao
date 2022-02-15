<?php

namespace Jtrw\DAO\ValueObject;

class ArrayLiteral implements ValueObjectInterface
{
    /**
     * @var array
     */
    protected array $value;
    
    /**
     * @param array $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
    
    /**
     * @return array
     */
    public function toNative(): array
    {
        return $this->value;
    }
    
    /**
     * @return string
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->toNative(), JSON_THROW_ON_ERROR);
    }
}