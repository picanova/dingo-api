<?php

namespace Picanova\Api\Tests\Http\RateLimit\Throttle;

use Picanova\Api\Auth\Auth;
use Picanova\Api\Http\RateLimit\Throttle\Authenticated;
use Picanova\Api\Tests\BaseTestCase;
use Illuminate\Container\Container;
use Mockery;

class AuthenticatedTest extends BaseTestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock(Auth::class)->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container;
        $container['api.auth'] = $auth;

        $this->assertTrue((new Authenticated)->match($container));
    }
}
