<?php

namespace Picanova\Api\Tests\Routing\Adapter;

use Picanova\Api\Routing\Adapter\Laravel;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;

class LaravelTest extends BaseAdapterTest
{
    public function getAdapterInstance()
    {
        return new Laravel(new Router(new Dispatcher, $this->container));
    }

    public function getContainerInstance()
    {
        return new Container;
    }
}
