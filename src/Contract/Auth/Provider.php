<?php

namespace Picanova\Api\Contract\Auth;

use Picanova\Api\Routing\Route;
use Illuminate\Http\Request;

interface Provider
{
    /**
     * Authenticate the request and return the authenticated user instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Picanova\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route);
}
