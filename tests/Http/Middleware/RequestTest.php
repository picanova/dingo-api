<?php

namespace Picanova\Api\Tests\Http\Middleware;

use Picanova\Api\Contract\Http\Request as RequestContract;
use Picanova\Api\Exception\Handler;
use Picanova\Api\Http\Middleware\Request as RequestMiddleware;
use Picanova\Api\Http\Parser\Accept as AcceptParser;
use Picanova\Api\Http\Request;
use Picanova\Api\Http\RequestValidator;
use Picanova\Api\Http\Validation;
use Picanova\Api\Http\Validation\Accept;
use Picanova\Api\Http\Validation\Domain;
use Picanova\Api\Http\Validation\Prefix;
use Picanova\Api\Routing\Router;
use Picanova\Api\Tests\BaseTestCase;
use Picanova\Api\Tests\ChecksLaravelVersionTrait;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\Request as IlluminateRequest;
use Mockery as m;

class RequestTest extends BaseTestCase
{
    /**
     * @var \Picanova\Api\Tests\Stubs\Application58Stub|\Picanova\Api\Tests\Stubs\Application6Stub|\Picanova\Api\Tests\Stubs\ApplicationStub
     */
    protected $app;
    /**
     * @var Router|m\LegacyMockInterface|m\MockInterface
     */
    protected $router;
    /**
     * @var RequestValidator
     */
    protected $validator;
    /**
     * @var Handler|m\LegacyMockInterface|m\MockInterface
     */
    protected $handler;
    /**
     * @var EventDispatcher
     */
    protected $events;
    /**
     * @var RequestMiddleware
     */
    protected $middleware;

    use ChecksLaravelVersionTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getApplicationStub();
        $this->router = m::mock(Router::class);
        $this->validator = new RequestValidator($this->app);
        $this->handler = m::mock(Handler::class);
        $this->events = new EventDispatcher($this->app);

        $this->app->alias(Request::class, RequestContract::class);

        $this->middleware = new RequestMiddleware($this->app, $this->handler, $this->router, $this->validator, $this->events);
    }

    public function testNoPrefixOrDomainDoesNotMatch()
    {
        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix(null);
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = Request::create('foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }

    public function testPrefixMatchesAndSendsRequestThroughRouter()
    {
        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix('/');
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $this->app[Domain::class] = new Validation\Domain(null);
        $this->app[Prefix::class] = new Validation\Prefix('bar');
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('bar/foo', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = IlluminateRequest::create('bing/bar/foo', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }

    public function testDomainMatchesAndSendsRequestThroughRouter()
    {
        $this->app[Domain::class] = new Validation\Domain('foo.bar');
        $this->app[Prefix::class] = new Validation\Prefix(null);
        $this->app[Accept::class] = new Validation\Accept(new AcceptParser('vnd', 'api', 'v1', 'json'));

        $request = IlluminateRequest::create('http://foo.bar/baz', 'GET');

        $this->router->shouldReceive('dispatch')->once();

        $this->middleware->handle($request, function () {
            //
        });

        $request = IlluminateRequest::create('http://bing.foo.bar/baz', 'GET');

        $this->middleware->handle($request, function ($handled) use ($request) {
            $this->assertSame($handled, $request);
        });
    }
}
