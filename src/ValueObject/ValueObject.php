<?php

namespace Jtrw\DAO\ValueObject;

interface ValueObject
{
    /**
     * @return mixed
     */
    public function toNative();
}