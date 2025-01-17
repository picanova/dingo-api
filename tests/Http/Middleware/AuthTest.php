<?php

namespace Picanova\Api\Tests\Http\Middleware;

use Picanova\Api\Auth\Auth;
use Picanova\Api\Http\Middleware\Auth as AuthMiddleware;
use Picanova\Api\Http\Request;
use Picanova\Api\Routing\Route;
use Picanova\Api\Routing\Router;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Tests\Stubs\RoutingAdapterStub;
use Illuminate\Container\Container;
use Illuminate\Routing\Route as IlluminateRoute;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthTest extends BaseTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var RoutingAdapterStub
     */
    protected $adapter;
    /**
     * @var Router|m\LegacyMockInterface|m\MockInterface
     */
    protected $router;
    /**
     * @var Auth|m\LegacyMockInterface|m\MockInterface
     */
    protected $auth;
    /**
     * @var AuthMiddleware
     */
    protected $middleware;

    public function setUp(): void
    {
        $this->container = new Container;
        $this->adapter = new RoutingAdapterStub;
        $this->router = m::mock(Router::class);
        $this->auth = m::mock(Auth::class);
        $this->middleware = new AuthMiddleware($this->router, $this->auth);
    }

    public function testProtectedRouteFiresAuthenticationAndPasses()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andReturn(null);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertSame($handledRequest, $request);
        });
    }

    public function testProtectedRouteAlreadyLoggedIn()
    {
        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(true);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function ($handledRequest) use ($request) {
            $this->assertSame($handledRequest, $request);
        });
    }

    public function testAuthenticationFailsAndExceptionIsThrown()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $exception = new UnauthorizedHttpException('test');

        $request = Request::create('test', 'GET');

        $route = new Route($this->adapter, $this->container, $request, new IlluminateRoute('GET', '/test', [
            'providers' => [],
        ]));

        $this->auth->shouldReceive('check')->once()->with(false)->andReturn(false);
        $this->auth->shouldReceive('authenticate')->once()->with([])->andThrow($exception);

        $this->router->shouldReceive('getCurrentRoute')->once()->andReturn($route);

        $this->middleware->handle($request, function () {
            //
        });
    }
}
