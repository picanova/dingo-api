<?php

namespace Picanova\Api\Tests\Stubs;

class UserStub
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
