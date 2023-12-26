<?php

namespace Picanova\Api\Contract\Http\RateLimit;

use Picanova\Api\Http\Request;
use Illuminate\Container\Container;

interface HasRateLimiter
{
    /**
     * Get rate limiter callable.
     *
     * @param \Illuminate\Container\Container $app
     * @param \Picanova\Api\Http\Request         $request
     *
     * @return string
     */
    public function getRateLimiter(Container $app, Request $request);
}
