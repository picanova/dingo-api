<?php

namespace Picanova\Api\Tests\Routing\Adapter;

use Picanova\Api\Routing\Adapter\Lumen;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteParser\Std as StdRouteParser;
use Illuminate\Http\Request;
use Laravel\Lumen\Application;

class LumenTest extends BaseAdapterTest
{
    public function getAdapterInstance()
    {
        $this->container->routeMiddleware([
            'api.auth' => get_class($this->container['api.auth']),
            'api.limiting' => get_class($this->container['api.limiting']),
        ]);

        // When we rebind the "request" instance during testing we'll pull the route resolver
        // from the Lumen request instance and set it on our request so we can fetch
        // the route properly.
        $this->container->rebinding('request', function ($app, $request) {
            $request->setRouteResolver($app[Request::class]->getRouteResolver());
        });

        return new Lumen($this->container, new StdRouteParser, new GcbDataGenerator, function ($routes) {
            return new GcbDispatcher($routes->getData());
        });
    }

    public function getContainerInstance()
    {
        return new Application;
    }

    public function testRoutesWithDomains()
    {
        $this->markTestSkipped('Lumen does not support sub-domain routing.');
    }
}
