<?php

namespace Jtrw\DAO\ValueObject;

interface ValueObjectInterface
{
    /**
     * @return mixed
     */
    public function toNative();
}