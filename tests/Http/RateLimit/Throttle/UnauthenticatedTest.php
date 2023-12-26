<?php

namespace Picanova\Api\Tests\Http\RateLimit\Throttle;

use Picanova\Api\Auth\Auth;
use Picanova\Api\Http\RateLimit\Throttle\Unauthenticated;
use Picanova\Api\Tests\BaseTestCase;
use Illuminate\Container\Container;
use Mockery;

class UnauthenticatedTest extends BaseTestCase
{
    public function testThrottleMatchesCorrectly()
    {
        $auth = Mockery::mock(Auth::class)->shouldReceive('check')->once()->andReturn(true)->getMock();
        $container = new Container;
        $container['api.auth'] = $auth;

        $this->assertFalse((new Unauthenticated)->match($container));
    }
}
