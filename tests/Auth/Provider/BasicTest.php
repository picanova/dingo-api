<?php

namespace Picanova\Api\Tests\Auth\Provider;

use Picanova\Api\Auth\Provider\Basic;
use Picanova\Api\Routing\Route;
use Picanova\Api\Tests\BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery as m;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BasicTest extends BaseTestCase
{
    protected $auth;
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->auth = m::mock('Illuminate\Auth\AuthManager');
        $this->provider = new Basic($this->auth);
    }

    public function testInvalidBasicCredentialsThrowsException()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email')->andReturn(new Response('', 401));

        $this->provider->authenticate($request, m::mock(Route::class));
    }

    public function testValidCredentialsReturnsUser()
    {
        $request = Request::create('GET', '/', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic 12345']);

        $this->auth->shouldReceive('onceBasic')->once()->with('email')->andReturn(null);
        $this->auth->shouldReceive('user')->once()->andReturn('foo');

        $this->assertSame('foo', $this->provider->authenticate($request, m::mock(Route::class)));
    }
}
