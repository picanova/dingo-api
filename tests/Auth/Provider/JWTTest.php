<?php

namespace Picanova\Api\Tests\Auth\Provider;

use Picanova\Api\Auth\Provider\JWT;
use Picanova\Api\Routing\Route;
use Picanova\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;

class JWTTest extends BaseTestCase
{
    protected $auth;
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock('Tymon\JWTAuth\JWTAuth');
        $this->provider = new JWT($this->auth);
    }

    public function testValidatingAuthorizationHeaderFailsAndThrowsException()
    {
        $this->expectException(BadRequestHttpException::class);

        $request = Request::create('foo', 'GET');
        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingFailsAndThrowsException()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andThrow(new JWTException('foo'));

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testAuthenticatingSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET');
        $request->headers->set('authorization', 'Bearer foo');

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock(Route::class))->id);
    }

    public function testAuthenticatingWithQueryStringSucceedsAndReturnsUserObject()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foo']);

        $this->auth->shouldReceive('setToken')->with('foo')->andReturn(m::self());
        $this->auth->shouldReceive('authenticate')->once()->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->provider->authenticate($request, m::mock(Route::class))->id);
    }
}
