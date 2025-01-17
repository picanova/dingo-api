<?php

namespace Picanova\Api\Tests\Routing\Adapter;

use Picanova\Api\Exception\Handler;
use Picanova\Api\Http;
use Picanova\Api\Routing\Adapter\Laravel;
use Picanova\Api\Routing\Adapter\Lumen;
use Picanova\Api\Routing\Router;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Tests\Stubs\MiddlewareStub;
use Illuminate\Contracts\Container\Container;
use Laravel\Lumen\Application;
use Mockery as m;

abstract class BaseAdapterTest extends BaseTestCase
{
    /**
     * @var Container|Application
     */
    protected $container;
    /**
     * @var Laravel|Lumen
     */
    protected $adapter;
    /**
     * @var Handler
     */
    protected $exception;
    /**
     * @var Router
     */
    protected $router;

    public function setUp(): void
    {
        $this->container = $this->getContainerInstance();
        $this->container['Illuminate\Container\Container'] = $this->container;
        $this->container['api.auth'] = new MiddlewareStub;
        $this->container['api.limiting'] = new MiddlewareStub;
        $this->container['api.controllers'] = new MiddlewareStub;
        $this->container['request'] = new Http\Request;

        Http\Request::setAcceptParser(new Http\Parser\Accept('vnd', 'api', 'v1', 'json'));

        $this->adapter = $this->getAdapterInstance();
        $this->exception = m::mock(Handler::class);
        $this->router = new Router($this->adapter, $this->exception, $this->container, null, null);
        app()->instance(\Illuminate\Routing\Router::class, $this->adapter, true);

        Http\Response::setFormatters(['json' => new Http\Response\Format\Json]);
    }

    /**
     * @return Container|Application
     */
    abstract public function getContainerInstance();

    /**
     * @return Laravel|Lumen
     */
    abstract public function getAdapterInstance();

    protected function createRequest($uri, $method, array $headers = [])
    {
        $request = Http\Request::create($uri, $method);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $this->container['request'] = $request;
    }

    public function testBasicRouteVersions()
    {
        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
            $this->router->post('foo', function () {
                return 'posted';
            });
            $this->router->patch('foo', function () {
                return 'patched';
            });
            $this->router->delete('foo', function () {
                return 'deleted';
            });
            $this->router->put('foo', function () {
                return 'put';
            });
            $this->router->options('foo', function () {
                return 'options';
            });
        });

        $this->router->group(['version' => 'v2'], function () {
            $this->router->get('foo', ['version' => 'v3', function () {
                return 'bar';
            }]);
        });

        $this->createRequest('/', 'GET');

        $this->assertArrayHasKey('v1', $this->router->getRoutes(), 'No routes were registered for version 1.');
        $this->assertArrayHasKey('v2', $this->router->getRoutes(), 'No routes were registered for version 2.');
        $this->assertArrayHasKey('v3', $this->router->getRoutes(), 'No routes were registered for version 3.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('foo', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo/', 'GET', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('foo', $this->router->dispatch($request)->getContent(), 'Could not dispatch request with trailing slash.');

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertSame('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v3+json']);
        $this->assertSame('bar', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'POST', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('posted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PATCH', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('patched', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'DELETE', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('deleted', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'PUT', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('put', $this->router->dispatch($request)->getContent());

        $request = $this->createRequest('/foo', 'options', ['accept' => 'application/vnd.api.v1+json']);
        $this->assertSame('options', $this->router->dispatch($request)->getContent());
    }

    public function testAdapterDispatchesRequestsThroughRouter()
    {
        $this->container['request'] = Http\Request::create('/foo', 'GET');

        $this->router->version('v1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $response = $this->router->dispatch($this->container['request']);

        $this->assertSame('foo', $response->getContent());
    }

    public function testRoutesWithPrefix()
    {
        $this->router->version('v1', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['prefix' => 'foo/bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo/bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch prefixed routes.');
    }

    public function testRoutesWithDomains()
    {
        $this->router->version('v1', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2', ['domain' => 'foo.bar'], function () {
            $this->router->get('foo', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('http://foo.bar/foo', 'GET', ['accept' => 'application/vnd.api.v2+json']);
        $this->assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router could not dispatch domain routes.');
    }

    public function testPointReleaseVersions()
    {
        $this->router->version('v1.1', function () {
            $this->router->get('foo', function () {
                return 'foo';
            });
        });

        $this->router->version('v2.0.1', function () {
            $this->router->get('bar', function () {
                return 'bar';
            });
        });

        $request = $this->createRequest('/foo', 'GET', ['accept' => 'application/vnd.api.v1.1+json']);
        $this->assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v2.0.1+json']);
        $this->assertSame('bar', $this->router->dispatch($request)->getContent(), 'Router does not support point release versions.');
    }

    public function testRoutingResources()
    {
        $this->router->version('v1', ['namespace' => '\Picanova\Api\Tests\Stubs'], function () {
            $this->router->resources([
                'bar' => ['RoutingControllerStub', ['only' => ['index']]],
            ]);
        });

        $request = $this->createRequest('/bar', 'GET', ['accept' => 'application/vnd.api.v1+json']);

        $this->assertSame('foo', $this->router->dispatch($request)->getContent(), 'Router did not register controller correctly.');
    }

    public function testIterableRoutes()
    {
        $this->router->version('v1', ['namespace' => '\Picanova\Api\Tests\Stubs'], function () {
            $this->router->post('/', ['uses' => 'RoutingControllerStub@index']);
            $this->router->post('/find', ['uses' => 'RoutingControllerOtherStub@show']);
        });

        $routes = $this->adapter->getIterableRoutes();
        $this->assertTrue(array_key_exists('v1', (array) $routes));
        $this->assertSame(2, count($routes['v1']));
    }
}
